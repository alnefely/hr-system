<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ApprovalStatus;
use App\Http\Requests\Portal\PortalPermissionRequest;
use App\Services\HR\PayrollRules;
use App\Services\HR\PermissionService;

/** استئذاناتي: الطلب والمتابعة والإلغاء قبل البت. */
class PermissionController extends PortalController
{
    public function index()
    {
        $employee = $this->employee();

        $permissions = $employee->permissions()
            ->with('approver:id,name')
            ->paginate(15);

        $approvedThisMonth = (float) $employee->permissions()
            ->where('status', ApprovalStatus::Approved->value)
            ->inMonth(now()->year, now()->month)
            ->sum('hours_count');

        return view('portal.permissions.index', [
            'permissions'       => $permissions,
            'approvedThisMonth' => $approvedThisMonth,
            'limit'             => PayrollRules::permissionLimit($employee),
        ]);
    }

    public function store(PortalPermissionRequest $request, PermissionService $service)
    {
        $service->create(['employee_id' => $this->employee()->id] + $request->validated());

        return redirect()->route('portal.permissions.index')->with('success', __('portal.permissions.submitted'));
    }

    /** إلغاء طلب لسه معلّق — بعد البت مايتلغيش من البوابة. */
    public function destroy(int $id)
    {
        $permission = $this->employee()->permissions()->whereKey($id)->firstOrFail();

        if ($permission->status !== ApprovalStatus::Pending->value) {
            return back()->with('error', __('portal.requests.cannot_cancel'));
        }

        $permission->delete();

        return back()->with('success', __('portal.requests.cancelled'));
    }
}
