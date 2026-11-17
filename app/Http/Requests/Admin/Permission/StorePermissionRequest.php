<?php

namespace App\Http\Requests\Admin\Permission;

use App\Rules\AccessibleEmployee;
use Illuminate\Foundation\Http\FormRequest;

/**
 * طلب استئذان من لوحة الإدارة.
 *
 * hours_count مطلوب في الفورم لكنه مابيتحفظش زي ما هو — PermissionService
 * بيحسبه من وقت الخروج والعودة.
 */
class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'     => ['required', new AccessibleEmployee],
            'permission_date' => ['required', 'date'],
            'start_time'      => ['required', 'date_format:H:i'],
            'end_time'        => ['required', 'date_format:H:i', 'after:start_time'],
            'hours_count'     => ['required', 'numeric', 'min:0.1', 'max:12'],
            'reason'          => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** البيانات للحفظ — من غير الساعات المكتوبة يدوياً. */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if ($key === null) {
            unset($data['hours_count']);
        }

        return $data;
    }
}
