<?php

namespace App\Http\Controllers\Portal;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use Illuminate\Http\Request;
use App\Exceptions\InvalidStateException;
use App\Http\Requests\Portal\PortalLeaveRequest;
use App\Models\EmployeeLeaveBalance;
use App\Services\HR\LeaveService;

/**
 * إجازاتي: نفس قواعد لوحة الإدارة بالظبط (التداخل، عدد الأيام، الرصيد
 * عند الاعتماد) لأن الطلب بيعدّي على LeaveService.
 */
class LeaveController extends PortalController
{
    public function index(Request $request, LeaveService $service)
    {
        $employee = $this->employee();
        $period = $this->period();

        if ($request->ajax()) {
            return datatables($this->inPeriod($employee->leaves()->reorder(), $period))
                ->addColumn('type_badge', fn ($l) => LeaveType::fromValue($l->leave_type)?->badgeHtml() ?? e($l->leave_type))
                ->editColumn('start_date', fn ($l) => '<span dir="ltr" class="text-nowrap">' . $l->start_date?->format('Y-m-d') . ' → ' . $l->end_date?->format('Y-m-d') . '</span>')
                ->editColumn('days_count', fn ($l) => (float) $l->days_count + 0)
                ->addColumn('status_badge', fn ($l) => LeaveStatus::badgeFor($l->status)
                    . ($l->rejection_reason ? '<div class="small text-danger mt-1">' . e($l->rejection_reason) . '</div>' : ''))
                ->addColumn('action', fn ($l) => view('portal.partials.cancel-button', [
                    'pending' => $l->status === LeaveStatus::Pending->value,
                    'action'  => route('portal.leaves.destroy', $l->id),
                ])->render())
                ->rawColumns(['type_badge', 'start_date', 'status_badge', 'action'])
                ->make(true);
        }

        $year = (int) now()->year;

        $service->ensureBalances([$employee], $year);

        return view('portal.leaves.index', [
            'period'  => $period,
            'balance' => EmployeeLeaveBalance::where('employee_id', $employee->id)->where('year', $year)->first(),
        ]);
    }

    public function store(PortalLeaveRequest $request, LeaveService $service)
    {
        try {
            $service->create(['employee_id' => $this->employee()->id] + $request->validated());
        } catch (InvalidStateException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('portal.leaves.index')->with('success', __('portal.leaves.submitted'));
    }

    public function destroy(int $id, LeaveService $service)
    {
        $leave = $this->employee()->leaves()->whereKey($id)->firstOrFail();

        if ($leave->status !== LeaveStatus::Pending->value) {
            return back()->with('error', __('portal.requests.cannot_cancel'));
        }

        $service->delete($leave);

        return back()->with('success', __('portal.requests.cancelled'));
    }
}
