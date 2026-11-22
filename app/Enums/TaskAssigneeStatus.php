<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/** حالة تكليف الموظف بالمهمة. */
enum TaskAssigneeStatus: string
{
    use HasEnumHelpers;

    case Active = 'active';
    case Declined = 'declined';

    public function label(): string
    {
        return __('task.assignee_status_enums.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Active => 'bg-label-success',
            self::Declined => 'bg-label-danger',
        };
    }
}
