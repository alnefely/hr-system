<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Employee;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password:employee'],
            'password'         => ['required', 'confirmed', 'different:current_password', ...Employee::portalPasswordRules()],
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => __('portal.password.current'),
            'password'         => __('portal.password.new'),
        ];
    }
}
