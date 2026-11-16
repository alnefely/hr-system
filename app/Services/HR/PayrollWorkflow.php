<?php

namespace App\Services\HR;

use App\Enums\AdvanceStatus;
use App\Enums\NotificationType;
use App\Enums\PayrollItemStatus;
use App\Enums\PayrollStatus;
use App\Enums\RewardStatus;
use App\Exceptions\InvalidStateException;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeReward;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * دورة حياة مسير الرواتب بعد إصداره: تعديل البنود، الاعتماد والرفض،
 * والصرف وتسوية السلف والمكافآت.
 *
 * كانت كلها جوّه PayrollController (حوالي 560 سطر). الانتقالات بين
 * الحالات بتعدّي على PayrollStatus::canTransitionTo — مصدر واحد للقواعد
 * بدل شروط نصية متكررة في كل دالة.
 *
 * أي عملية مش مسموحة بترمي InvalidStateException برسالة مترجمة.
 */
class PayrollWorkflow
{
    public function __construct(
        private NotificationService $notifications,
    ) {}

    // ========================================================
    //  تعديل البنود (المسودة بس)
    // ========================================================

    /**
     * تعديل البدلات والخصومات وطريقة الصرف لمجموعة بنود.
     *
     * @param  array<int|string, array{allowances?: mixed, deductions?: mixed, payment_method: string, notes?: ?string}>  $items
     */
    public function updateItems(Payroll $payroll, array $items): void
    {
        $this->ensureEditable($payroll);

        DB::transaction(function () use ($payroll, $items) {
            // البنود كلها في استعلام واحد، والإجماليات مرة واحدة في الآخر
            $models = $payroll->items()->whereIn('id', array_keys($items))->get()->keyBy('id');

            PayrollItem::withoutSyncingTotals(function () use ($items, $models) {
                foreach ($items as $itemId => $data) {
                    $models->get($itemId)?->update([
                        'allowances'     => $data['allowances'] ?? 0,
                        'deductions'     => $data['deductions'] ?? 0,
                        'payment_method' => $data['payment_method'],
                        'notes'          => $data['notes'] ?? null,
                    ]);
                }
            });

            $payroll->recalculateTotals();
        });
    }

    /**
     * تعديل تفاصيل بند (البدلات والخصومات سطر سطر).
     * الإجماليات بتتحسب من التفاصيل في PayrollItem::saving.
     */
    public function updateItemDetails(PayrollItem $item, array $allowances, array $deductions, string $paymentMethod, ?string $notes): void
    {
        $this->ensureEditable($item->payroll);

        $item->update([
            'allowance_details' => $this->cleanDetails($allowances),
            'deduction_details' => $this->cleanDetails($deductions),
            'payment_method'    => $paymentMethod,
            'notes'             => $notes,
        ]);
    }

    // ========================================================
    //  الاعتماد
    // ========================================================

    public function approve(Payroll $payroll): void
    {
        $this->transition($payroll, PayrollStatus::Approved, 'payroll.messages.cant_edit_approved', [
            'approved_by' => admin()?->id,
            'approved_at' => now(),
        ]);
    }

    public function reject(Payroll $payroll, string $reason): void
    {
        // إعادة الرفض بسبب جديد مسموحة (تحديث السبب) من غير ما تبقى انتقال.
        $current = $this->status($payroll);

        if ($current !== PayrollStatus::Rejected && ! $current->canTransitionTo(PayrollStatus::Rejected)) {
            throw new InvalidStateException(__('payroll.messages.cant_edit_approved'));
        }

        $payroll->update([
            'status'           => PayrollStatus::Rejected->value,
            'rejected_by'      => admin()?->id,
            'rejected_at'      => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function resubmit(Payroll $payroll): void
    {
        if ($this->status($payroll) !== PayrollStatus::Rejected) {
            throw new InvalidStateException(__('payroll.messages.cant_edit_approved'));
        }

        $payroll->update(['status' => PayrollStatus::Draft->value]);
    }

    /** إلغاء الاعتماد والرجوع لمسودة — مش مسموح بعد الصرف. */
    public function unapprove(Payroll $payroll, string $reason): void
    {
        $current = $this->status($payroll);

        if ($current === PayrollStatus::Paid) {
            throw new InvalidStateException(__('payroll.messages.cant_unapprove_paid'));
        }

        if ($current !== PayrollStatus::Approved) {
            throw new InvalidStateException(__('payroll.messages.cant_edit_approved'));
        }

        $payroll->update([
            'status'           => PayrollStatus::Draft->value,
            'unapproved_by'    => admin()?->id,
            'unapproved_at'    => now(),
            'unapprove_reason' => $reason,
            'approved_by'      => null,
            'approved_at'      => null,
        ]);
    }

    public function delete(Payroll $payroll): void
    {
        $this->ensureEditable($payroll);

        $payroll->delete();
    }

    // ========================================================
    //  الصرف
    // ========================================================

    public function payItem(PayrollItem $item, ?string $paymentMethod = null): void
    {
        $this->ensurePayable($item->payroll);

        if ($item->status === PayrollItemStatus::Paid->value) {
            throw new InvalidStateException(__('payroll.messages.already_paid'));
        }

        DB::transaction(function () use ($item, $paymentMethod) {
            // القفل بيمنع إن طلبين متزامنين يدفعوا نفس البند ويخصموا السلفة مرتين.
            $items = $this->unpaid(PayrollItem::whereKey($item->id))->lockForUpdate()->get();

            $this->markItemsPaid($item->payroll, $items, $paymentMethod);
        });
    }

    /** @param  array<int, int>  $itemIds */
    public function paySelected(Payroll $payroll, array $itemIds, ?string $paymentMethod = null): void
    {
        $this->ensurePayable($payroll);

        DB::transaction(function () use ($payroll, $itemIds, $paymentMethod) {
            // البنود المدفوعة مابتتعالجش تاني ومابيتخصمش منها قسط السلفة مرة كمان.
            $items = $this->unpaid($payroll->items()->whereIn('id', $itemIds))->lockForUpdate()->get();

            $this->markItemsPaid($payroll, $items, $paymentMethod);
        });
    }

    public function payAll(Payroll $payroll): void
    {
        $this->ensurePayable($payroll);

        DB::transaction(function () use ($payroll) {
            $items = $this->unpaid($payroll->items())->lockForUpdate()->get();

            $this->markItemsPaid($payroll, $items);
        });
    }

    // ========================================================
    //  الإشعارات
    // ========================================================

    /** إشعار المعتمِدين بمسير جاهز للمراجعة. */
    public function notifyAwaitingReview(Payroll $payroll): void
    {
        $this->notifications->notifyEligible(
            type: NotificationType::PayrollAwaitingReview,
            title: __('notification.messages.payroll_awaiting_review.title'),
            body: __('notification.messages.payroll_awaiting_review.body', [
                'title' => $payroll->title,
                'net'   => number_format((float) $payroll->total_net_salary, 2),
            ]),
            url: route('admin.payrolls.show', $payroll->id),
            branchId: $payroll->branch_id,
            data: ['payroll_id' => $payroll->id],
            excludeAdminId: admin()?->id,
        );
    }

    // ========================================================
    //  الداخلي
    // ========================================================

    private function status(?Payroll $payroll): ?PayrollStatus
    {
        return PayrollStatus::fromValue($payroll?->status);
    }

    private function ensureEditable(?Payroll $payroll): void
    {
        if (! $this->status($payroll)?->isEditable()) {
            throw new InvalidStateException(__('payroll.messages.cant_edit_approved'));
        }
    }

    private function ensurePayable(?Payroll $payroll): void
    {
        if (! $this->status($payroll)?->isPayable()) {
            throw new InvalidStateException(__('payroll.messages.cant_pay_unapproved'));
        }
    }

    private function transition(Payroll $payroll, PayrollStatus $target, string $errorKey, array $attributes = []): void
    {
        if (! $this->status($payroll)?->canTransitionTo($target)) {
            throw new InvalidStateException(__($errorKey));
        }

        $payroll->update(['status' => $target->value] + $attributes);
    }

    private function unpaid($query)
    {
        return $query->where('status', '!=', PayrollItemStatus::Paid->value);
    }

    /** سطور فيها عنوان ومبلغ بس، والمبلغ رقم موجب. */
    private function cleanDetails(array $rows): array
    {
        $clean = [];

        foreach ($rows as $row) {
            if (! empty($row['title']) && isset($row['amount']) && is_numeric($row['amount']) && $row['amount'] >= 0) {
                $clean[] = [
                    'title'  => trim($row['title']),
                    'amount' => (float) $row['amount'],
                ];
            }
        }

        return $clean;
    }

    /**
     * صرف مجموعة بنود دفعة واحدة.
     *
     * الصرف مابيغيّرش المبالغ، فإعادة حساب إجماليات المسير بتتوقف. كل
     * السلف المرتبطة بتتجاب في استعلام واحد، والمكافآت في UPDATE واحد.
     * المسير بيتقفل كمدفوع لو مابقاش فيه بنود معلّقة.
     *
     * @param  Collection<int, PayrollItem>  $items  مقفولة بـ lockForUpdate
     */
    private function markItemsPaid(Payroll $payroll, Collection $items, ?string $paymentMethod = null): void
    {
        if ($items->isNotEmpty()) {
            $now     = now();
            $adminId = admin()?->id;

            PayrollItem::withoutSyncingTotals(function () use ($items, $paymentMethod, $now, $adminId) {
                foreach ($items as $item) {
                    $item->update([
                        'status'         => PayrollItemStatus::Paid->value,
                        'paid_at'        => $now,
                        'paid_by'        => $adminId,
                        'payment_method' => $paymentMethod ?? $item->payment_method,
                    ]);
                }
            });

            $this->settleAdvancesAndRewards($payroll, $items);
        }

        if ($this->status($payroll) !== PayrollStatus::Paid
            && ! $this->unpaid($payroll->items())->exists()) {
            $payroll->update([
                'status'  => PayrollStatus::Paid->value,
                'paid_by' => admin()?->id,
                'paid_at' => now(),
            ]);
        }
    }

    /**
     * تسوية أقساط السلف وصرف المكافآت للبنود المدفوعة.
     *
     * @param  Collection<int, PayrollItem>  $items
     */
    private function settleAdvancesAndRewards(Payroll $payroll, Collection $items): void
    {
        $installments = []; // advance_id => [amounts...]
        $rewardIds    = [];

        foreach ($items as $item) {
            foreach ((array) $item->deduction_details as $ded) {
                $amount = (float) ($ded['amount'] ?? 0);
                if (! empty($ded['advance_id']) && $amount > 0) {
                    $installments[(int) $ded['advance_id']][] = $amount;
                }
            }

            foreach ((array) $item->allowance_details as $al) {
                if (! empty($al['reward_id'])) {
                    $rewardIds[] = (int) $al['reward_id'];
                }
            }
        }

        if ($installments) {
            $advances = EmployeeAdvance::whereIn('id', array_keys($installments))
                ->whereIn('status', [AdvanceStatus::Approved->value, AdvanceStatus::Paid->value])
                ->get();

            $note = 'استقطاع قسط سلفة من مسير رواتب شهر ' . $payroll->month . '/' . $payroll->year;

            foreach ($advances as $advance) {
                foreach ($installments[$advance->id] as $amount) {
                    $advance->repayments()->create([
                        'amount'         => $amount,
                        'repayment_date' => now()->toDateString(),
                        'type'           => 'payroll_deduction',
                        'notes'          => $note,
                        'created_by'     => admin()?->id,
                    ]);
                }

                $advance->recalculateRepayments();
            }
        }

        if ($rewardIds) {
            // المكافأة "مدفوعة" بعد ما تتصرف مع المسير
            EmployeeReward::whereIn('id', array_unique($rewardIds))
                ->where('status', RewardStatus::Approved->value)
                ->update(['status' => RewardStatus::Paid->value]);
        }
    }
}
