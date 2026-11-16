<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminAccountRequest;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

class AdminController extends Controller
{

    private function roles(): Collection
    {
        return Role::select('id', 'name')->get();
    }

    private function checkIsMainAdmin(Admin $admin)
    {
        if ($admin->main) {
            return abort(redirect()->route('admin.admins')->with('error', __('alert.cant_delete_main_admin')));
        }
    }

    // profile
    public function profile()
    {
        $admin = admin();
        return view('admin.admins.profile', compact('admin'));
    }

    // update profile
    public function updateProfile(AdminAccountRequest $request)
    {
        $admin = admin();

        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->img = $request->img;
        if ($request->filled('password')) {
            $admin->password = bcrypt($request->password);
        }
        $admin->save();
        return redirect()->back()->with('success', __('alert.done_save'));
    }

    // index
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $admins = Admin::with('role:id,name');
            return datatables($admins)->make(true);
        }
        $roles = $this->roles();
        return view('admin.admins.index', compact('roles'));
    }

    // create
    public function create()
    {
        $roles = $this->roles();
        return view('admin.admins.create', compact('roles'));
    }

    // store
    public function store(AdminAccountRequest $request)
    {
        $admin = new Admin();
        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->password = bcrypt($request->password);
        $admin->img = $request->img;
        $admin->role_id = $request->role_id;
        $admin->save();
        return redirect()->route('admin.admins')->with('success', __('alert.done_save'));
    }

    // edit
    public function edit(Admin $admin)
    {
        $this->checkIsMainAdmin($admin);
        $roles = $this->roles()->values()->toArray();
        return view('admin.admins.edit', compact('admin', 'roles'));
    }

    // update
    public function update(AdminAccountRequest $request, Admin $admin)
    {
        $this->checkIsMainAdmin($admin);
        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->img = $request->img;
        $admin->role_id = $request->role_id;
        if ($request->filled('password')) {
            $admin->password = bcrypt($request->password);
        }
        $admin->save();
        return redirect()->route('admin.admins')->with('success', __('alert.done_update'));
    }

    // destroy
    public function destroy(Admin $admin)
    {
        $this->checkIsMainAdmin($admin);
        $admin->delete();
        return redirect()->route('admin.admins')->with('success', __('alert.done_delete'));
    }


}
