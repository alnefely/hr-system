<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Support\BranchScopeResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

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
            'password' => [$adminId ? 'nullable' : 'required', 'string', Password::min(8)->letters()->numbers()],
            'img'      => ['nullable', 'string', 'max:255', new \App\Rules\SafeFileReference],
        ];

        if (! $isProfile) {
            $rules['role_id'] = ['required', 'exists:roles,id'];

            // ربط اختياري بملف موظف: بيحدد فرع المشرف (تقييد البيانات بفرعه)
            // ومين بيعتمد طلبات مين. الموظف الواحد يتربط بمشرف واحد بس.
            $rules['employee_id'] = [
                'nullable', 'integer',
                Rule::exists('employees', 'id')->whereNull('deleted_at'),
                Rule::unique('admins', 'employee_id')->ignore($adminId),
            ];
        }

        return $rules;
    }

    /**
     * حدود المشرف غير الرئيسي.
     *
     * قبل كده مشرف الفرع اللي عنده admin_update كان يقدر يعدّل حسابه هو:
     * يفك ربطه بالموظف (فيبقى بيشوف كل الفروع) ويدّي نفسه أي دور.
     *  - مايعدّلش حسابه من هنا (الملف الشخصي بس).
     *  - مايدّيش دور فيه صلاحيات أكتر من صلاحياته، ولا دور رئيسي.
     *  - مشرف الفرع لازم يربط الحساب بموظف من فرعه.
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            $actor = admin();

            if (! $actor || $actor->main || $this->routeIs('admin.profile.update')) {
                return;
            }

            $target = $this->route('admin');

            if ($target && $target->id === $actor->id) {
                $validator->errors()->add('role_id', __('admin.errors.cannot_edit_self'));

                return;
            }

            $role = Role::find($this->input('role_id'));

            if ($role) {
                $mine = app(\App\Services\Role\RoleService::class)->roles();
                $extra = array_diff((array) $role->roles, $mine);

                if ($role->main || $extra) {
                    $validator->errors()->add('role_id', __('admin.errors.role_exceeds_yours'));
                }
            }

            $branchId = BranchScopeResolver::branchId();

            if ($branchId !== null) {
                $employeeBranch = $this->input('employee_id')
                    ? \Illuminate\Support\Facades\DB::table('employees')->where('id', $this->input('employee_id'))->value('branch_id')
                    : null;

                if ((int) $employeeBranch !== $branchId) {
                    $validator->errors()->add('employee_id', __('admin.errors.employee_must_be_in_branch'));
                }
            }
        }];
    }
}
