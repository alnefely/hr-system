<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * إضافة/تعديل دور.
 *
 * permissions.* مقيّدة بالصلاحيات المعرّفة في RoleService، عشان حد
 * مايبعتش صلاحية مخترعة من الـ DevTools.
 */
class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('roles', 'name')->ignore($this->route('role')?->id),
            ],
            'description'   => ['nullable', 'string', 'max:255'],
            'permissions'   => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(all_roles())],
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.required' => __('role.permissions_required'),
        ];
    }
}
