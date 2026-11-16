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

/**
 * حواجز دورات الاعتماد اللي اتنقلت للخدمات.
 */
class HrWorkflowGuardsTest extends TestCase
{
    use RefreshDatabase;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create([
            'name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'branch_id' => $branch->id,
            'job_title_id' => JobTitle::create(['name' => 'Staff', 'is_active' => true])->id,
            'name' => 'Omar', 'employee_code' => 'HQ-0001', 'phone' => '01000000000',
            'status' => 'active', 'basic_salary' => 3000, 'hire_date' => '2024-01-01',
        ]);

        $role = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $this->actingAs(Admin::create([
            'name' => 'Root', 'email' => 'root@test.com',
            'password' => bcrypt('password'), 'main' => true, 'role_id' => $role->id,
        ]), 'admin');
    }

    private function advance(string $status): EmployeeAdvance
    {
        return EmployeeAdvance::create([
            'employee_id' => $this->employee->id, 'amount' => 1000, 'installments_count' => 2,
            'monthly_installment' => 500, 'paid_amount' => 0, 'waived_amount' => 0,
            'remaining_amount' => 1000, 'advance_date' => '2026-10-01',
            'start_date' => '2026-11-01', 'status' => $status,
        ]);
    }

    /**
     * سداد على سلفة معلّقة كان بيتسجّل، ولو غطّى الرصيد كانت بتبقى
     * "مسددة" من غير ما حد يعتمدها.
     */
    public function test_payment_and_waiver_require_an_approved_advance(): void
    {
        foreach (['pending', 'rejected'] as $status) {
            $advance = $this->advance($status);

            $this->post(route('admin.advances.record-payment', $advance->id), [
                'amount' => 1000, 'repayment_date' => '2026-11-05',
            ])->assertSessionHas('error');

            $this->post(route('admin.advances.waive', $advance->id), [
                'waived_amount' => 100, 'reason' => 'x',
            ])->assertSessionHas('error');

            $advance->refresh();
            $this->assertSame($status, $advance->status);
            $this->assertEquals(0, (float) $advance->paid_amount);
            $this->assertSame(0, $advance->repayments()->count());
        }
    }

    public function test_approved_advance_accepts_payment(): void
    {
        $advance = $this->advance('approved');

        $this->post(route('admin.advances.record-payment', $advance->id), [
            'amount' => 400, 'repayment_date' => '2026-11-05',
        ])->assertSessionHas('success');

        $this->assertEquals(600, (float) $advance->fresh()->remaining_amount);
    }

    /** سلفة عليها سداد ماتتحذفش — كانت بتختفي ومعاها تاريخ الخصومات. */
    public function test_advance_with_repayments_cannot_be_deleted(): void
    {
        $advance = $this->advance('approved');
        $this->post(route('admin.advances.record-payment', $advance->id), [
            'amount' => 100, 'repayment_date' => '2026-11-05',
        ]);

        $this->delete(route('admin.advances.destroy', $advance->id))->assertSessionHas('error');
        $this->assertNotSoftDeleted('employee_advances', ['id' => $advance->id]);

        $fresh = $this->advance('pending');
        $this->delete(route('admin.advances.destroy', $fresh->id))->assertSessionHas('success');
        $this->assertSoftDeleted('employee_advances', ['id' => $fresh->id]);
    }

    /** المكافأة المصروفة مع المسير ماتتحذفش (كانت isEditable بتسمح بيها). */
    public function test_paid_reward_cannot_be_deleted(): void
    {
        $reward = EmployeeReward::create([
            'employee_id' => $this->employee->id, 'reward_type' => 'bonus', 'title' => 'Q4',
            'amount' => 500, 'reward_date' => '2026-11-01', 'status' => 'paid',
        ]);

        $this->delete(route('admin.rewards.destroy', $reward->id))->assertSessionHas('error');
        $this->assertNotSoftDeleted('employee_rewards', ['id' => $reward->id]);
    }

    /** إلغاء الاعتماد للمسير المعتمد بس — مش للمسودة ولا المرفوض. */
    public function test_unapprove_only_applies_to_approved_payrolls(): void
    {
        $payroll = Payroll::create([
            'title' => 'Nov', 'year' => 2026, 'month' => 11,
            'branch_id' => $this->employee->branch_id, 'status' => 'rejected',
        ]);

        $this->post(route('admin.payrolls.unapprove', $payroll->id), ['unapprove_reason' => 'oops'])
            ->assertSessionHas('error');
        $this->assertSame('rejected', $payroll->fresh()->status);

        $payroll->update(['status' => 'approved']);
        $this->post(route('admin.payrolls.unapprove', $payroll->id), ['unapprove_reason' => 'oops'])
            ->assertSessionHas('success');
        $this->assertSame('draft', $payroll->fresh()->status);
    }
}
