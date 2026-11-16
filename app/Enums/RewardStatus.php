<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * حالات المكافأة.
 *
 *   pending ──approve──> approved ──(صرف المسير)──> paid
 *      └──reject──> rejected
 *
 * ApprovalStatus مافيهاش paid، والمكافأة بتتعلّم "مدفوعة" لما بندها في
 * المسير يتصرف — فكانت بتظهر في الجدول من غير اسم ولا لون.
 */
enum RewardStatus: string
{
    use HasEnumHelpers;

    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Paid     = 'paid';

    public function label(): string
    {
        return __('reward.status_enums.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending  => 'bg-label-warning',
            self::Approved => 'bg-label-success',
            self::Rejected => 'bg-label-danger',
            self::Paid     => 'bg-label-info',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending  => 'fa-solid fa-hourglass-half',
            self::Approved => 'fa-solid fa-circle-check',
            self::Rejected => 'fa-solid fa-circle-xmark',
            self::Paid     => 'fa-solid fa-money-bill-wave',
        };
    }

    /** التعديل والحذف مسموحين قبل ما المكافأة تدخل المسير بس. */
    public function isEditable(): bool
    {
        return $this === self::Pending || $this === self::Rejected;
    }
}
