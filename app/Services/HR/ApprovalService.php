<?php

namespace App\Services\HR;

use App\Enums\ApprovalStatus;
use App\Exceptions\InvalidStateException;
use Illuminate\Database\Eloquent\Model;

/**
 * دورة الاعتماد المشتركة للأذونات والجزاءات والمكافآت.
 *
 * كانت نفس الشروط مكررة في كل كنترولر بنصوص زي 'pending' و'approved'.
 * الرسائل بتتاخد من ملف ترجمة الموديول: "{module}.messages.not_pending"
 * و "{module}.messages.cannot_self_approve".
 */
class ApprovalService
{
    /**
     * اعتماد طلب معلّق.
     *
     * $separateDuties: اللي سجّل الحركة مايعتمدهاش (إلا المدير العام) —
     * أي حركة بتأثر على راتب الموظف لازم يراجعها شخص تاني.
     */
    public function approve(Model $record, string $module, bool $separateDuties = false): void
    {
        $this->ensurePending($record, $module);

        if ($record->employee_id ?? null) {
            self::ensureNotBeneficiary((int) $record->employee_id, $module);
        }

        $this->ensurePayrollOpen($record);

        if ($separateDuties
            && $record->created_by
            && $record->created_by === admin()?->id
            && ! admin()?->main) {
            throw new InvalidStateException(__("{$module}.messages.cannot_self_approve"));
        }

        $record->update([
            'status'      => ApprovalStatus::Approved->value,
            'approved_by' => admin()?->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * رفض طلب مع توثيق السبب.
     *
     * $allowApproved: الأذونات ممكن تترفض بعد اعتمادها (إلغاء)، أما الجزاءات
     * والمكافآت فالمعلّق بس — بعد الاعتماد بقت جزء من المسير.
     */
    public function reject(Model $record, string $reason, string $module, bool $allowApproved = false): void
    {
        $allowed = [ApprovalStatus::Pending->value];

        if ($allowApproved) {
            $allowed[] = ApprovalStatus::Approved->value;
        }

        if (! in_array($record->status, $allowed, true)) {
            throw new InvalidStateException(__("{$module}.messages.not_pending"));
        }

        if ($record->status === ApprovalStatus::Approved->value) {
            $this->ensurePayrollOpen($record);
        }

        $record->update([
            'status'           => ApprovalStatus::Rejected->value,
            'rejection_reason' => $reason,
            'approved_by'      => admin()?->id,
            'approved_at'      => now(),
        ]);
    }

    /**
     * المسؤول مايعتمدش طلب لنفسه (إجازته أو سلفته أو مكافأته) — إلا المدير العام.
     */
    public static function ensureNotBeneficiary(?int $employeeId, string $module): void
    {
        $admin = admin();

        if ($admin && ! $admin->main && $employeeId && (int) $admin->employee_id === $employeeId) {
            throw new InvalidStateException(__('global.cannot_approve_own_request'));
        }
    }

    /**
     * الإذن المعتمد بيدخل حساب ساعات المسير — قراره مايتغيّرش بعد اعتماد
     * مسير شهره (الجزاءات والمكافآت بترفض المعتمد أصلاً).
     */
    private function ensurePayrollOpen(Model $record): void
    {
        if ($record instanceof \App\Models\EmployeePermission
            && $record->permission_date
            && PayrollRules::hasClosedPayroll((int) $record->employee_id, $record->permission_date, $record->permission_date)) {
            throw new InvalidStateException(__('payroll.messages.period_closed'));
        }
    }

    private function ensurePending(Model $record, string $module): void
    {
        if ($record->status !== ApprovalStatus::Pending->value) {
            throw new InvalidStateException(__("{$module}.messages.not_pending"));
        }
    }
}
