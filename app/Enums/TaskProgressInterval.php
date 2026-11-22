<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/** كل قد إيه الموظف مطلوب منه يحدّث نسبة الإنجاز. */
enum TaskProgressInterval: string
{
    use HasEnumHelpers;

    case None = 'none';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Custom = 'custom';

    public function label(): string
    {
        return __('task.progress_interval_enums.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::None => 'bg-label-secondary',
            self::Daily => 'bg-label-info',
            self::Weekly => 'bg-label-info',
            self::Custom => 'bg-label-primary',
        };
    }
}
