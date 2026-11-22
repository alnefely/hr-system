<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/** أولوية المهمة (ومستخدمة كمان لأولوية المشروع). */
enum TaskPriority: string
{
    use HasEnumHelpers;

    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return __('task.priority_enums.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Low => 'bg-label-secondary',
            self::Medium => 'bg-label-info',
            self::High => 'bg-label-warning',
            self::Urgent => 'bg-label-danger',
        };
    }
}
