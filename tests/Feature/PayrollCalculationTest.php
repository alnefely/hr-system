<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeReward;
use App\Models\JobTitle;
use App\Models\Payroll;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Branch $branch;
    protected JobTitle $jobTitle;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name'      => 'Cairo Branch',
            'code'      => 'CAI-01',
            'country'   => 'Egypt',
            'currency'  => 'EGP',
            'is_active' => true,
        ]);

        $this->jobTitle = JobTitle::create([
            'name'      => 'Accountant',
            'is_active' => true,
        ]);

        $role = Role::create([
            'name'  => 'Super Admin Role',
            'roles' => all_roles(),
        ]);

        $this->admin = Admin::create([
            'name'     => 'Super Admin',
            'email'    => 'admin@test.com',
            'password' => bcrypt('password'),
            'main'     => true,
            'role_id'  => $role->id,
            'status'   => 'active',
        ]);

        $this->employee = Employee::create([
            'branch_id'                => $this->branch->id,
            'job_title_id'             => $this->jobTitle->id,
            'name'                     => 'Kareem Tarek',
            'employee_code'            => 'EMP-003',
            'phone'                    => '01200000000',
            'status'                   => 'active',
            'annual_leave_entitlement' => 21.00,
            'basic_salary'             => 5000.00,
            'hire_date'                => '2024-01-01',
        ]);
    }

    public function test_payroll_generation_and_net_calculation(): void
    {
        $this->actingAs($this->admin, 'admin');

        // Add an approved reward of 500 in 2026-09
        EmployeeReward::create([
            'title'       => 'Performance Bonus',
            'employee_id' => $this->employee->id,
            'reward_type' => 'bonus',
            'amount'      => 500.00,
            'reward_date' => '2026-09-15',
            'status'      => 'approved',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        // Add an approved advance with 300 monthly installment starting in 2026-09
        EmployeeAdvance::create([
            'employee_id'         => $this->employee->id,
            'amount'              => 900.00,
            'installments_count'  => 3,
            'monthly_installment' => 300.00,
            'paid_amount'         => 0.00,
            'waived_amount'       => 0.00,
            'remaining_amount'    => 900.00,
            'advance_date'        => '2026-09-01',
            'start_date'          => '2026-09-01',
            'status'              => 'approved',
            'approved_by'         => $this->admin->id,
            'approved_at'         => now(),
        ]);

        // Generate payroll for month 9, year 2026
        $response = $this->post(route('admin.payrolls.store'), [
            'year'      => 2026,
            'month'     => 9,
            'branch_id' => $this->branch->id,
        ]);

        $response->assertSessionHas('success');
        $payroll = Payroll::where('branch_id', $this->branch->id)->where('year', 2026)->where('month', 9)->first();
        $this->assertNotNull($payroll);

        // Verify totals:
        // Basic: 5000
        // Allowances/Rewards: 500
        // Deductions/Advances: 300
        // Net: 5000 + 500 - 300 = 5200
        $this->assertEquals(5000.00, (float) $payroll->total_basic_salary);
        $this->assertEquals(500.00, (float) $payroll->total_allowances);
        $this->assertEquals(300.00, (float) $payroll->total_deductions);
        $this->assertEquals(5200.00, (float) $payroll->total_net_salary);
        $this->assertEquals('draft', $payroll->status);
    }

    public function test_payroll_approval_and_disbursal_workflow(): void
    {
        $this->actingAs($this->admin, 'admin');

        $payroll = Payroll::create([
            'title'              => 'Payroll Test',
            'year'               => 2026,
            'month'              => 8,
            'branch_id'          => $this->branch->id,
            'status'             => 'draft',
            'total_basic_salary' => 5000.00,
            'total_allowances'   => 0.00,
            'total_deductions'   => 0.00,
            'total_net_salary'   => 5000.00,
            'created_by'         => $this->admin->id,
        ]);

        // 1. Approve payroll
        $responseApprove = $this->post(route('admin.payrolls.approve', $payroll->id));
        $responseApprove->assertSessionHas('success');
        $this->assertEquals('approved', $payroll->fresh()->status);
        $this->assertNotNull($payroll->fresh()->approved_at);

        // 2. Pay payroll
        $responsePay = $this->post(route('admin.payrolls.pay', $payroll->id), [
            'payment_method' => 'bank_transfer',
        ]);
        $responsePay->assertSessionHas('success');
        $this->assertEquals('paid', $payroll->fresh()->status);
        $this->assertNotNull($payroll->fresh()->paid_at);
    }
}
