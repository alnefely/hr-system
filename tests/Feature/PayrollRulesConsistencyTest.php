<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\EmployeePenalty;
use App\Models\JobTitle;
use App\Models\Role;
use App\Services\HR\PayrollGenerator;
use App\Services\HR\PayrollRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * المسير والتقارير لازم يحسبوا بنفس القواعد.
 */
class PayrollRulesConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create([
            'name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);
        $jobTitle = JobTitle::create(['name' => 'Engineer', 'is_active' => true]);
        $role = Role::create(['name' => 'Super Admin', 'roles' => all_roles()]);

        $this->actingAs(Admin::create([
            'name' => 'Root', 'email' => 'root@test.com',
            'password' => bcrypt('password'), 'main' => true, 'role_id' => $role->id,
        ]), 'admin');

        // أجر اليوم 100 بالظبط عشان الحسابات تبقى واضحة
        $this->employee = Employee::create([
            'branch_id' => $branch->id, 'job_title_id' => $jobTitle->id,
            'name' => 'Sara', 'employee_code' => 'HQ-0001',
            'phone' => '01500000000', 'status' => 'active',
            'basic_salary' => 3000, 'hire_date' => '2024-01-01',
        ]);
    }

    private function unpaidLeave(string $from, string $to, float $days): EmployeeLeave
    {
        return EmployeeLeave::create([
            'employee_id' => $this->employee->id, 'leave_type' => 'unpaid',
            'start_date' => $from, 'end_date' => $to, 'days_count' => $days,
            'status' => 'approved',
        ]);
    }

    /** إجازة 28/10 → 5/11 (9 أيام): 4 على أكتوبر و5 على نوفمبر. */
    public function test_unpaid_leave_spanning_two_months_is_split(): void
    {
        $this->unpaidLeave('2026-10-28', '2026-11-05', 9);

        $october  = app(PayrollGenerator::class)->generate(2026, 10, $this->employee->branch_id);
        $november = app(PayrollGenerator::class)->generate(2026, 11, $this->employee->branch_id);

        $this->assertEquals(400, (float) $october->items()->first()->deductions, 'أكتوبر');
        $this->assertEquals(500, (float) $november->items()->first()->deductions, 'نوفمبر');
    }

    public function test_leave_inside_single_month_keeps_its_days(): void
    {
        $leave = $this->unpaidLeave('2026-11-10', '2026-11-12', 3);

        $this->assertEquals(3.0, PayrollRules::leaveDaysWithin($leave, '2026-11-01', '2026-11-30'));
        $this->assertEquals(0.0, PayrollRules::leaveDaysWithin($leave, '2026-10-01', '2026-10-31'));
    }

    /**
     * تدقيق الراتب كان بيخصم الجزاءات المعلّقة والمرفوضة، وبيعيد حساب
     * جزاء الأيام من سعر اليوم الحالي بدل المبلغ اللي اتعمد.
     */
    public function test_salary_audit_matches_payroll_penalty_rules(): void
    {
        EmployeePenalty::create([
            'employee_id' => $this->employee->id, 'penalty_type' => 'days_deduction',
            'days_count' => 2, 'deduction_amount' => 150, // المبلغ المعتمد وقت التسجيل
            'penalty_date' => '2026-11-03', 'reason' => 'x', 'status' => 'approved',
        ]);
        EmployeePenalty::create([
            'employee_id' => $this->employee->id, 'penalty_type' => 'amount_deduction',
            'deduction_amount' => 999, 'penalty_date' => '2026-11-04', 'reason' => 'x', 'status' => 'pending',
        ]);
        EmployeePenalty::create([
            'employee_id' => $this->employee->id, 'penalty_type' => 'amount_deduction',
            'deduction_amount' => 888, 'penalty_date' => '2026-11-05', 'reason' => 'x', 'status' => 'rejected',
        ]);

        $payroll = app(PayrollGenerator::class)->generate(2026, 11, $this->employee->branch_id);
        $payrollDeductions = (float) $payroll->items()->first()->deductions;

        $response = $this->get(route('admin.reports.salary-audit', [
            'employee_id' => $this->employee->id,
            'from_date'   => '2026-11-01',
            'to_date'     => '2026-11-30',
        ]))->assertOk();

        $audit = $response->viewData('auditData');

        $this->assertEquals(150, $payrollDeductions);
        $this->assertEquals($payrollDeductions, (float) $audit['total_penalties']);
    }

    /** أجر الساعة واحد في كل مكان، وبيحترم سعر اليوم الخاص. */
    public function test_hourly_rate_respects_custom_daily_rate_everywhere(): void
    {
        $this->employee->update(['custom_daily_rate' => 160, 'daily_work_hours' => 8]);
        $employee = $this->employee->fresh();

        $this->assertEquals(20.0, PayrollRules::hourlyRate($employee));
        $this->assertEquals($employee->hourly_rate, $employee->effective_hourly_rate);
    }
}
