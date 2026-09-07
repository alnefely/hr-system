<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveBalance;
use App\Models\JobTitle;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * اختبارات مقاومة التكرار للعمليات المالية.
 *
 * الحالات دي كانت بق حقيقي في النظام: ضغطة زر مكررة، أو تبويب قديم،
 * أو طلبين متزامنين، كانوا بيخصموا نفس المبلغ مرتين. الاختبارات دي
 * بتستدعي نفس المسار مرتين وبتتأكد إن الأثر حصل مرة واحدة بس.
 */
class FinancialIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Branch $branch;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'HQ', 'code' => 'HQ-01', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $jobTitle = JobTitle::create(['name' => 'Engineer', 'is_active' => true]);
        $role = Role::create(['name' => 'Super Admin', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@test.com',
            'password' => bcrypt('password'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->employee = Employee::create([
            'branch_id' => $this->branch->id, 'job_title_id' => $jobTitle->id,
            'name' => 'Sara Nabil', 'employee_code' => 'EMP-IDM',
            'phone' => '01500000000', 'status' => 'active',
            'basic_salary' => 6000.00, 'annual_leave_entitlement' => 21.00,
            'hire_date' => '2024-01-01',
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    /**
     * اعتماد نفس الإجازة مرتين مايخصمش الرصيد مرتين.
     */
    public function test_approving_same_leave_twice_consumes_balance_once(): void
    {
        $year = 2026;

        EmployeeLeaveBalance::create([
            'employee_id' => $this->employee->id, 'year' => $year,
            'annual_balance' => 21, 'casual_balance' => 7, 'sick_balance' => 14,
            'used_annual' => 0, 'used_casual' => 0, 'used_sick' => 0,
        ]);

        $leave = EmployeeLeave::create([
            'employee_id' => $this->employee->id, 'leave_type' => 'annual',
            'start_date' => "{$year}-06-01", 'end_date' => "{$year}-06-05",
            'days_count' => 5, 'status' => 'pending',
        ]);

        $this->post(route('admin.leaves.approve', $leave->id));
        $this->post(route('admin.leaves.approve', $leave->id));

        $balance = EmployeeLeaveBalance::where('employee_id', $this->employee->id)
            ->where('year', $year)->first();

        $this->assertEquals(
            5.00,
            (float) $balance->used_annual,
            'الرصيد المستهلك اتخصم أكتر من مرة للإجازة الواحدة'
        );
    }

    /**
     * صرف نفس بند الراتب مرتين مايخصمش قسط السلفة مرتين.
     */
    public function test_paying_same_payroll_item_twice_settles_advance_once(): void
    {
        $advance = EmployeeAdvance::create([
            'employee_id' => $this->employee->id,
            'amount' => 1200.00, 'installments_count' => 4,
            'monthly_installment' => 300.00, 'paid_amount' => 0.00,
            'waived_amount' => 0.00, 'remaining_amount' => 1200.00,
            'advance_date' => '2026-09-01', 'start_date' => '2026-09-01',
            'status' => 'approved',
        ]);

        $payroll = Payroll::create([
            'title' => 'Idempotency Payroll', 'year' => 2026, 'month' => 9,
            'branch_id' => $this->branch->id, 'status' => 'approved',
            'created_by' => $this->admin->id,
        ]);

        $item = PayrollItem::create([
            'payroll_id' => $payroll->id, 'employee_id' => $this->employee->id,
            'basic_salary' => 6000.00, 'allowances' => 0.00,
            'allowance_details' => [],
            'deductions' => 300.00,
            'deduction_details' => [
                ['title' => 'قسط سلفة', 'amount' => 300.00, 'advance_id' => $advance->id],
            ],
            'net_salary' => 5700.00, 'status' => 'pending', 'payment_method' => 'cash',
        ]);

        $this->post(route('admin.payrolls.pay-item', $item->id));
        $this->post(route('admin.payrolls.pay-item', $item->id));

        $advance->refresh();

        $this->assertEquals(300.00, (float) $advance->paid_amount, 'قسط السلفة اتخصم مرتين');
        $this->assertEquals(900.00, (float) $advance->remaining_amount, 'المتبقي على السلفة غلط');
        $this->assertEquals(1, $advance->repayments()->count(), 'اتسجل أكتر من سداد لنفس القسط');
    }

    /**
     * السداد الأكبر من المتبقي مرفوض ومابيغيّرش الأرصدة.
     */
    public function test_repayment_exceeding_remaining_is_rejected(): void
    {
        $advance = EmployeeAdvance::create([
            'employee_id' => $this->employee->id,
            'amount' => 1000.00, 'installments_count' => 2,
            'monthly_installment' => 500.00, 'paid_amount' => 0.00,
            'waived_amount' => 0.00, 'remaining_amount' => 1000.00,
            'advance_date' => '2026-09-01', 'start_date' => '2026-09-01',
            'status' => 'approved',
        ]);

        $this->post(route('admin.advances.record-payment', $advance->id), [
            'amount' => 99999.00,
            'repayment_date' => '2026-10-01',
        ]);

        $advance->refresh();

        $this->assertEquals(0.00, (float) $advance->paid_amount, 'اتقبل سداد أكبر من المتبقي');
        $this->assertEquals(1000.00, (float) $advance->remaining_amount);
        $this->assertEquals(0, $advance->repayments()->count());
    }

    /**
     * إصدار مسير مكرر لنفس الشهر والفرع مرفوض.
     */
    public function test_duplicate_payroll_for_same_period_is_rejected(): void
    {
        $payload = [
            'year' => 2026, 'month' => 11, 'branch_id' => $this->branch->id,
        ];

        $this->post(route('admin.payrolls.store'), $payload);
        $this->post(route('admin.payrolls.store'), $payload);

        $this->assertEquals(
            1,
            Payroll::where('year', 2026)->where('month', 11)
                ->where('branch_id', $this->branch->id)->count(),
            'اتعمل أكتر من مسير لنفس الشهر والفرع'
        );
    }
}
