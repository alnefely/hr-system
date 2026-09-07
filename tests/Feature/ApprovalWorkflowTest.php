<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeePenalty;
use App\Models\EmployeeReward;
use App\Models\JobTitle;
use App\Models\Role;
use App\Services\HR\PayrollGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * دورة اعتماد الجزاءات والمكافآت.
 *
 * المبدأ المحاسبي المطبَّق هنا هو فصل المهام (Segregation of Duties):
 * أي حركة بتأثر على راتب الموظف — خصم أو استحقاق — لازم يراجعها
 * شخص غير اللي سجّلها، ومايدخلش المسير غير بعد الاعتماد.
 *
 * قبل كده كانت المكافأة بتتعمد ذاتياً، والجزاء بيتخصم من غير مراجعة أصلاً.
 */
class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $recorder;
    protected Admin $approver;
    protected Branch $branch;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);
        $jobTitle = JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        $this->branch = Branch::create([
            'name' => 'HQ', 'code' => 'AP', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'branch_id' => $this->branch->id, 'job_title_id' => $jobTitle->id,
            'name' => 'Target', 'employee_code' => 'AP-1', 'phone' => '0100',
            'status' => 'active', 'basic_salary' => 3000, 'hire_date' => '2024-01-01',
        ]);

        // مسجّل غير رئيسي عشان قاعدة "مايعتمدش اللي سجّله" تنطبق
        $this->recorder = Admin::create([
            'name' => 'Recorder', 'email' => 'rec@ap.test',
            'password' => bcrypt('x'), 'main' => false,
            'role_id' => $role->id, 'employee_id' => $this->employee->id,
        ]);

        $this->approver = Admin::create([
            'name' => 'Approver', 'email' => 'app@ap.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);
    }

    // ========================================================
    //  الجزاءات
    // ========================================================

    public function test_new_penalty_starts_pending(): void
    {
        $this->actingAs($this->approver, 'admin')
            ->post(route('admin.penalties.store'), [
                'employee_id'  => $this->employee->id,
                'penalty_type' => 'amount_deduction',
                'deduction_amount' => 150,
                'penalty_date' => '2026-09-05',
                'reason'       => 'تأخير متكرر',
            ])->assertSessionHasNoErrors();

        $penalty = EmployeePenalty::where('employee_id', $this->employee->id)->first();

        $this->assertNotNull($penalty);
        $this->assertEquals('pending', $penalty->status, 'الجزاء المفروض يبدأ قيد الاعتماد');
    }

    public function test_pending_penalty_does_not_enter_payroll(): void
    {
        $this->actingAs($this->approver, 'admin');

        EmployeePenalty::create([
            'employee_id' => $this->employee->id, 'penalty_type' => 'amount_deduction',
            'days_count' => 0, 'deduction_amount' => 500,
            'penalty_date' => '2026-09-10', 'reason' => 'معلق', 'status' => 'pending',
        ]);

        $payroll = app(PayrollGenerator::class)->generate(2026, 9, $this->branch->id);
        $item = $payroll->items()->where('employee_id', $this->employee->id)->first();

        $this->assertEquals(0.00, (float) $item->deductions, 'جزاء معلق اتخصم من المسير');
    }

    public function test_approved_penalty_enters_payroll(): void
    {
        $this->actingAs($this->approver, 'admin');

        EmployeePenalty::create([
            'employee_id' => $this->employee->id, 'penalty_type' => 'amount_deduction',
            'days_count' => 0, 'deduction_amount' => 500,
            'penalty_date' => '2026-09-10', 'reason' => 'معتمد', 'status' => 'approved',
        ]);

        $payroll = app(PayrollGenerator::class)->generate(2026, 9, $this->branch->id);
        $item = $payroll->items()->where('employee_id', $this->employee->id)->first();

        $this->assertEquals(500.00, (float) $item->deductions);
    }

    public function test_recorder_cannot_approve_their_own_penalty(): void
    {
        $penalty = EmployeePenalty::create([
            'employee_id' => $this->employee->id, 'penalty_type' => 'amount_deduction',
            'days_count' => 0, 'deduction_amount' => 100,
            'penalty_date' => '2026-09-01', 'reason' => 'ذاتي',
            'status' => 'pending', 'created_by' => $this->recorder->id,
        ]);

        $this->actingAs($this->recorder, 'admin')
            ->post(route('admin.penalties.approve', $penalty->id));

        $this->assertEquals(
            'pending',
            $penalty->fresh()->status,
            'المسجّل قدر يعتمد جزاء سجّله بنفسه — فصل المهام مكسور'
        );
    }

    public function test_another_officer_can_approve(): void
    {
        $penalty = EmployeePenalty::create([
            'employee_id' => $this->employee->id, 'penalty_type' => 'amount_deduction',
            'days_count' => 0, 'deduction_amount' => 100,
            'penalty_date' => '2026-09-01', 'reason' => 'مراجعة',
            'status' => 'pending', 'created_by' => $this->recorder->id,
        ]);

        $this->actingAs($this->approver, 'admin')
            ->post(route('admin.penalties.approve', $penalty->id));

        $penalty->refresh();

        $this->assertEquals('approved', $penalty->status);
        $this->assertEquals($this->approver->id, $penalty->approved_by);
        $this->assertNotNull($penalty->approved_at);
    }

    public function test_rejection_requires_a_reason_and_is_recorded(): void
    {
        $penalty = EmployeePenalty::create([
            'employee_id' => $this->employee->id, 'penalty_type' => 'amount_deduction',
            'days_count' => 0, 'deduction_amount' => 100,
            'penalty_date' => '2026-09-01', 'reason' => 'x', 'status' => 'pending',
        ]);

        $this->actingAs($this->approver, 'admin');

        // من غير سبب => مرفوض
        $this->post(route('admin.penalties.reject', $penalty->id))
            ->assertSessionHasErrors('rejection_reason');

        $this->post(route('admin.penalties.reject', $penalty->id), [
            'rejection_reason' => 'التحقيق أثبت عدم المخالفة',
        ]);

        $penalty->refresh();

        $this->assertEquals('rejected', $penalty->status);
        $this->assertEquals('التحقيق أثبت عدم المخالفة', $penalty->rejection_reason);
    }

    public function test_decided_penalty_cannot_be_decided_again(): void
    {
        $penalty = EmployeePenalty::create([
            'employee_id' => $this->employee->id, 'penalty_type' => 'amount_deduction',
            'days_count' => 0, 'deduction_amount' => 100,
            'penalty_date' => '2026-09-01', 'reason' => 'x', 'status' => 'approved',
        ]);

        $this->actingAs($this->approver, 'admin')
            ->post(route('admin.penalties.reject', $penalty->id), [
                'rejection_reason' => 'محاولة تغيير',
            ]);

        $this->assertEquals('approved', $penalty->fresh()->status);
    }

    public function test_approved_penalty_cannot_be_deleted(): void
    {
        $penalty = EmployeePenalty::create([
            'employee_id' => $this->employee->id, 'penalty_type' => 'amount_deduction',
            'days_count' => 0, 'deduction_amount' => 100,
            'penalty_date' => '2026-09-01', 'reason' => 'x', 'status' => 'approved',
        ]);

        $this->actingAs($this->approver, 'admin')
            ->delete(route('admin.penalties.destroy', $penalty->id));

        $this->assertNotSoftDeleted('employee_penalties', ['id' => $penalty->id]);
    }

    // ========================================================
    //  المكافآت
    // ========================================================

    public function test_new_reward_starts_pending_not_self_approved(): void
    {
        $this->actingAs($this->approver, 'admin')
            ->post(route('admin.rewards.store'), [
                'employee_id' => $this->employee->id,
                'reward_type' => 'bonus',
                'title'       => 'مكافأة أداء',
                'amount'      => 800,
                'reward_date' => '2026-09-05',
            ])->assertSessionHasNoErrors();

        $reward = EmployeeReward::where('employee_id', $this->employee->id)->first();

        $this->assertNotNull($reward);
        $this->assertEquals('pending', $reward->status, 'المكافأة اتعمدت ذاتياً');
        $this->assertNull($reward->approved_at);
    }

    public function test_pending_reward_does_not_enter_payroll(): void
    {
        $this->actingAs($this->approver, 'admin');

        EmployeeReward::create([
            'employee_id' => $this->employee->id, 'reward_type' => 'bonus',
            'title' => 'معلقة', 'amount' => 700, 'reward_date' => '2026-09-10',
            'status' => 'pending',
        ]);

        $payroll = app(PayrollGenerator::class)->generate(2026, 9, $this->branch->id);
        $item = $payroll->items()->where('employee_id', $this->employee->id)->first();

        $this->assertEquals(0.00, (float) $item->allowances, 'مكافأة معلقة اتصرفت');
    }

    public function test_approved_reward_enters_payroll(): void
    {
        $this->actingAs($this->approver, 'admin');

        EmployeeReward::create([
            'employee_id' => $this->employee->id, 'reward_type' => 'bonus',
            'title' => 'معتمدة', 'amount' => 700, 'reward_date' => '2026-09-10',
            'status' => 'approved',
        ]);

        $payroll = app(PayrollGenerator::class)->generate(2026, 9, $this->branch->id);
        $item = $payroll->items()->where('employee_id', $this->employee->id)->first();

        $this->assertEquals(700.00, (float) $item->allowances);
    }

    public function test_recorder_cannot_approve_their_own_reward(): void
    {
        $reward = EmployeeReward::create([
            'employee_id' => $this->employee->id, 'reward_type' => 'bonus',
            'title' => 'ذاتية', 'amount' => 500, 'reward_date' => '2026-09-01',
            'status' => 'pending', 'created_by' => $this->recorder->id,
        ]);

        $this->actingAs($this->recorder, 'admin')
            ->post(route('admin.rewards.approve', $reward->id));

        $this->assertEquals('pending', $reward->fresh()->status);
    }

    // ========================================================
    //  الصلاحيات
    // ========================================================

    public function test_approval_requires_the_permission(): void
    {
        $limitedRole = Role::create(['name' => 'Limited', 'roles' => ['penalty_show']]);
        $limited = Admin::create([
            'name' => 'Limited', 'email' => 'lim@ap.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $limitedRole->id,
        ]);

        $penalty = EmployeePenalty::create([
            'employee_id' => $this->employee->id, 'penalty_type' => 'amount_deduction',
            'days_count' => 0, 'deduction_amount' => 100,
            'penalty_date' => '2026-09-01', 'reason' => 'x', 'status' => 'pending',
        ]);

        $this->actingAs($limited, 'admin')
            ->post(route('admin.penalties.approve', $penalty->id))
            ->assertRedirect(route('admin.error_403'));

        $this->assertEquals('pending', $penalty->fresh()->status);
    }
}
