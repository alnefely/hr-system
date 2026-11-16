<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * إضافة/تعديل مشرف، وتعديل الملف الشخصي.
 *
 * الفرق بين الحالات:
 *  - الإضافة: كلمة المرور إجبارية.
 *  - الملف الشخصي: مفيش role_id (المشرف مايغيّرش دوره بنفسه).
 */
class AdminAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isProfile = $this->routeIs('admin.profile.update');

        // في الملف الشخصي المشرف هو المستخدم الحالي، وفي التعديل من الرابط
        $adminId = $isProfile ? admin()?->id : $this->route('admin')?->id;

        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', Rule::unique('admins', 'email')->ignore($adminId)],
            'password' => [$adminId ? 'nullable' : 'required', 'string', 'min:6'],
            'img'      => ['nullable', 'string', 'max:255'],
        ];

        if (! $isProfile) {
            $rules['role_id'] = ['required', 'exists:roles,id'];
        }

        return $rules;
    }
}
