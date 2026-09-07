<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * حالات مسير الرواتب ودورة انتقالها.
 *
 *   draft ──approve──> approved ──pay──> paid
 *     │                    │
 *     └──reject──> rejected└──unapprove──> draft
 *
 * paid حالة نهائية: مفيش رجوع منها.
 */
enum PayrollStatus: string
{
    use HasEnumHelpers;

    case Draft    = 'draft';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Paid     = 'paid';

    public function label(): string
    {
        return __('payroll.enums.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Draft    => 'bg-label-warning',
            self::Approved => 'bg-label-info',
            self::Paid     => 'bg-label-success',
            self::Rejected => 'bg-label-danger',
        };
    }

    /** الانتقالات المسموح بيها من الحالة دي. */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft    => [self::Approved, self::Rejected],
            self::Approved => [self::Paid, self::Draft],
            self::Rejected => [self::Draft],
            self::Paid     => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** هل المسير لسه قابل للتعديل؟ */
    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    /** هل يمكن صرف بنوده؟ */
    public function isPayable(): bool
    {
        return $this === self::Approved;
    }
}
