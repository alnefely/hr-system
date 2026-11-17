<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

/** طلب استئذان من الموظف لنفسه — employee_id مابيجيش من الفورم أبداً. */
class PortalPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permission_date' => ['required', 'date'],
            'start_time'      => ['required', 'date_format:H:i'],
            'end_time'        => ['required', 'date_format:H:i', 'after:start_time'],
            'reason'          => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'permission_date' => __('portal.permissions.date'),
            'start_time'      => __('portal.permissions.from'),
            'end_time'        => __('portal.permissions.to'),
            'reason'          => __('portal.fields.reason'),
        ];
    }
}
