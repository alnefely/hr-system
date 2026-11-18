<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ApprovalStatus;
use App\Http\Requests\Portal\PortalPermissionRequest;
use App\Services\HR\PayrollRules;
use App\Services\HR\PermissionService;
use Illuminate\Http\Request;

/** استئذاناتي: الطلب والمتابعة والإلغاء قبل البت. */
class PermissionController extends PortalController
{
    public function index(Request $request)
    {
        $employee = $this->employee();
        $period = $this->period();

        if ($request->ajax()) {
            // reorder: ترتيب العلاقة الافتراضي كان هيغلب ترتيب الجدول
            return datatables($this->inPeriod($employee->permissions()->reorder(), $period))
                ->editColumn('permission_date', fn ($p) => $p->permission_date?->format('Y-m-d'))
                ->addColumn('time', fn ($p) => '<span dir="ltr" class="text-nowrap">' . e($p->start_time) . ' - ' . e($p->end_time) . '</span>')
                ->editColumn('hours_count', fn ($p) => (float) $p->hours_count + 0)
                ->addColumn('status_badge', fn ($p) => ApprovalStatus::badgeFor($p->status, 'permission.status_enums')
                    . ($p->rejection_reason ? '<div class="small text-danger mt-1">' . e($p->rejection_reason) . '</div>' : ''))
                ->addColumn('action', fn ($p) => view('portal.partials.cancel-button', [
                    'pending' => $p->status === ApprovalStatus::Pending->value,
                    'action'  => route('portal.permissions.destroy', $p->id),
                ])->render())
                ->rawColumns(['time', 'status_badge', 'action'])
                ->make(true);
        }

        $approvedThisMonth = (float) $employee->permissions()
            ->where('status', ApprovalStatus::Approved->value)
            ->inMonth(now()->year, now()->month)
            ->sum('hours_count');

        return view('portal.permissions.index', [
            'period'            => $period,
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
