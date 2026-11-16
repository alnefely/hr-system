<?php

namespace App\Services\HR;

use App\Enums\ApprovalStatus;
use App\Enums\AdvanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\PayrollItemStatus;
use App\Enums\PayrollStatus;
use App\Enums\RewardStatus;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLeave;
use App\Models\EmployeePenalty;
use App\Models\EmployeePermission;
use App\Models\EmployeeReward;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * توليد مسير الرواتب.
 *
 * المنطق ده كان جوّه PayrollController@store (حوالي 160 سطر)، وده كان
 * بيخليه صعب الاختبار وصعب الاستدعاء من أي مكان تاني. دلوقتي بقى خدمة
 * مستقلة ينفع تتنادى من الكنترولر أو من أمر artisan أو من job.
 *
 * ملاحظة أداء: كل مدخلات المسير بتتجاب في استعلام واحد لكل نوع
 * (5 استعلامات ثابتة) مهما كان عدد الموظفين، والبنود بتتكتب بـ
 * bulk insert على دفعات — مش صف صف.
 */
class PayrollGenerator
{
    /** حجم دفعة الإدخال المجمّع. */
    private const INSERT_CHUNK = 200;

    /**
     * أنشئ مسير رواتب لشهر وفرع محددين.
     *
     * @throws \Illuminate\Database\QueryException لو المسير موجود (unique index)
     */
    public function generate(int $year, int $month, ?int $branchId = null, ?string $notes = null, ?int $createdBy = null): Payroll
    {
        return DB::transaction(function () use ($year, $month, $branchId, $notes, $createdBy) {
            $payroll = Payroll::create([
                'title'      => $this->buildTitle($year, $month, $branchId),
                'year'       => $year,
                'month'      => $month,
                'branch_id'  => $branchId,
                'status'     => PayrollStatus::Draft->value,
                'created_by' => $createdBy ?? admin()?->id,
                'notes'      => $notes,
            ]);

            $employees = $this->targetEmployees($branchId);

            if ($employees->isEmpty()) {
                return $payroll;
            }

            $inputs = $this->collectInputs($employees->pluck('id')->all(), $year, $month);
            $rows = [];
            $now = now();

            foreach ($employees as $employee) {
                $rows[] = $this->buildItemRow($payroll, $employee, $inputs, $now);
            }

            foreach (array_chunk($rows, self::INSERT_CHUNK) as $chunk) {
                PayrollItem::insert($chunk);
            }

            $payroll->recalculateTotals();

            return $payroll->refresh();
        });
    }

    /**
     * أنشئ كشف مستقل لكل فرع فيه موظفون نشطون.
     *
     * كشف واحد يجمع كل الفروع بيبقى غير قابل للفلترة ولا للتقارير
     * الفرعية، وكل فرع ليه عملته وخزينته — فالتجميع بيدي رقم
     * مالوش معنى محاسبي.
     *
     * @return array{created: list<Payroll>, skipped: list<string>}
     */
    public function generateForAllBranches(int $year, int $month, ?string $notes = null, ?int $createdBy = null): array
    {
        $created = [];
        $skipped = [];

        foreach ($this->branchesWithActiveEmployees() as $branch) {
            if ($this->exists($year, $month, $branch->id)) {
                $skipped[] = $branch->name;

                continue;
            }

            $created[] = $this->generate($year, $month, $branch->id, $notes, $createdBy);
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * الفروع اللي فيها موظفون نشطون.
     *
     * الفرع الفاضي مالوش كشف — كشف من غير بنود بيلخبط العدّادات.
     *
     * @return Collection<int, Branch>
     */
    public function branchesWithActiveEmployees(): Collection
    {
        $branchIds = Employee::active()
            ->whereNotNull('branch_id')
            ->distinct()
            ->pluck('branch_id');

        return Branch::whereIn('id', $branchIds)->orderBy('name')->get(['id', 'name']);
    }

    /** هل في مسير مسجّل بالفعل لنفس الشهر والفرع؟ */
    public function exists(int $year, int $month, ?int $branchId): bool
    {
        return Payroll::where('year', $year)
            ->where('month', $month)
            ->where('branch_id', $branchId)
            ->exists();
    }

    // ========================================================
    //  الداخلي
    // ========================================================

    private function targetEmployees(?int $branchId): Collection
    {
        return Employee::active()
            ->with('allowances')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();
    }

    /**
     * كل مدخلات المسير في 5 استعلامات مجمّعة.
     *
     * @param array<int, int> $employeeIds
     */
    private function collectInputs(array $employeeIds, int $year, int $month): array
    {
        $lastDayOfMonth = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $month)));
        [$monthStart, $monthEnd] = EmployeeLeave::monthBounds($year, $month);

        return [
            'rewards' => EmployeeReward::whereIn('employee_id', $employeeIds)
                ->where('status', RewardStatus::Approved->value)
                ->inMonth($year, $month)
                ->get()
                ->groupBy('employee_id'),

            // المعتمد بس: الجزاء خصم من الراتب فلازم يعدّي دورة الاعتماد
            // قبل ما يدخل المسير — زي المكافآت بالظبط.
            'penalties' => EmployeePenalty::whereIn('employee_id', $employeeIds)
                ->whereIn('status', PayrollRules::DEDUCTIBLE_PENALTY_STATUSES)
                ->inMonth($year, $month)
                ->get()
                ->groupBy('employee_id'),

            // الإجازة اللي بتعدّي الشهر بتتوزّع على الشهرين بدل ما تتخصم كلها
            // في شهر البداية — عشان كده بنجيب كل اللي بيتقاطع مع الشهر.
            'unpaidLeaveDays' => PayrollRules::overlapping(
                    EmployeeLeave::whereIn('employee_id', $employeeIds)
                        ->where('status', LeaveStatus::Approved->value)
                        ->where('leave_type', 'unpaid'),
                    $monthStart,
                    $monthEnd
                )
                ->get(['id', 'employee_id', 'start_date', 'end_date', 'days_count'])
                ->groupBy('employee_id')
                ->map(fn ($leaves) => round($leaves->sum(
                    fn (EmployeeLeave $leave) => PayrollRules::leaveDaysWithin($leave, $monthStart, $monthEnd)
                ), 2)),

            'permissionHours' => EmployeePermission::whereIn('employee_id', $employeeIds)
                ->where('status', ApprovalStatus::Approved->value)
                ->inMonth($year, $month)
                ->groupBy('employee_id')
                ->selectRaw('employee_id, SUM(hours_count) as total_hours')
                ->pluck('total_hours', 'employee_id'),

            'advances' => EmployeeAdvance::whereIn('employee_id', $employeeIds)
                ->where('status', AdvanceStatus::Approved->value)
                ->where('remaining_amount', '>', 0)
                ->where('start_date', '<=', $lastDayOfMonth)
                ->get()
                ->groupBy('employee_id'),
        ];
    }

    private function buildItemRow(Payroll $payroll, Employee $employee, array $inputs, $now): array
    {
        $basicSalary = round((float) ($employee->basic_salary ?? 0), 2);

        $dailyRate  = PayrollRules::dailyRate($employee);
        $hourlyRate = PayrollRules::hourlyRate($employee);

        [$allowanceDetails, $totalAllowances] = $this->buildAllowances($employee, $inputs);
        [$deductionDetails, $totalDeductions] = $this->buildDeductions($employee, $inputs, $dailyRate, $hourlyRate);

        return [
            'payroll_id'        => $payroll->id,
            'employee_id'       => $employee->id,
            'basic_salary'      => $basicSalary,
            'allowance_details' => json_encode($allowanceDetails, JSON_UNESCAPED_UNICODE),
            'allowances'        => $totalAllowances,
            'deductions'        => $totalDeductions,
            'deduction_details' => json_encode($deductionDetails, JSON_UNESCAPED_UNICODE),
            'net_salary'        => max(0, round($basicSalary + $totalAllowances - $totalDeductions, 2)),
            'status'            => PayrollItemStatus::Pending->value,
            'payment_method'    => !empty($employee->bank_account_number) ? 'bank_transfer' : 'cash',
            'created_at'        => $now,
            'updated_at'        => $now,
        ];
    }

    /** @return array{0: array, 1: float} */
    private function buildAllowances(Employee $employee, array $inputs): array
    {
        $details = [];
        $total = 0.0;

        // البدلات الثابتة التعاقدية
        foreach ($employee->allowances as $allowance) {
            $amount = round((float) $allowance->amount, 2);
            $total += $amount;
            $details[] = [
                'title'  => $allowance->name,
                'amount' => $amount,
                'type'   => 'fixed',
            ];
        }

        // المكافآت المعتمدة في الشهر المستهدف
        foreach ($inputs['rewards']->get($employee->id, collect()) as $reward) {
            $amount = round((float) $reward->amount, 2);
            $total += $amount;
            $details[] = [
                'title'     => __('payroll.generated.reward', ['title' => $reward->title ?? __('reward.singular')]),
                'amount'    => $amount,
                'reward_id' => $reward->id,
                'type'      => 'reward',
            ];
        }

        return [$details, round($total, 2)];
    }

    /** @return array{0: array, 1: float} */
    private function buildDeductions(Employee $employee, array $inputs, float $dailyRate, float $hourlyRate): array
    {
        $details = [];
        $total = 0.0;

        // 1. الجزاءات الإدارية
        foreach ($inputs['penalties']->get($employee->id, collect()) as $penalty) {
            $cost = PayrollRules::penaltyCost($penalty, $dailyRate);

            if ($penalty->penalty_type === 'days_deduction') {
                $title = __('payroll.generated.penalty_days', [
                    'days'   => $penalty->days_count,
                    'reason' => $penalty->reason ?? __('payroll.generated.violation'),
                ]);
            } elseif ($penalty->penalty_type === 'amount_deduction') {
                $title = __('payroll.generated.penalty_amount', [
                    'reason' => $penalty->reason ?? __('payroll.generated.financial_deduction'),
                ]);
            } else {
                continue;
            }

            $total += $cost;
            $details[] = ['title' => $title, 'amount' => $cost];
        }

        // 2. الإجازات بدون أجر
        $unpaidDays = (float) ($inputs['unpaidLeaveDays'][$employee->id] ?? 0);
        $unpaidDeduction = round($unpaidDays * $dailyRate, 2);

        if ($unpaidDeduction > 0) {
            $total += $unpaidDeduction;
            $details[] = [
                'title'  => __('payroll.generated.unpaid_leave', ['days' => $unpaidDays]),
                'amount' => $unpaidDeduction,
            ];
        }

        // 3. ساعات الاستئذان الزائدة عن الحد الشهري
        $limit = PayrollRules::permissionLimit($employee);
        $approvedHours = (float) ($inputs['permissionHours'][$employee->id] ?? 0);
        $excessHours = max(0, $approvedHours - $limit);
        $permissionDeduction = round($excessHours * $hourlyRate, 2);

        if ($permissionDeduction > 0) {
            $total += $permissionDeduction;
            $details[] = [
                'title'  => __('payroll.generated.excess_permission', ['hours' => $excessHours]),
                'amount' => $permissionDeduction,
            ];
        }

        // 4. أقساط السلف
        foreach ($inputs['advances']->get($employee->id, collect()) as $advance) {
            $installment = round(min((float) $advance->remaining_amount, (float) $advance->monthly_installment), 2);

            if ($installment <= 0) {
                continue;
            }

            $total += $installment;
            $details[] = [
                'title'      => __('payroll.generated.advance_installment', ['amount' => number_format($installment, 2)]),
                'amount'     => $installment,
                'advance_id' => $advance->id,
            ];
        }

        return [$details, round($total, 2)];
    }

    private function buildTitle(int $year, int $month, ?int $branchId): string
    {
        $branchName = $branchId
            ? (\App\Models\Branch::find($branchId)?->name ?? '')
            : __('payroll.fields.all_branches');

        return __('payroll.generated.title', [
            'month'  => $month,
            'year'   => $year,
            'branch' => $branchName,
        ]);
    }
}
