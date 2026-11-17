<?php

namespace App\Http\Requests\Portal;

use App\Enums\LeaveType;
use Illuminate\Foundation\Http\FormRequest;

/** طلب إجازة من الموظف لنفسه. */
class PortalLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'leave_type' => ['required', LeaveType::rule()],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'days_count' => ['required', 'numeric', 'min:0.5'],
            'reason'     => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'leave_type' => __('portal.leaves.type'),
            'start_date' => __('portal.leaves.from'),
            'end_date'   => __('portal.leaves.to'),
            'days_count' => __('portal.leaves.days'),
            'reason'     => __('portal.fields.reason'),
        ];
    }
}
