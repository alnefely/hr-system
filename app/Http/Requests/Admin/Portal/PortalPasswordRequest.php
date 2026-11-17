<?php

namespace App\Http\Requests\Admin\Portal;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

/** كلمة مرور أولية من الموارد البشرية (تفعيل أو إعادة تعيين) — من غير تأكيد ولا شروط تعقيد. */
class PortalPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', ...Employee::portalPasswordRules()],
        ];
    }

    public function attributes(): array
    {
        return ['password' => __('portal.admin.password')];
    }
}
