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

        $record->update([
            'status'           => ApprovalStatus::Rejected->value,
            'rejection_reason' => $reason,
            'approved_by'      => admin()?->id,
            'approved_at'      => now(),
        ]);
    }

    private function ensurePending(Model $record, string $module): void
    {
        if ($record->status !== ApprovalStatus::Pending->value) {
            throw new InvalidStateException(__("{$module}.messages.not_pending"));
        }
    }
}
