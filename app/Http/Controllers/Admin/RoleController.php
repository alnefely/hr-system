<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * قائمة الأدوار
     */
    public function index()
    {
        $roles = Role::allWithAdmins();

        return view('admin.roles.index', compact('roles'));
    }

    /**
     * شاشة إضافة دور جديد
     */
    public function create()
    {
        return view('admin.roles.create', [
            'groupedRoles' => grouped_roles(),
        ]);
    }

    /**
     * حفظ دور جديد
     */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        Role::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'roles'       => $data['permissions'],
        ]);

        return redirect()->route('admin.roles')->with('success', __('alert.done_create'));
    }

    /**
     * شاشة تعديل دور
     */
    public function edit(Role $role)
    {
        if ($role->main) {
            return redirect()->route('admin.roles')
                ->with('error', __('role.alert_update', ['name' => $role->name]));
        }

        return view('admin.roles.edit', [
            'role'               => $role,
            'groupedRoles' => grouped_roles(),
        ]);
    }

    /**
     * تحديث دور
     */
    public function update(Request $request, Role $role)
    {
        if ($role->main) {
            return redirect()->route('admin.roles')
                ->with('error', __('role.alert_update', ['name' => $role->name]));
        }

        $data = $this->validated($request, $role);

        // الدور الاحتياطي (2) اسمه ثابت — الصلاحيات بس اللي بتتعدّل
        if ($role->id != 2) {
            $role->name = $data['name'];
        }

        $role->description = $data['description'] ?? null;
        $role->roles       = $data['permissions'];
        $role->save();

        return redirect()->route('admin.roles')->with('success', __('alert.done_update'));
    }

    /**
     * حذف دور
     */
    public function destroy(Role $role)
    {
        if ($role->main || $role->id == 2) {
            return redirect()->back()
                ->with('error', __('role.alert_delete', ['name' => $role->name]));
        }

        $role->delete();

        return redirect()->route('admin.roles')->with('success', __('alert.done_delete'));
    }

    /**
     * قواعد التحقق — مشتركة بين الإضافة والتعديل.
     *
     * permissions.* مقيّدة بالصلاحيات المعرّفة في RoleService، عشان
     * حد ميبعتش صلاحية مخترعة من الـ DevTools.
     */
    protected function validated(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('roles', 'name')->ignore($role?->id),
            ],
            'description'   => ['nullable', 'string', 'max:255'],
            'permissions'   => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(all_roles())],
        ], [
            'permissions.required' => __('role.permissions_required'),
        ]);
    }
}
