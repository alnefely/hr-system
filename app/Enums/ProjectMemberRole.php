<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/** دور العضو داخل المشروع. */
enum ProjectMemberRole: string
{
    use HasEnumHelpers;

    case Manager = 'manager';
    case Member = 'member';
    case Viewer = 'viewer';

    public function label(): string
    {
        return __('project.member_role_enums.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Manager => 'bg-label-primary',
            self::Member => 'bg-label-info',
            self::Viewer => 'bg-label-secondary',
        };
    }
}
