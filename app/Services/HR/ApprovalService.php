<?php

namespace App\Services\HR;

use App\Enums\ApprovalStatus;
use App\Exceptions\InvalidStateException;
use Illuminate\Database\Eloquent\Model;

/**
 * دورة الاعتماد المشتركة للأذونات والجزاءات والمكافآت.
 *
 * كانت نفس الشروط مكررة في كل كنترولر بنصوص زي 'pending' و'approved'.
 * الرسائل بتتاخد من ملف ترجمة الموديول: "{module}.messages.not_pending".
 *
 * مين يعتمد بتحدده صلاحية الاعتماد في الدور بس — مفيش منع لاعتماد
 * حركة سجّلها المسؤول بنفسه أو طلب خاص بيه.
 */
class ApprovalService
{
    /**
     * اعتماد طلب معلّق.
     */
    public function approve(Model $record, string $module): void
    {
        $this->ensurePending($record, $module);

        $this->ensurePayrollOpen($record);

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
