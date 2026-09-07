<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/** أنواع العمليات في سجل حركات النظام. */
enum ActivityEvent: string
{
    use HasEnumHelpers;

    case Created      = 'created';
    case Updated      = 'updated';
    case Deleted      = 'deleted';
    case Restored     = 'restored';
    case ForceDeleted = 'force_deleted';

    public function label(): string
    {
        return __('activity.events.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Created      => 'bg-label-success',
            self::Updated      => 'bg-label-info',
            self::Deleted      => 'bg-label-warning',
            self::ForceDeleted => 'bg-label-danger',
            self::Restored     => 'bg-label-primary',
        };
    }

    /** هل العملية دي بتشيل بيانات؟ */
    public function isDestructive(): bool
    {
        return in_array($this, [self::Deleted, self::ForceDeleted], true);
    }
}
