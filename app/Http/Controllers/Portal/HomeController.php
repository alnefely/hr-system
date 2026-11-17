<?php

namespace App\Http\Controllers\Portal;

use App\Enums\AdvanceStatus;
use App\Enums\ApprovalStatus;
use App\Enums\LeaveStatus;
use App\Enums\PayrollItemStatus;
use App\Models\EmployeeLeaveBalance;
use App\Services\HR\LeaveService;
use App\Services\HR\PayrollRules;

/** الرئيسية: رصيدي ومستحقاتي وطلباتي في شاشة واحدة. */
class HomeController extends PortalController
{
    public function index(LeaveService $leaves)
    {
        $employee = $this->employee()->loadMissing(['branch:id,name,currency', 'jobTitle:id,name']);
        $year = (int) now()->year;

        // أول مرة في السنة: رصيد افتراضي بنفس قاعدة شاشة الأرصدة
        $leaves->ensureBalances([$employee], $year);

        $balance = EmployeeLeaveBalance::where('employee_id', $employee->id)->where('year', $year)->first();

        $permissionHours = (float) $employee->permissions()
            ->where('status', ApprovalStatus::Approved->value)
            ->inMonth($year, now()->month)
            ->sum('hours_count');

        $pending = [
            'leaves'      => $employee->leaves()->where('status', LeaveStatus::Pending->value)->count(),
            'permissions' => $employee->permissions()->where('status', ApprovalStatus::Pending->value)->count(),
            'advances'    => $employee->advances()->where('status', AdvanceStatus::Pending->value)->count(),
        ];

        $advanceRemaining = (float) $employee->advances()
            ->whereIn('status', [AdvanceStatus::Approved->value])
            ->sum('remaining_amount');

        $latestPayslips = $employee->payrollItems()
            ->where('status', PayrollItemStatus::Paid->value)
            ->with('payroll:id,year,month')
            ->latest('paid_at')
            ->take(3)
            ->get();

        return view('portal.home', [
            'employee'         => $employee,
            'balance'          => $balance,
            'permissionHours'  => $permissionHours,
            'permissionLimit'  => PayrollRules::permissionLimit($employee),
            'pending'          => $pending,
            'advanceRemaining' => $advanceRemaining,
            'latestPayslips'   => $latestPayslips,
        ]);
    }
}
