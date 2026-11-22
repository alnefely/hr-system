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

            $employees = $this->targetEmployees($branchId, $year, $month);

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

        foreach ($this->branchesWithActiveEmployees($year, $month) as $branch) {
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
    public function branchesWithActiveEmployees(?int $year = null, ?int $month = null): Collection
    {
        // نفس معيار الموظفين المستحقين اللي بيتبني بيه الكشف
        $branchIds = ($year && $month
                ? $this->eligibleEmployeesQuery($year, $month)
                : Employee::active())
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

    /**
     * الموظفين المستحقين لراتب الشهر.
     *
     * قبل كده كان "active" بس: اللي حالته "في إجازة" ماكانش بياخد راتب
     * خالص، واللي اتعيّن بعد الشهر كان بياخد الشهر كامل. دلوقتي أي حد
     * فترة خدمته بتتقاطع مع الشهر — والمنتهية خدمته جوّه الشهر بياخد
     * الأيام اللي اشتغلها (التناسب في buildItemRow).
     */
    private function targetEmployees(?int $branchId, int $year, int $month): Collection
    {
        return $this->eligibleEmployeesQuery($year, $month)
            ->with('allowances')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();
    }

    private function eligibleEmployeesQuery(int $year, int $month)
    {
        [$monthStart, $monthEnd] = $this->monthRange($year, $month);

        return Employee::query()
            ->where(function ($q) use ($monthStart) {
                $q->whereIn('status', ['active', 'on_leave'])
                    ->orWhere(function ($q) use ($monthStart) {
                        $q->where('status', 'terminated')
                            ->whereNotNull('termination_date')
                            ->where('termination_date', '>=', $monthStart);
                    });
            })
            ->where(fn ($q) => $q->whereNull('hire_date')->orWhere('hire_date', '<=', $monthEnd));
    }

    /** @return array{0: string, 1: string} أول وآخر يوم في الشهر (Y-m-d) */
    private function monthRange(int $year, int $month): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);

        return [$start, date('Y-m-t', strtotime($start))];
    }

    /**
     * نسبة أيام الخدمة من الشهر (1 = شهر كامل).
     *
     * المعيّن في نص الشهر أو المنتهية خدمته قبل آخره بياخد الأيام
     * اللي اشتغلها بس.
     */
    private function serviceRatio(Employee $employee, int $year, int $month): float
    {
        [$monthStart, $monthEnd] = $this->monthRange($year, $month);

        $from = $monthStart;
        $to   = $monthEnd;

        $hire = $employee->getRawOriginal('hire_date');
        if ($hire && substr($hire, 0, 10) > $from) {
            $from = substr($hire, 0, 10);
        }

        $end = $employee->getRawOriginal('termination_date');
        if ($employee->status === 'terminated' && $end && substr($end, 0, 10) < $to) {
            $to = substr($end, 0, 10);
        }

        if ($from === $monthStart && $to === $monthEnd) {
            return 1.0;
        }

        $daysInMonth = (int) date('t', strtotime($monthStart));
        $worked      = (int) ((strtotime($to) - strtotime($from)) / 86400) + 1;

        return max(0, min(1, $worked / $daysInMonth));
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

            // الراتب الساري في آخر يوم من الشهر من سجل الرواتب — العلاوة
            // المجدولة لأول الشهر بتتطبق حتى لو المزامنة اليومية ماتعملتش.
            'effectiveSalaries' => $this->effectiveSalaries($employeeIds, $lastDayOfMonth),

            // الأقساط المحجوزة في مسيرات تانية لسه ماتصرفتش
            'reservedInstallments' => PayrollRules::reservedAdvanceInstallments($employeeIds),
        ];
    }

    /**
     * @param  array<int, int>  $employeeIds
     * @return array<int, float>  employee_id => الراتب الأساسي الساري
     */
    private function effectiveSalaries(array $employeeIds, string $asOf): array
    {
        $salaries = [];

        \App\Models\EmployeeSalary::withoutGlobalScope('branch')
            ->whereIn('employee_id', $employeeIds)
            ->where('effective_date', '<=', $asOf)
            ->orderBy('effective_date')
            ->orderBy('id')
            ->get(['employee_id', 'basic_salary'])
            ->each(function ($row) use (&$salaries) {
                // الترتيب تصاعدي، فآخر قيمة لكل موظف هي الأحدث
                $salaries[$row->employee_id] = (float) $row->basic_salary;
            });

        return $salaries;
    }

    private function buildItemRow(Payroll $payroll, Employee $employee, array $inputs, $now): array
    {
        if (isset($inputs['effectiveSalaries'][$employee->id])) {
            // أجر اليوم والساعة بيتحسبوا من الراتب ده كمان
            $employee->basic_salary = $inputs['effectiveSalaries'][$employee->id];
        }

        $ratio       = $this->serviceRatio($employee, (int) $payroll->year, (int) $payroll->month);
        $basicSalary = round((float) ($employee->basic_salary ?? 0) * $ratio, 2);

        $dailyRate  = PayrollRules::dailyRate($employee);
        $hourlyRate = PayrollRules::hourlyRate($employee);

        [$allowanceDetails, $totalAllowances] = $this->buildAllowances($employee, $inputs + ['serviceRatio' => $ratio]);
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
        $ratio = $inputs['serviceRatio'] ?? 1.0;

        foreach ($employee->allowances as $allowance) {
            $amount = round((float) $allowance->amount * $ratio, 2);
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
            // المتبقي الفعلي = الرصيد ناقص اللي محجوز في مسير مفتوح تاني
            $available   = (float) $advance->remaining_amount - (float) ($inputs['reservedInstallments'][$advance->id] ?? 0);
            $installment = round(min($available, (float) $advance->monthly_installment), 2);

            // كسور التقريب (1000 ÷ 3 = 333.33) كانت بتسيب 0.01 تتخصم في شهر
            // رابع. لو الباقي بعد القسط ده كسور بس، القسط ده بياخده كله.
            if ($available - $installment > 0 && $available - $installment <= 0.01 * max(1, (int) $advance->installments_count)) {
                $installment = round($available, 2);
            }

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
