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
                    $item = $models->get($itemId);

                    if (! $item || $item->status === PayrollItemStatus::Paid->value) {
                        continue;
                    }

                    // الإجمالي لوحده كان بيتعدّل والتفاصيل تفضل زي ما هي، والصرف
                    // بيقرا التفاصيل — فلو الخصم اتصفّر، قسط السلفة كان بيتسجّل
                    // سداد رغم إن الموظف مااتخصمش منه. دلوقتي التفاصيل بتتبني
                    // من الإجمالي الجديد.
                    $item->fill([
                        'allowance_details' => $this->detailsForTotal(
                            (array) $item->allowance_details, (float) ($data['allowances'] ?? 0), (float) $item->allowances, 'reward_id'
                        ),
                        'deduction_details' => $this->detailsForTotal(
                            (array) $item->deduction_details, (float) ($data['deductions'] ?? 0), (float) $item->deductions, 'advance_id'
                        ),
                        'payment_method' => $data['payment_method'],
                        'notes'          => $data['notes'] ?? null,
                    ])->save();
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

        if ($item->status === PayrollItemStatus::Paid->value) {
            throw new InvalidStateException(__('payroll.messages.already_paid'));
        }

        $deductionDetails = $this->cleanDetails($deductions, (array) $item->deduction_details);
        $this->ensureInstallmentsWithinBalance($item, $deductionDetails);

        $item->update([
            'allowance_details' => $this->cleanDetails($allowances, (array) $item->allowance_details),
            'deduction_details' => $deductionDetails,
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

        // المسير بيفضل "معتمد" لحد ما كل البنود تتصرف. لو جزء اتصرف، الرجوع
        // لمسودة كان بيسمح بتعديل مبالغ اتصرفت فعلاً أو حذف المسير بيها.
        $this->ensureNothingPaid($payroll);

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
        $this->ensureNothingPaid($payroll);

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
            $payroll = $this->lockPayable($item->payroll_id);

            // القفل بيمنع إن طلبين متزامنين يدفعوا نفس البند ويخصموا السلفة مرتين.
            $items = $this->unpaid(PayrollItem::whereKey($item->id))->lockForUpdate()->get();

            $this->markItemsPaid($payroll, $items, $paymentMethod);
        });
    }

    /** @param  array<int, int>  $itemIds */
    public function paySelected(Payroll $payroll, array $itemIds, ?string $paymentMethod = null): void
    {
        $this->ensurePayable($payroll);

        DB::transaction(function () use ($payroll, $itemIds, $paymentMethod) {
            $payroll = $this->lockPayable($payroll->id);

            // البنود المدفوعة مابتتعالجش تاني ومابيتخصمش منها قسط السلفة مرة كمان.
            $items = $this->unpaid($payroll->items()->whereIn('id', $itemIds))->lockForUpdate()->get();

            $this->markItemsPaid($payroll, $items, $paymentMethod);
        });
    }

    public function payAll(Payroll $payroll): void
    {
        $this->ensurePayable($payroll);

        DB::transaction(function () use ($payroll) {
            $payroll = $this->lockPayable($payroll->id);
            $items   = $this->unpaid($payroll->items())->lockForUpdate()->get();

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
            excludeAdminId: \App\Services\Notification\NotificationService::actingAdminId(),
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

    /**
     * قفل صف المسير وإعادة فحص حالته جوّه المعاملة — من غيره، إلغاء اعتماد
     * بيحصل بين الفحص والكتابة كان بيتمسح بحالة "مصروف".
     */
    private function lockPayable(int $payrollId): Payroll
    {
        $payroll = Payroll::whereKey($payrollId)->lockForUpdate()->firstOrFail();
        $this->ensurePayable($payroll);

        return $payroll;
    }

    private function ensureNothingPaid(Payroll $payroll): void
    {
        if ($payroll->items()->where('status', PayrollItemStatus::Paid->value)->exists()) {
            throw new InvalidStateException(__('payroll.messages.cant_unapprove_partially_paid'));
        }
    }

    /**
     * قسط السلفة في البند مايزيدش عن رصيدها الفعلي (ناقص المحجوز في
     * مسيرات تانية) — وإلا الموظف بيتخصم منه مبلغ مالوش سداد مقابله.
     */
    private function ensureInstallmentsWithinBalance(PayrollItem $item, array $deductionDetails): void
    {
        $requested = [];
        foreach ($deductionDetails as $row) {
            if (! empty($row['advance_id'])) {
                $requested[(int) $row['advance_id']] = ($requested[(int) $row['advance_id']] ?? 0) + (float) $row['amount'];
            }
        }

        if (! $requested) {
            return;
        }

        $reserved = PayrollRules::reservedAdvanceInstallments([$item->employee_id], $item->id);

        foreach (EmployeeAdvance::whereIn('id', array_keys($requested))->get(['id', 'remaining_amount']) as $advance) {
            $available = round((float) $advance->remaining_amount - (float) ($reserved[$advance->id] ?? 0), 2);

            if (round($requested[$advance->id], 2) > $available) {
                throw new InvalidStateException(__('advance.errors.amount_exceeds_remaining', [
                    'amount'    => number_format($requested[$advance->id], 2),
                    'remaining' => number_format(max(0, $available), 2),
                ]));
            }
        }
    }

    /**
     * بناء التفاصيل من إجمالي متعدّل يدوياً.
     *
     * لو الإجمالي ماتغيّرش التفاصيل بتفضل زي ما هي. لو اتغيّر: السطور
     * المربوطة (قسط سلفة/مكافأة) بتفضل، والباقي بيتجمّع في سطر "تعديل يدوي".
     * لو الإجمالي الجديد أقل من السطور المربوطة، بنشيل المربوط بالترتيب
     * العكسي (يعني الإعفاء من القسط الشهر ده) — ومابيتسجّلش سداد عليه.
     */
    private function detailsForTotal(array $details, float $newTotal, float $currentTotal, string $linkKey): array
    {
        $newTotal = round(max(0, $newTotal), 2);

        if (abs($newTotal - round($currentTotal, 2)) < 0.005) {
            return $details;
        }

        $linked = array_values(array_filter($details, fn ($row) => ! empty($row[$linkKey])));

        while ($linked && array_sum(array_map(fn ($r) => (float) ($r['amount'] ?? 0), $linked)) - $newTotal > 0.005) {
            array_pop($linked);
        }

        $linkedSum = round(array_sum(array_map(fn ($r) => (float) ($r['amount'] ?? 0), $linked)), 2);
        $rest      = round($newTotal - $linkedSum, 2);

        if ($rest > 0) {
            $linked[] = ['title' => __('payroll.generated.manual_adjustment'), 'amount' => $rest];
        }

        return $linked;
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

    /**
     * سطور فيها عنوان ومبلغ موجب.
     *
     * الربط بالسلفة/المكافأة (advance_id, reward_id, type) بيتاخد من السطر
     * الأصلي المخزّن عن طريق ref — عمره ما بيتاخد من المتصفح. قبل كده
     * أي تعديل كان بيمسح الربط: القسط يتخصم ومايتسجّلش سداد، والمكافأة
     * ماتتقفلش "مدفوعة". كل ref بيتستخدم مرة واحدة بس.
     */
    private function cleanDetails(array $rows, array $original = []): array
    {
        $clean = [];
        $used  = [];

        foreach ($rows as $row) {
            if (empty($row['title']) || ! isset($row['amount']) || ! is_numeric($row['amount']) || $row['amount'] < 0) {
                continue;
            }

            $line = [
                'title'  => trim($row['title']),
                'amount' => round((float) $row['amount'], 2),
            ];

            $ref = isset($row['ref']) && is_numeric($row['ref']) ? (int) $row['ref'] : null;

            if ($ref !== null && isset($original[$ref]) && ! isset($used[$ref])) {
                $used[$ref] = true;

                foreach (['advance_id', 'reward_id', 'type'] as $key) {
                    if (isset($original[$ref][$key])) {
                        $line[$key] = $original[$ref][$key];
                    }
                }
            }

            $clean[] = $line;
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

            // تحديث جماعي بدل UPDATE + سجل نشاط لكل بند (300 موظف كانوا
            // ~1000 استعلام والصفوف مقفولة). سجل نشاط واحد ملخّص على المسير.
            $update = [
                'status'     => PayrollItemStatus::Paid->value,
                'paid_at'    => $now,
                'paid_by'    => $adminId,
                'updated_at' => $now,
            ];

            if ($paymentMethod) {
                $update['payment_method'] = $paymentMethod;
            }

            PayrollItem::withoutGlobalScopes()->whereIn('id', $items->modelKeys())->update($update);

            foreach ($items as $item) {
                $item->forceFill($update)->syncOriginal();
            }

            app(\App\Services\Activity\ActivityLogger::class)->log($payroll, 'updated', [], [
                'paid_items'     => $items->count(),
                'paid_item_ids'  => $items->modelKeys(),
                'payment_method' => $paymentMethod,
            ]);

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
            // القفل بيمنع سداد يدوي متزامن من إنه يعدّي الرصيد
            $advances = EmployeeAdvance::whereIn('id', array_keys($installments))
                ->whereIn('status', [AdvanceStatus::Approved->value, AdvanceStatus::Paid->value])
                ->lockForUpdate()
                ->get();

            $note = 'استقطاع قسط سلفة من مسير رواتب شهر ' . $payroll->month . '/' . $payroll->year;

            foreach ($advances as $advance) {
                $remaining = (float) $advance->remaining_amount;

                foreach ($installments[$advance->id] as $amount) {
                    // السداد مايعدّيش الرصيد — الزيادة كانت بتتصفّر بصمت في
                    // recalculateRepayments والموظف متخصوم منه مبلغ مالوش مقابل.
                    $amount = round(min($amount, $remaining), 2);

                    if ($amount <= 0) {
                        continue;
                    }

                    $remaining -= $amount;

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
