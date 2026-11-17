<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminAccountRequest;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{

    private function roles(): Collection
    {
        return Role::select('id', 'name')->get();
    }

    /**
     * الموظفين المتاحين للربط بحساب مشرف (من كل الفروع).
     * الموظف المربوط بمشرف تاني مابيظهرش — الربط واحد لواحد.
     */
    private function linkableEmployees(?Admin $admin = null): Collection
    {
        return Employee::withoutGlobalScope('branch')
            ->where(function ($q) use ($admin) {
                $q->whereNotIn('id', Admin::whereNotNull('employee_id')
                    ->when($admin, fn ($q) => $q->whereKeyNot($admin->id))
                    ->select('employee_id'));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'employee_code'])
            ->map(fn ($e) => (object) ['id' => $e->id, 'label' => "{$e->name} ({$e->employee_code})"]);
    }

    /**
     * ربط المشرف بملف موظف (أو فك الربط). الربط متسجّل في الجدولين
     * (admins.employee_id و employees.admin_id) فلازم يفضلوا متطابقين.
     */
    private function syncEmployeeLink(Admin $admin, ?int $employeeId): void
    {
        $admin->employee_id = $employeeId;
        $admin->save();

        Employee::withoutGlobalScope('branch')
            ->where('admin_id', $admin->id)
            ->when($employeeId, fn ($q) => $q->whereKeyNot($employeeId))
            ->update(['admin_id' => null]);

        if ($employeeId) {
            Employee::withoutGlobalScope('branch')->whereKey($employeeId)->update(['admin_id' => $admin->id]);
        }
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
        $employees = $this->linkableEmployees();
        return view('admin.admins.create', compact('roles', 'employees'));
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

        DB::transaction(fn () => $this->syncEmployeeLink($admin, $request->integer('employee_id') ?: null));

        return redirect()->route('admin.admins')->with('success', __('alert.done_save'));
    }

    // edit
    public function edit(Admin $admin)
    {
        $this->checkIsMainAdmin($admin);
        $roles = $this->roles()->values()->toArray();
        $employees = $this->linkableEmployees($admin);
        return view('admin.admins.edit', compact('admin', 'roles', 'employees'));
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

        DB::transaction(fn () => $this->syncEmployeeLink($admin, $request->integer('employee_id') ?: null));

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
