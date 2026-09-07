<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * حالة الاعتماد للجزاءات والمكافآت.
 *
 * المبدأ المحاسبي: أي حركة بتأثر على راتب الموظف (خصم أو استحقاق)
 * لازم يعتمدها شخص تاني غير اللي سجّلها — فصل المهام.
 *
 * المعتمد بس هو اللي بيدخل مسير الرواتب.
 */
enum ApprovalStatus: string
{
    use HasEnumHelpers;

    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('global.approval.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending  => 'bg-label-warning',
            self::Approved => 'bg-label-success',
            self::Rejected => 'bg-label-danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending  => 'fa-solid fa-hourglass-half',
            self::Approved => 'fa-solid fa-circle-check',
            self::Rejected => 'fa-solid fa-circle-xmark',
        };
    }

    /** هل الحركة دي بتدخل مسير الرواتب؟ */
    public function affectsPayroll(): bool
    {
        return $this === self::Approved;
    }

    /** الاعتماد والرفض للمعلق بس. */
    public function isDecidable(): bool
    {
        return $this === self::Pending;
    }

    /** التعديل والحذف مسموح قبل الاعتماد فقط. */
    public function isEditable(): bool
    {
        return $this !== self::Approved;
    }
}
