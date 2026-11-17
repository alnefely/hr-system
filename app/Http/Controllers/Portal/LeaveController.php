<?php

namespace App\Http\Controllers\Portal;

use App\Enums\LeaveStatus;
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
    public function index(LeaveService $service)
    {
        $employee = $this->employee();
        $year = (int) now()->year;

        $service->ensureBalances([$employee], $year);

        return view('portal.leaves.index', [
            'leaves'  => $employee->leaves()->with('approver:id,name')->paginate(15),
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
