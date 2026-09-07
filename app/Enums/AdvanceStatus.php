<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * حالات السلفة.
 *
 * pending ──approve──> approved ──(اكتمل السداد)──> paid
 *    └────reject────> rejected
 */
enum AdvanceStatus: string
{
    use HasEnumHelpers;

    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Paid     = 'paid';

    public function label(): string
    {
        return __('advance.status_enums.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending  => 'bg-label-warning',
            self::Approved => 'bg-label-info',
            self::Paid     => 'bg-label-success',
            self::Rejected => 'bg-label-danger',
        };
    }

    /** هل بيتخصم منها قسط في المسير؟ */
    public function isDeductible(): bool
    {
        return $this === self::Approved;
    }

    /** الحالات اللي بتقبل تسجيل سداد أو إعفاء. */
    public function acceptsRepayment(): bool
    {
        return in_array($this, [self::Approved, self::Paid], true);
    }
}
