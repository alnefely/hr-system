<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/** حالة صرف بند راتب موظف واحد. */
enum PayrollItemStatus: string
{
    use HasEnumHelpers;

    case Pending = 'pending';
    case Paid    = 'paid';

    public function label(): string
    {
        return __('payroll.enums.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'bg-label-warning',
            self::Paid    => 'bg-label-success',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }
}
