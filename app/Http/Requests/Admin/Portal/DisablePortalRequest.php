<?php

namespace App\Http\Requests\Admin\Portal;

use App\Http\Requests\Admin\ReasonRequest;

/** إيقاف دخول الموظف — السبب إلزامي لأنه بيظهر للموظف. */
class DisablePortalRequest extends ReasonRequest
{
    protected string $field = 'reason';

    protected int $min = 3;

    public function attributes(): array
    {
        return ['reason' => __('portal.admin.disable_reason')];
    }
}
