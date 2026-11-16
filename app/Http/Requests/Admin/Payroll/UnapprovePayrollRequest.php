<?php

namespace App\Http\Requests\Admin\Payroll;

use App\Http\Requests\Admin\ReasonRequest;

/** إلغاء اعتماد مسير — حقل unapprove_reason. */
class UnapprovePayrollRequest extends ReasonRequest
{
    protected string $field = 'unapprove_reason';

    protected int $min = 3;
}
