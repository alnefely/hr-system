<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/** حالة مراجعة تسليم المهمة. */
enum TaskReviewStatus: string
{
    use HasEnumHelpers;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('task.review_status_enums.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'bg-label-warning',
            self::Approved => 'bg-label-success',
            self::Rejected => 'bg-label-danger',
        };
    }
}
