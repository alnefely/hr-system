<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/** حالة طلب الإجازة. */
enum LeaveStatus: string
{
    use HasEnumHelpers;

    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('leave.status_enums.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending  => 'bg-label-warning',
            self::Approved => 'bg-label-success',
            self::Rejected => 'bg-label-danger',
        };
    }

    /** الرصيد بيتخصم للمعتمد بس. */
    public function consumesBalance(): bool
    {
        return $this === self::Approved;
    }

    /** الاعتماد والرفض للطلبات المعلقة فقط. */
    public function isDecidable(): bool
    {
        return $this === self::Pending;
    }
}
