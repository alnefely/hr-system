<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\JobTitle;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvanceWorkflowTest extends TestCase
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
            'name'                     => 'Mahmoud Ali',
            'employee_code'            => 'EMP-002',
            'phone'                    => '01100000000',
            'status'                   => 'active',
            'annual_leave_entitlement' => 21.00,
            'basic_salary'             => 6000.00,
            'hire_date'                => '2024-01-01',
        ]);
    }

    public function test_advance_creation_and_installment_calculation(): void
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->post(route('admin.advances.store'), [
            'employee_id'        => $this->employee->id,
            'amount'             => 3000.00,
            'installments_count' => 3,
            'advance_date'       => '2026-09-01',
            'start_date'         => '2026-10-01',
            'reason'             => 'Personal emergency',
        ]);

        $response->assertSessionHas('success');
        $advance = EmployeeAdvance::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($advance);
        $this->assertEquals(3000.00, (float) $advance->amount);
        $this->assertEquals(1000.00, (float) $advance->monthly_installment);
        $this->assertEquals(3000.00, (float) $advance->remaining_amount);
        $this->assertEquals('pending', $advance->status);
    }

    public function test_record_payment_and_waiver_with_state_transition(): void
    {
        $this->actingAs($this->admin, 'admin');

        $advance = EmployeeAdvance::create([
            'employee_id'         => $this->employee->id,
            'amount'              => 2000.00,
            'installments_count'  => 2,
            'monthly_installment' => 1000.00,
            'paid_amount'         => 0.00,
            'waived_amount'       => 0.00,
            'remaining_amount'    => 2000.00,
            'advance_date'        => '2026-09-01',
            'start_date'          => '2026-10-01',
            'status'              => 'approved',
        ]);

        // Record repayment of 1000
        $responsePayment = $this->post(route('admin.advances.record-payment', $advance->id), [
            'amount'         => 1000.00,
            'repayment_date' => '2026-10-01',
            'notes'          => 'First installment',
        ]);

        $responsePayment->assertSessionHas('success');
        $advance->refresh();
        $this->assertEquals(1000.00, (float) $advance->paid_amount);
        $this->assertEquals(1000.00, (float) $advance->remaining_amount);
        $this->assertEquals('approved', $advance->status);

        // Waive the remaining 1000
        $responseWaiver = $this->post(route('admin.advances.waive', $advance->id), [
            'waived_amount' => 1000.00,
            'reason'        => 'Management incentive waiver',
        ]);

        $responseWaiver->assertSessionHas('success');
        $advance->refresh();
        $this->assertEquals(1000.00, (float) $advance->waived_amount);
        $this->assertEquals(0.00, (float) $advance->remaining_amount);
        $this->assertEquals('paid', $advance->status);
    }

    /**
     * سلفة اتسدّد جزء منها ما تتحوّلش لمرفوضة.
     *
     * الرفض مسموح للحالة pending بس، والسلفة دي بقت approved
     * وعليها سداد — فالرفض هيضيّع تتبّع فلوس اتحصّلت فعلاً.
     */
    public function test_settled_advance_cannot_be_rejected(): void
    {
        $this->actingAs($this->admin, 'admin');

        $advance = EmployeeAdvance::create([
            'employee_id'         => $this->employee->id,
            'amount'              => 1500.00,
            'installments_count'  => 1,
            'monthly_installment' => 1500.00,
            'paid_amount'         => 500.00,
            'waived_amount'       => 0.00,
            'remaining_amount'    => 1000.00,
            'advance_date'        => '2026-09-01',
            'start_date'          => '2026-10-01',
            'status'              => 'approved',
        ]);

        $this->post(route('admin.advances.reject', $advance->id));

        $this->assertEquals('approved', $advance->fresh()->status, 'السلفة المسدَّد جزء منها اتحوّلت لمرفوضة');
    }

    /** والاعتماد كمان مايتكررش على سلفة معتمدة أصلاً. */
    public function test_already_approved_advance_cannot_be_reapproved(): void
    {
        $this->actingAs($this->admin, 'admin');

        $advance = EmployeeAdvance::create([
            'employee_id'         => $this->employee->id,
            'amount'              => 1000.00,
            'installments_count'  => 1,
            'monthly_installment' => 1000.00,
            'paid_amount'         => 0.00,
            'waived_amount'       => 0.00,
            'remaining_amount'    => 1000.00,
            'advance_date'        => '2026-09-01',
            'start_date'          => '2026-10-01',
            'status'              => 'rejected',
        ]);

        $this->post(route('admin.advances.approve', $advance->id));

        $this->assertEquals('rejected', $advance->fresh()->status, 'السلفة المرفوضة اتعمدت');
    }

    /**
     * الحالة ما تتغيّرش من الجدول خالص.
     *
     * كانت قائمة منسدلة جوه العمود شغالة بصلاحية advance_update
     * العامة، فبتلتف حول صلاحيتي advance_approve و advance_reject.
     */
    public function test_status_cannot_be_changed_from_the_table(): void
    {
        $this->assertFalse(
            \Illuminate\Support\Facades\Route::has('admin.advances.status'),
            'مسار تغيير الحالة المباشر لسه موجود'
        );

        $this->actingAs($this->admin, 'admin');

        $html = $this->get(route('admin.advances.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString(
            'advance-status-select',
            $html,
            'قائمة تغيير الحالة لسه في الجدول'
        );
    }
}
