<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveBalance;
use App\Models\JobTitle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveWorkflowTest extends TestCase
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
            'name'      => 'Main Branch',
            'code'      => 'BR-01',
            'country'   => 'Egypt',
            'currency'  => 'EGP',
            'is_active' => true,
        ]);

        $this->jobTitle = JobTitle::create([
            'name'      => 'Developer',
            'is_active' => true,
        ]);

        $role = \App\Models\Role::create([
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
            'name'                     => 'Ahmed Mohamed',
            'employee_code'            => 'EMP-001',
            'phone'                    => '01000000000',
            'status'                   => 'active',
            'annual_leave_entitlement' => 21.00,
            'basic_salary'             => 5000.00,
            'hire_date'                => '2024-01-01',
        ]);
    }

    public function test_leave_creation_validates_maximum_days_count(): void
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->post(route('admin.leaves.store'), [
            'employee_id' => $this->employee->id,
            'leave_type'  => 'annual',
            'start_date'  => '2026-09-10',
            'end_date'    => '2026-09-12', // 3 days max
            'days_count'  => 5, // Invalid: 5 > 3
            'reason'      => 'Vacation',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('employee_leaves', [
            'employee_id' => $this->employee->id,
            'days_count'  => 5,
        ]);
    }

    public function test_leave_creation_prevents_overlapping(): void
    {
        $this->actingAs($this->admin, 'admin');

        // Existing approved leave: 2026-09-10 to 2026-09-15
        EmployeeLeave::create([
            'employee_id' => $this->employee->id,
            'leave_type'  => 'annual',
            'start_date'  => '2026-09-10',
            'end_date'    => '2026-09-15',
            'days_count'  => 6,
            'status'      => 'approved',
        ]);

        // Attempt overlapping leave: 2026-09-12 to 2026-09-18
        $response = $this->post(route('admin.leaves.store'), [
            'employee_id' => $this->employee->id,
            'leave_type'  => 'annual',
            'start_date'  => '2026-09-12',
            'end_date'    => '2026-09-18',
            'days_count'  => 7,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, EmployeeLeave::where('employee_id', $this->employee->id)->count());
    }

    public function test_leave_approval_rejects_overdraft_and_recalculates_used_on_success(): void
    {
        $this->actingAs($this->admin, 'admin');

        $balance = EmployeeLeaveBalance::create([
            'employee_id'    => $this->employee->id,
            'year'           => 2026,
            'annual_balance' => 5.00,
            'casual_balance' => 7.00,
            'sick_balance'   => 14.00,
            'used_annual'    => 0.00,
            'used_casual'    => 0.00,
            'used_sick'      => 0.00,
            'used_unpaid'    => 0.00,
        ]);

        // Request 10 days (balance only has 5)
        $leaveOver = EmployeeLeave::create([
            'employee_id' => $this->employee->id,
            'leave_type'  => 'annual',
            'start_date'  => '2026-09-01',
            'end_date'    => '2026-09-10',
            'days_count'  => 10.00,
            'status'      => 'pending',
        ]);

        $response = $this->post(route('admin.leaves.approve', $leaveOver->id));
        $response->assertSessionHas('error');
        $this->assertEquals('pending', $leaveOver->fresh()->status);
        $this->assertEquals(0.00, (float) $balance->fresh()->used_annual);

        // Valid leave of 3 days
        $leaveValid = EmployeeLeave::create([
            'employee_id' => $this->employee->id,
            'leave_type'  => 'annual',
            'start_date'  => '2026-10-01',
            'end_date'    => '2026-10-03',
            'days_count'  => 3.00,
            'status'      => 'pending',
        ]);

        $responseValid = $this->post(route('admin.leaves.approve', $leaveValid->id));
        $responseValid->assertSessionHas('success');
        $this->assertEquals('approved', $leaveValid->fresh()->status);
        $this->assertEquals(3.00, (float) $balance->fresh()->used_annual);
        $this->assertEquals(2.00, (float) $balance->fresh()->remaining_annual);
    }
}
