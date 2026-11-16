<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Role;

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
    public function store(RoleRequest $request)
    {
        $data = $request->validated();

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
    public function update(RoleRequest $request, Role $role)
    {
        if ($role->main) {
            return redirect()->route('admin.roles')
                ->with('error', __('role.alert_update', ['name' => $role->name]));
        }

        $data = $request->validated();

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
}
