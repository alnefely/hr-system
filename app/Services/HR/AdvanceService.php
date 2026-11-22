<?php

namespace App\Services\HR;

use App\Enums\AdvanceStatus;
use App\Enums\NotificationType;
use App\Exceptions\InvalidStateException;
use App\Models\EmployeeAdvance;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\DB;

/**
 * السلف: الطلب والاعتماد والسداد اليدوي والإعفاء.
 *
 * كانت كلها جوّه EmployeeAdvanceController. الأرصدة دايماً بتتحسب من
 * جدول السدادات (recalculateRepayments) مش بالزيادة والنقص.
 *
 * أقساط المسير بتتسوّى في PayrollWorkflow وقت صرف البند.
 */
class AdvanceService
{
    public function __construct(
        private NotificationService $notifications,
    ) {}

    public function create(array $data): EmployeeAdvance
    {
        $installments = max(1, (int) $data['installments_count']);

        $advance = EmployeeAdvance::create([
            'employee_id'         => $data['employee_id'],
            'amount'              => $data['amount'],
            'installments_count'  => $installments,
            'monthly_installment' => round((float) $data['amount'] / $installments, 2),
            'paid_amount'         => 0.00,
            'waived_amount'       => 0.00,
            'remaining_amount'    => $data['amount'],
            'advance_date'        => $data['advance_date'],
            'start_date'          => $data['start_date'],
            'status'              => AdvanceStatus::Pending->value,
            'reason'              => $data['reason'] ?? null,
            'notes'               => $data['notes'] ?? null,
        ]);

        $this->notifyRequested($advance);

        return $advance;
    }

    public function approve(EmployeeAdvance $advance): void
    {
        $this->decide($advance, AdvanceStatus::Approved);
    }

    public function reject(EmployeeAdvance $advance): void
    {
        $this->decide($advance, AdvanceStatus::Rejected);
    }

    /** سداد نقدي مباشر خارج المسير. */
    public function recordPayment(EmployeeAdvance $advance, float $amount, string $date, ?string $notes): void
    {
        $this->addRepayment($advance, $amount, [
            'repayment_date' => $date,
            'type'           => 'manual_payment',
            'notes'          => $notes ?? 'سداد نقدي مباشر',
        ]);
    }

    /** إسقاط جزء من السلفة بقرار إداري. */
    public function waive(EmployeeAdvance $advance, float $amount, string $reason): void
    {
        $this->addRepayment($advance, $amount, [
            'repayment_date' => now()->toDateString(),
            'type'           => 'manager_waiver',
            'notes'          => 'إسقاط إداري معتمد: ' . $reason,
        ]);
    }

    /**
     * الحذف ممنوع لو عليها سداد أو إعفاء: الحركات دي بتأثر على حسابات
     * الموظف والمسيرات، وحذف السلفة بيخفيها من غير ما يلغيها.
     */
    public function delete(EmployeeAdvance $advance): void
    {
        if ((float) $advance->paid_amount > 0 || (float) $advance->waived_amount > 0 || $advance->repayments()->exists()) {
            throw new InvalidStateException(__('advance.errors.cannot_delete_repaid'));
        }

        // قسطها محجوز في مسير لسه ماتصرفش: الحذف كان بيخلي المسير يخصم
        // المبلغ من غير ما يتسجّل سداد على أي سلفة.
        if (isset(PayrollRules::reservedAdvanceInstallments([$advance->employee_id])[$advance->id])) {
            throw new InvalidStateException(__('advance.errors.in_open_payroll'));
        }

        $advance->delete();
    }

    // ========================================================
    //  الداخلي
    // ========================================================

    private function decide(EmployeeAdvance $advance, AdvanceStatus $decision): void
    {
        if ($advance->status !== AdvanceStatus::Pending->value) {
            throw new InvalidStateException(__('advance.errors.not_pending'));
        }

        $advance->update([
            'status'      => $decision->value,
            'approved_by' => admin()?->id,
            'approved_at' => now(),
        ]);

        $this->notifyDecided($advance, $decision);
    }

    /**
     * إضافة حركة سداد/إعفاء على سلفة معتمدة.
     *
     * قبل كده السداد كان مسموح على سلفة معلّقة أو مرفوضة، ولو الرصيد
     * خلص كانت بتتحوّل "مسددة" من غير ما حد يعتمدها أصلاً.
     */
    private function addRepayment(EmployeeAdvance $advance, float $amount, array $attributes): void
    {
        DB::transaction(function () use ($advance, $amount, $attributes) {
            // قفل السلفة لقراءة أحدث رصيد بشكل ذري
            $locked = EmployeeAdvance::whereKey($advance->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [AdvanceStatus::Approved->value, AdvanceStatus::Paid->value], true)) {
                throw new InvalidStateException(__('advance.errors.not_approved'));
            }

            if ((float) $locked->remaining_amount <= 0) {
                throw new InvalidStateException(__('advance.errors.already_fully_paid'));
            }

            // المتاح = الرصيد ناقص الأقساط المحجوزة في مسيرات لسه ماتصرفتش،
            // عشان المسير وقت صرفه يلاقي القسط اللي خصمه من الموظف.
            $reserved  = (float) (PayrollRules::reservedAdvanceInstallments([$locked->employee_id])[$locked->id] ?? 0);
            $available = round((float) $locked->remaining_amount - $reserved, 2);

            if ($amount > $available) {
                throw new InvalidStateException(__('advance.errors.amount_exceeds_remaining', [
                    'amount'    => number_format($amount, 2),
                    'remaining' => number_format(max(0, $available), 2),
                ]));
            }

            $locked->repayments()->create($attributes + [
                'amount'     => $amount,
                'created_by' => admin()?->id,
            ]);

            $locked->recalculateRepayments();
        });
    }

    /** إشعار المسؤولين بطلب سلفة جديد. */
    private function notifyRequested(EmployeeAdvance $advance): void
    {
        $employee = $advance->employee;

        $this->notifications->notifyEligible(
            type: NotificationType::AdvanceRequested,
            title: __('notification.messages.advance_requested.title'),
            body: __('notification.messages.advance_requested.body', [
                'employee'     => $employee?->name ?? '-',
                'amount'       => number_format((float) $advance->amount, 2),
                'installments' => $advance->installments_count,
            ]),
            url: route('admin.advances.index', ['employee_id' => $advance->employee_id]),
            branchId: $employee?->branch_id,
            data: ['advance_id' => $advance->id],
            excludeAdminId: \App\Services\Notification\NotificationService::actingAdminId(),
        );
    }

    /** إشعار مقدّم الطلب بنتيجة المراجعة. */
    private function notifyDecided(EmployeeAdvance $advance, AdvanceStatus $decision): void
    {
        $employee = $advance->employee;

        if (! $employee?->admin_id) {
            return;
        }

        $this->notifications->send(
            adminId: $employee->admin_id,
            type: NotificationType::AdvanceDecided,
            title: __('notification.messages.advance_decided.title'),
            body: __('notification.messages.advance_decided.body', [
                'amount' => number_format((float) $advance->amount, 2),
                'status' => $decision->label(),
            ]),
            url: route('admin.advances.index', ['employee_id' => $advance->employee_id]),
            branchId: $employee->branch_id,
            data: ['advance_id' => $advance->id, 'decision' => $decision->value],
        );
    }
}
