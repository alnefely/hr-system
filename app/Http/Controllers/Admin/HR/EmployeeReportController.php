<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Branch;
use App\Models\EmployeeLeave;
use App\Models\EmployeePenalty;
use App\Models\EmployeePermission;
use App\Models\EmployeeReward;
use App\Models\EmployeeAdvanceRepayment;
use App\Services\Export\ExcelExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployeeReportController extends Controller
{
    public function statement(Request $request)
    {
        $employees = Employee::active()->get(['id', 'name', 'employee_code', 'branch_id', 'basic_salary']);
        $selectedEmployee = null;
        $stats = null;
        $timeline = collect();

        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate   = $request->to_date ?? now()->endOfMonth()->format('Y-m-d');

        if ($request->filled('employee_id')) {
            $selectedEmployee = Employee::with(['branch', 'jobTitle', 'leaveBalances'])->find($request->employee_id);

            if ($selectedEmployee) {
                $leaves = EmployeeLeave::where('employee_id', $selectedEmployee->id)
                    ->whereBetween('start_date', [$fromDate, $toDate])
                    ->get();

                $permissions = EmployeePermission::where('employee_id', $selectedEmployee->id)
                    ->whereBetween('permission_date', [$fromDate, $toDate])
                    ->get();

                $penalties = EmployeePenalty::where('employee_id', $selectedEmployee->id)
                    ->whereBetween('penalty_date', [$fromDate, $toDate])
                    ->get();

                $approvedLeaves  = $leaves->where('status', 'approved');
                $totalAnnualDays = $approvedLeaves->where('leave_type', 'annual')->sum('days_count');
                $totalCasualDays = $approvedLeaves->where('leave_type', 'casual')->sum('days_count');
                $totalSickDays   = $approvedLeaves->where('leave_type', 'sick')->sum('days_count');
                $totalUnpaidDays = $approvedLeaves->where('leave_type', 'unpaid')->sum('days_count');

                $approvedPermissions  = $permissions->where('status', 'approved');
                $totalPermissionHours = $approvedPermissions->sum('hours_count');
                $monthlyLimit         = (float) ($selectedEmployee->monthly_permission_hours_limit ?? 4.00);
                $excessHours          = max(0, $totalPermissionHours - $monthlyLimit);

                $dailyRate  = $selectedEmployee->daily_rate ?: ($selectedEmployee->basic_salary > 0 ? ($selectedEmployee->basic_salary / 30) : 0);
                $hourlyRate = $selectedEmployee->effective_hourly_rate;

                $penaltiesDays   = $penalties->where('penalty_type', 'days_deduction')->sum('days_count');
                $penaltiesAmount = $penalties->where('penalty_type', 'amount_deduction')->sum('deduction_amount');

                $totalPenaltiesCost  = ($penaltiesDays * $dailyRate) + $penaltiesAmount;
                $unpaidLeavesCost    = $totalUnpaidDays * $dailyRate;
                $permissionCost      = $excessHours * $hourlyRate;
                $totalDeductionsCost = round($totalPenaltiesCost + $unpaidLeavesCost + $permissionCost, 2);

                $stats = [
                    'annual_days'           => $totalAnnualDays,
                    'casual_days'           => $totalCasualDays,
                    'sick_days'             => $totalSickDays,
                    'unpaid_days'           => $totalUnpaidDays,
                    'total_days'            => ($totalAnnualDays + $totalCasualDays + $totalSickDays + $totalUnpaidDays),
                    'perm_hours'            => $totalPermissionHours,
                    'excess_hours'          => $excessHours,
                    'hourly_rate'           => $hourlyRate,
                    'penalties_days'        => $penaltiesDays,
                    'penalties_cost'        => $totalPenaltiesCost,
                    'total_deductions_cost' => $totalDeductionsCost,
                ];

                foreach ($leaves as $l) {
                    $timeline->push([
                        'date'   => $l->start_date ? $l->start_date->format('Y-m-d') : '-',
                        'type'   => 'leave',
                        'title'  => 'طلب إجازة (' . (__('leave.type_enums.' . $l->leave_type) ?? $l->leave_type) . ')',
                        'detail' => $l->days_count . ' يوم (' . ($l->start_date ? $l->start_date->format('Y-m-d') : '') . ' إلى ' . ($l->end_date ? $l->end_date->format('Y-m-d') : '') . ')',
                        'reason' => $l->reason ?? '-',
                        'status' => $l->status,
                        'badge'  => $l->status === 'approved' ? 'bg-label-success' : ($l->status === 'rejected' ? 'bg-label-danger' : 'bg-label-warning'),
                        'cost'   => $l->leave_type === 'unpaid' && $l->status === 'approved' ? round($l->days_count * $dailyRate, 2) : 0,
                    ]);
                }

                foreach ($permissions as $p) {
                    $timeline->push([
                        'date'   => $p->permission_date ? $p->permission_date->format('Y-m-d') : '-',
                        'type'   => 'permission',
                        'title'  => 'إذن استئذان قصير',
                        'detail' => $p->hours_count . ' ساعة (' . $p->start_time . ' - ' . $p->end_time . ')',
                        'reason' => $p->reason ?? '-',
                        'status' => $p->status,
                        'badge'  => $p->status === 'approved' ? 'bg-label-info' : ($p->status === 'rejected' ? 'bg-label-danger' : 'bg-label-warning'),
                        'cost'   => 0,
                    ]);
                }

                foreach ($penalties as $pen) {
                    $val = $pen->penalty_type === 'days_deduction' ? ($pen->days_count . ' يوم خصم') : ($pen->penalty_type === 'amount_deduction' ? ($pen->deduction_amount . ' ' . ($selectedEmployee->branch?->currency ?? '')) : 'إنذار كتابي');
                    $cost = $pen->penalty_type === 'days_deduction' ? round($pen->days_count * $dailyRate, 2) : ($pen->penalty_type === 'amount_deduction' ? $pen->deduction_amount : 0);
                    $timeline->push([
                        'date'   => $pen->penalty_date ? $pen->penalty_date->format('Y-m-d') : '-',
                        'type'   => 'penalty',
                        'title'  => 'خصم / جزاء إداري (' . (__('penalty.type_enums.' . $pen->penalty_type) ?? $pen->penalty_type) . ')',
                        'detail' => $val,
                        'reason' => $pen->reason ?? '-',
                        'status' => 'applied',
                        'badge'  => 'bg-label-danger',
                        'cost'   => $cost,
                    ]);
                }

                $timeline = $timeline->sortByDesc('date')->values();
            }
        }

        return view('admin.hr.reports.employee_statement', compact('employees', 'selectedEmployee', 'stats', 'timeline', 'fromDate', 'toDate'));
    }

    /**
     * كشف وسجل مرتب موظف معين بمواقيت معينة (Salary Audit & History Statement)
     */
    public function salaryAudit(Request $request)
    {
        $employees = Employee::active()->with(['branch:id,name,currency', 'jobTitle:id,name'])->get(['id', 'name', 'employee_code', 'branch_id', 'job_title_id', 'basic_salary', 'hire_date']);

        $selectedEmployeeId = $request->get('employee_id');
        $selectedEmployee   = null;
        $auditData          = null;

        // نطاق تواريخ زي تقرير كشف حساب الموظف. الافتراضي الشهر
        // الحالي كامل، فالشاشة بتفتح على نفس اللي كانت بتفتح عليه.
        $startDate = $request->from_date ?: now()->startOfMonth()->format('Y-m-d');
        $endDate   = $request->to_date ?: now()->endOfMonth()->format('Y-m-d');

        // لو المستخدم عكس التواريخ بنصلّحها بدل نتيجة فاضية
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        // بند الراتب الأساسي بيتحسب على شهر واحد، فبناخد شهر
        // البداية للعرض وللحسابات الشهرية.
        $periodStart = Carbon::parse($startDate);
        $month = (int) $periodStart->month;
        $year  = (int) $periodStart->year;

        if ($selectedEmployeeId) {
            $selectedEmployee = Employee::with(['branch', 'jobTitle', 'allowances'])->find($selectedEmployeeId);

            if ($selectedEmployee) {
                $currency = $selectedEmployee->branch?->currency ?? 'EGP';
                $basicSalary = (float) $selectedEmployee->basic_salary;
                $dailyRate = $selectedEmployee->daily_rate ?: ($basicSalary > 0 ? round($basicSalary / 30, 2) : 0);
                $hourlyRate = $selectedEmployee->effective_hourly_rate;

                // 1. Fixed Allowances
                $fixedAllowances = $selectedEmployee->allowances ?? collect();
                $totalFixedAllowances = (float) $fixedAllowances->sum('amount');

                // 2. Rewards & Bonuses in this month
                $rewards = EmployeeReward::where('employee_id', $selectedEmployee->id)
                    ->whereIn('status', ['approved', 'paid'])
                    ->whereBetween('reward_date', [$startDate, $endDate])
                    ->get();
                $totalRewards = (float) $rewards->sum('amount');

                // 3. Penalties & Deductions in this month
                $penalties = EmployeePenalty::where('employee_id', $selectedEmployee->id)
                    ->whereBetween('penalty_date', [$startDate, $endDate])
                    ->get();

                $penaltyDaysCount = (float) $penalties->where('penalty_type', 'days_deduction')->sum('days_count');
                $penaltyDaysCost  = round($penaltyDaysCount * $dailyRate, 2);
                $penaltyAmountCost = (float) $penalties->where('penalty_type', 'amount_deduction')->sum('deduction_amount');
                $totalPenalties = round($penaltyDaysCost + $penaltyAmountCost, 2);

                // 4. Excess Permission Hours
                $permissions = EmployeePermission::where('employee_id', $selectedEmployee->id)
                    ->where('status', 'approved')
                    ->whereBetween('permission_date', [$startDate, $endDate])
                    ->get();
                $totalPermHours = (float) $permissions->sum('hours_count');
                $monthlyPermLimit = (float) ($selectedEmployee->monthly_permission_hours_limit ?? 4.00);
                $excessPermHours = max(0, $totalPermHours - $monthlyPermLimit);
                $permExcessCost = round($excessPermHours * $hourlyRate, 2);

                // 5. Unpaid Leaves
                $unpaidLeaves = EmployeeLeave::where('employee_id', $selectedEmployee->id)
                    ->where('status', 'approved')
                    ->where('leave_type', 'unpaid')
                    ->whereBetween('start_date', [$startDate, $endDate])
                    ->get();
                $unpaidLeaveDays = (float) $unpaidLeaves->sum('days_count');
                $unpaidLeaveCost = round($unpaidLeaveDays * $dailyRate, 2);

                // 6. Advance Repayments in this month
                $advanceRepayments = EmployeeAdvanceRepayment::whereHas('advance', function($q) use ($selectedEmployee) {
                        $q->where('employee_id', $selectedEmployee->id);
                    })
                    ->whereBetween('repayment_date', [$startDate, $endDate])
                    ->get();
                $totalAdvanceRepayments = (float) $advanceRepayments->sum('amount');

                // Financial Summary
                $totalAdditions   = round($totalFixedAllowances + $totalRewards, 2);
                $totalDeductions  = round($totalPenalties + $permExcessCost + $unpaidLeaveCost + $totalAdvanceRepayments, 2);
                $grossSalary      = round($basicSalary + $totalAdditions, 2);
                $netEstimatedPay  = round($grossSalary - $totalDeductions, 2);

                // Chronological Breakdown entries
                $breakdown = collect();

                // Base & Fixed allowances
                $breakdown->push([
                    'date'     => $startDate,
                    'type'     => 'base',
                    'category' => 'استحقاق أساسي',
                    'title'    => 'الراتب الأساسي المعتمد',
                    'amount'   => $basicSalary,
                    'direction'=> 'plus',
                    'notes'    => 'راتب العقد الثابت',
                ]);

                foreach ($fixedAllowances as $fa) {
                    $breakdown->push([
                        'date'     => $startDate,
                        'type'     => 'fixed_allowance',
                        'category' => 'بدل ثابت',
                        'title'    => 'بدل: ' . $fa->name,
                        'amount'   => (float) $fa->amount,
                        'direction'=> 'plus',
                        'notes'    => 'ضمن البدلات الشهرية الثابتة',
                    ]);
                }

                // Rewards
                foreach ($rewards as $r) {
                    $lbl = __('reward.type_enums.' . $r->reward_type) ?? $r->reward_type;
                    $breakdown->push([
                        'date'     => $r->reward_date ? $r->reward_date->format('Y-m-d') : $startDate,
                        'type'     => 'reward',
                        'category' => 'مكافأة / حافز إضافي',
                        'title'    => $r->title . ' (' . $lbl . ')',
                        'amount'   => (float) $r->amount,
                        'direction'=> 'plus',
                        'notes'    => $r->reason ?? 'مكافأة معتمدة',
                    ]);
                }

                // Penalties
                foreach ($penalties as $pen) {
                    $pCost = $pen->penalty_type === 'days_deduction' ? round($pen->days_count * $dailyRate, 2) : $pen->deduction_amount;
                    if ($pCost > 0) {
                        $pDesc = $pen->penalty_type === 'days_deduction' ? ('خصم ' . $pen->days_count . ' يوم غياب/تأخير') : 'خصم مالي إداري مباشر';
                        $breakdown->push([
                            'date'     => $pen->penalty_date ? $pen->penalty_date->format('Y-m-d') : $startDate,
                            'type'     => 'penalty',
                            'category' => 'خصم وجزاء إداري',
                            'title'    => $pDesc,
                            'amount'   => (float) $pCost,
                            'direction'=> 'minus',
                            'notes'    => $pen->reason ?? 'جزاء إداري',
                        ]);
                    }
                }

                // Advance repayments
                foreach ($advanceRepayments as $advRep) {
                    $breakdown->push([
                        'date'     => $advRep->repayment_date ? $advRep->repayment_date->format('Y-m-d') : $startDate,
                        'type'     => 'advance_repayment',
                        'category' => 'سداد قسط سلفة',
                        'title'    => 'سداد قسط سلفة مالية (' . ($advRep->type === 'payroll_deduction' ? 'خصم مسير' : 'سداد نقدي') . ')',
                        'amount'   => (float) $advRep->amount,
                        'direction'=> 'minus',
                        'notes'    => $advRep->notes ?? 'خصم استرداد سلفة',
                    ]);
                }

                // Unpaid leave
                if ($unpaidLeaveCost > 0) {
                    $breakdown->push([
                        'date'     => $endDate,
                        'type'     => 'unpaid_leave',
                        'category' => 'إجازة بدون راتب',
                        'title'    => 'خصم إجازات غير مدفوعة (' . $unpaidLeaveDays . ' يوم)',
                        'amount'   => (float) $unpaidLeaveCost,
                        'direction'=> 'minus',
                        'notes'    => 'خصم أيام غير مدفوعة من الراتب',
                    ]);
                }

                // Perm excess
                if ($permExcessCost > 0) {
                    $breakdown->push([
                        'date'     => $endDate,
                        'type'     => 'permission_excess',
                        'category' => 'تجاوز ساعات الاستئذان',
                        'title'    => 'خصم تجاوز حد الاستئذان (' . $excessPermHours . ' ساعة زائدة)',
                        'amount'   => (float) $permExcessCost,
                        'direction'=> 'minus',
                        'notes'    => 'تجاوز الحد الشهري المسموح به (' . $monthlyPermLimit . ' ساعة)',
                    ]);
                }

                $breakdown = $breakdown->sortBy('date')->values();

                $auditData = [
                    'basic_salary'          => $basicSalary,
                    'daily_rate'            => $dailyRate,
                    'hourly_rate'           => $hourlyRate,
                    'fixed_allowances'      => $fixedAllowances,
                    'total_fixed_allowances'=> $totalFixedAllowances,
                    'rewards'               => $rewards,
                    'total_rewards'         => $totalRewards,
                    'total_additions'       => $totalAdditions,
                    'penalties'             => $penalties,
                    'total_penalties'       => $totalPenalties,
                    'excess_perm_hours'     => $excessPermHours,
                    'perm_excess_cost'      => $permExcessCost,
                    'unpaid_leave_days'     => $unpaidLeaveDays,
                    'unpaid_leave_cost'     => $unpaidLeaveCost,
                    'total_advances_repaid' => $totalAdvanceRepayments,
                    'total_deductions'      => $totalDeductions,
                    'gross_salary'          => $grossSalary,
                    'net_estimated_pay'     => $netEstimatedPay,
                    'currency'              => $currency,
                    'breakdown'             => $breakdown,
                    'startDate'             => $startDate,
                    'endDate'               => $endDate,
                ];

                // Check for Excel Export
                if ($request->get('export') === 'excel') {
                    $headers = ['م', 'التاريخ', 'التصنيف', 'البيان / المعاملة', 'المعاملة (+/-)', 'المبلغ', 'العملة', 'ملاحظات وتفاصيل'];
                    $rows = [];
                    $i = 1;
                    foreach ($breakdown as $item) {
                        $dirSymbol = $item['direction'] === 'plus' ? '(+) إضافة' : '(-) خصم';
                        $rows[] = [
                            $i++,
                            $item['date'],
                            $item['category'],
                            $item['title'],
                            $dirSymbol,
                            number_format($item['amount'], 2),
                            $currency,
                            $item['notes'],
                        ];
                    }

                    $totals = [
                        'الصافي التقديري',
                        '',
                        '',
                        'إجمالي الإضافات: ' . number_format($totalAdditions, 2) . ' | إجمالي الخصومات: ' . number_format($totalDeductions, 2),
                        'الصافي المستحق:',
                        number_format($netEstimatedPay, 2),
                        $currency,
                        'تم الاعتماد بنجاح',
                    ];

                    $filename = 'كشف_راتب_' . $selectedEmployee->employee_code . '_' . $year . '_' . $month;
                    $title = 'كشف وسجل راتب الموظف: ' . $selectedEmployee->name . ' لشهر ' . $month . ' / ' . $year;
                    $meta = [
                        'كود الموظف' => $selectedEmployee->employee_code,
                        'الفرع'      => $selectedEmployee->branch?->name ?? '-',
                        'المسمى'     => $selectedEmployee->jobTitle?->name ?? '-',
                        'الراتب الأساسي' => number_format($basicSalary, 2) . ' ' . $currency,
                    ];

                    return ExcelExportService::download($title, $headers, $rows, $totals, $filename, $meta);
                }
            }
        }

        return view('admin.hr.reports.salary_audit', compact(
            'employees',
            'selectedEmployee',
            'selectedEmployeeId',
            'month',
            'year',
            'startDate',
            'endDate',
            'auditData'
        ));
    }

    /**
     * متابعة مستندات الموظفين: اللي قرب ينتهي واللي قيد المراجعة.
     *
     * المستندات كانت متفرّقة على ملفات الموظفين، فمفيش مكان واحد
     * يقول لك إيه اللي محتاج تجديد أو اعتماد.
     */
    public function documents(Request $request)
    {
        $days = (int) $request->input('days', EmployeeDocument::EXPIRY_ALERT_DAYS);
        $days = max(1, min($days, 365));

        $status   = $request->input('status', 'attention');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;

        $base = fn () => EmployeeDocument::query()
            ->with(['employee:id,name,employee_code,branch_id', 'employee.branch:id,name', 'verifier:id,name'])
            ->when($branchId, fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', $branchId)));

        // العدّادات بتتحسب قبل الفلترة عشان تفضل ثابتة
        $counts = [
            'expiring' => $base()->expiringSoon($days)->count(),
            'expired'  => $base()->expired()->count(),
            'pending'  => $base()->pendingVerification()->count(),
        ];

        $query = $base();

        match ($status) {
            'expiring' => $query->expiringSoon($days),
            'expired'  => $query->expired(),
            'pending'  => $query->pendingVerification(),
            // الافتراضي: كل اللي محتاج انتباه
            default    => $query->where(function ($q) use ($days) {
                $q->expiringSoon($days)
                    ->orWhere(fn ($x) => $x->expired())
                    ->orWhere(fn ($x) => $x->pendingVerification());
            }),
        };

        $documents = $query
            ->orderByRaw('expiry_date IS NULL')
            ->orderBy('expiry_date')
            ->paginate(20)
            ->withQueryString();

        $branches = Branch::where('is_active', true)->get(['id', 'name']);

        return view('admin.hr.reports.documents', compact(
            'documents',
            'counts',
            'branches',
            'days',
            'status',
            'branchId'
        ));
    }
}
