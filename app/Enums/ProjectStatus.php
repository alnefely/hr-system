<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/** حالة المشروع. */
enum ProjectStatus: string
{
    use HasEnumHelpers;

    case Planning = 'planning';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('project.status_enums.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Planning => 'bg-label-secondary',
            self::Active => 'bg-label-primary',
            self::OnHold => 'bg-label-warning',
            self::Completed => 'bg-label-success',
            self::Cancelled => 'bg-label-dark',
        };
    }
}
