<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/** نوع تذكير المهمة. */
enum TaskReminderType: string
{
    use HasEnumHelpers;

    case DueSoon = 'due_soon';
    case Overdue = 'overdue';
    case ProgressUpdate = 'progress_update';
    case Custom = 'custom';

    public function label(): string
    {
        return __('task.reminder_type_enums.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::DueSoon => 'bg-label-warning',
            self::Overdue => 'bg-label-danger',
            self::ProgressUpdate => 'bg-label-info',
            self::Custom => 'bg-label-secondary',
        };
    }
}
