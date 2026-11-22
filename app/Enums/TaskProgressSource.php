<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/** مصدر حساب نسبة الإنجاز. */
enum TaskProgressSource: string
{
    use HasEnumHelpers;

    case Manual = 'manual';
    case Checklist = 'checklist';
    case Subtasks = 'subtasks';

    public function label(): string
    {
        return __('task.progress_source_enums.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Manual => 'bg-label-primary',
            self::Checklist => 'bg-label-info',
            self::Subtasks => 'bg-label-info',
        };
    }
}
