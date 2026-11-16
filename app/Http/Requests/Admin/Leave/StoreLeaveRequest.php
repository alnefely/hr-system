<?php

namespace App\Http\Requests\Admin\Leave;

use App\Enums\LeaveType;
use App\Rules\AccessibleEmployee;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', new AccessibleEmployee],
            'leave_type'  => ['required', LeaveType::rule()],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
            'days_count'  => ['required', 'numeric', 'min:0.5'],
            'reason'      => ['nullable', 'string', 'max:1000'],
            'attachment'  => ['nullable', 'string', 'max:1000'],
        ];
    }
}
