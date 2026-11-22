<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/** تكرار المهمة. */
enum TaskRecurrenceFrequency: string
{
    use HasEnumHelpers;

    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return __('task.frequency_enums.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Daily => 'bg-label-info',
            self::Weekly => 'bg-label-info',
            self::Monthly => 'bg-label-primary',
            self::Yearly => 'bg-label-primary',
        };
    }
}
