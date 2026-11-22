<?php

namespace Tests\Feature;

use App\Exceptions\InvalidStateException;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveBalance;
use App\Models\EmployeeSalary;
use App\Models\JobTitle;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Role;
use App\Services\HR\AdvanceService;
use App\Services\HR\LeaveService;
use App\Services\HR\PayrollGenerator;
use App\Services\HR\PayrollWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * أخطاء الرواتب والسلف اللي اتصلّحت في المراجعة الشاملة.
 * كل اختبار بيمثّل سيناريو كان بيخصم فلوس غلط من الموظف.
 */
class PayrollSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Branch $branch;
    protected JobTitle $job;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'HQ', 'code' => 'HQ-01', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);
        $this->job = JobTitle::create(['name' => 'Engineer', 'is_active' => true]);

        $role = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@test.com', 'password' => bcrypt('password'),
            'main' => true, 'role_id' => $role->id,
        ]);

        $this->employee = $this->makeEmployee('EMP-1', ['basic_salary' => 6000]);

        $this->actingAs($this->admin, 'admin');
    }

    private function makeEmployee(string $code, array $attrs = []): Employee
    {
        return Employee::create($attrs + [
            'branch_id' => $this->branch->id, 'job_title_id' => $this->job->id,
            'name' => 'Emp ' . $code, 'employee_code' => $code,
            'phone' => '+2010' . crc32($code), 'status' => 'active',
            'basic_salary' => 6000, 'hire_date' => '2024-01-01',
        ]);
    }

    private function advance(float $amount, int $installments, string $start = '2026-01-01'): EmployeeAdvance
    {
        return EmployeeAdvance::create([
            'employee_id' => $this->employee->id, 'amount' => $amount,
            'installments_count' => $installments,
            'monthly_installment' => round($amount / $installments, 2),
            'paid_amount' => 0, 'waived_amount' => 0, 'remaining_amount' => $amount,
            'advance_date' => $start, 'start_date' => $start, 'status' => 'approved',
        ]);
    }

    private function generate(int $year, int $month): Payroll
    {
        return app(PayrollGenerator::class)->generate($year, $month, $this->branch->id);
    }

    private function itemFor(Payroll $payroll, ?Employee $employee = null): PayrollItem
    {
        return $payroll->items()->where('employee_id', ($employee ?? $this->employee)->id)->firstOrFail();
    }

    private function installmentIn(PayrollItem $item): float
    {
        return (float) collect($item->fresh()->deduction_details)->whereNotNull('advance_id')->sum('amount');
    }

    /** مسير اتصرف جزء منه مايرجعش مسودة ولا يتمسح. */
    public function test_partially_paid_payroll_cannot_be_unapproved_or_deleted(): void
    {
        $other = $this->makeEmployee('EMP-2');
        $payroll = $this->generate(2026, 3);
        $workflow = app(PayrollWorkflow::class);

        $workflow->approve($payroll);
        $workflow->payItem($this->itemFor($payroll));

        try {
            $workflow->unapprove($payroll->fresh(), 'غلط');
            $this->fail('إلغاء اعتماد مسير مصروف جزئياً اتسمح');
        } catch (InvalidStateException) {
        }

        $this->assertSame('approved', $payroll->fresh()->status);
        $this->assertSame('pending', $this->itemFor($payroll, $other)->status);
    }

    /** مسيرين مفتوحين لنفس الموظف مايخصموش نفس القسط مرتين. */
    public function test_two_open_payrolls_do_not_deduct_the_same_installment(): void
    {
        $this->advance(1000, 1);

        $october  = $this->generate(2026, 10);
        $november = $this->generate(2026, 11);

        $this->assertEquals(1000, $this->installmentIn($this->itemFor($october)));
        $this->assertEquals(0, $this->installmentIn($this->itemFor($november)), 'القسط اتخصم في المسيرين');
    }

    /** السداد اليدوي مايتعدّاش على قسط محجوز في مسير مفتوح. */
    public function test_manual_repayment_cannot_take_a_reserved_installment(): void
    {
        $advance = $this->advance(1000, 1);
        $this->generate(2026, 10);

        $this->expectException(InvalidStateException::class);
        app(AdvanceService::class)->recordPayment($advance, 500, '2026-10-10', null);
    }

    /** تعديل تفاصيل البند بيحافظ على ربط السلفة، فالصرف بيسجّل السداد. */
    public function test_editing_item_details_keeps_the_advance_link(): void
    {
        $advance = $this->advance(900, 3);
        $payroll = $this->generate(2026, 4);
        $item = $this->itemFor($payroll);
        $workflow = app(PayrollWorkflow::class);

        $rows = collect($item->deduction_details)->map(fn ($r, $i) => [
            'ref' => $i, 'title' => $r['title'] . ' (معدّل)', 'amount' => $r['amount'],
        ])->all();
        $rows[] = ['title' => 'خصم إضافي', 'amount' => 50];

        $workflow->updateItemDetails($item, [], $rows, 'cash', null);

        $this->assertEquals(300, $this->installmentIn($item));

        $workflow->approve($payroll->fresh());
        $workflow->payAll($payroll->fresh());

        $this->assertEquals(600, (float) $advance->fresh()->remaining_amount, 'السداد ماتسجّلش بعد تعديل التفاصيل');
    }

    /** المتصفح مايقدرش يزوّد قسط أكبر من رصيد السلفة. */
    public function test_item_details_cannot_raise_installment_above_balance(): void
    {
        $this->advance(300, 3);
        $payroll = $this->generate(2026, 4);
        $item = $this->itemFor($payroll);

        $rows = collect($item->deduction_details)->map(fn ($r, $i) => ['ref' => $i, 'title' => $r['title'], 'amount' => 5000])->all();

        $this->expectException(InvalidStateException::class);
        app(PayrollWorkflow::class)->updateItemDetails($item, [], $rows, 'cash', null);
    }

    /** تصفير الخصم من التعديل الجماعي = إعفاء من القسط، مش سداد وهمي. */
    public function test_zeroing_total_deductions_does_not_record_a_repayment(): void
    {
        $advance = $this->advance(900, 3);
        $payroll = $this->generate(2026, 5);
        $item = $this->itemFor($payroll);
        $workflow = app(PayrollWorkflow::class);

        $workflow->updateItems($payroll, [
            $item->id => ['allowances' => 0, 'deductions' => 0, 'payment_method' => 'cash'],
        ]);

        $workflow->approve($payroll->fresh());
        $workflow->payAll($payroll->fresh());

        $this->assertEquals(900, (float) $advance->fresh()->remaining_amount);
        $this->assertEquals(6000, (float) $item->fresh()->net_salary);
    }

    /** كسور التقريب تتخصم مع آخر قسط بدل شهر رابع. */
    public function test_rounding_tail_is_taken_with_the_last_installment(): void
    {
        $advance = $this->advance(1000, 3);
        $workflow = app(PayrollWorkflow::class);

        foreach ([1, 2, 3] as $month) {
            $payroll = $this->generate(2026, $month);
            $workflow->approve($payroll);
            $workflow->payAll($payroll->fresh());
        }

        $this->assertEquals(0, (float) $advance->fresh()->remaining_amount);
        $this->assertSame('paid', $advance->fresh()->status);
    }

    /** اللي أصدر المسير مايعتمدهوش بنفسه (لو مش المدير العام). */
    public function test_payroll_creator_cannot_approve_it(): void
    {
        $role = Role::create(['name' => 'HR', 'roles' => ['payroll_create', 'payroll_approve', 'payroll_show']]);
        $hr = Admin::create([
            'name' => 'HR', 'email' => 'hr@test.com', 'password' => bcrypt('password'),
            'main' => false, 'role_id' => $role->id,
        ]);
        $this->actingAs($hr, 'admin');

        $payroll = app(PayrollGenerator::class)->generate(2026, 6, $this->branch->id, null, $hr->id);

        $this->expectException(InvalidStateException::class);
        app(PayrollWorkflow::class)->approve($payroll);
    }

    /** المعيّن في نص الشهر بياخد أيامه، واللي "في إجازة" بياخد راتبه. */
    public function test_mid_month_hire_is_prorated_and_on_leave_employee_is_paid(): void
    {
        $newHire = $this->makeEmployee('EMP-NEW', ['basic_salary' => 3000, 'hire_date' => '2026-06-16']);
        $onLeave = $this->makeEmployee('EMP-OL', ['status' => 'on_leave']);
        $future  = $this->makeEmployee('EMP-FUT', ['hire_date' => '2026-07-05']);

        $payroll = $this->generate(2026, 6);

        $this->assertEquals(1500, (float) $this->itemFor($payroll, $newHire)->basic_salary);
        $this->assertEquals(6000, (float) $this->itemFor($payroll, $onLeave)->basic_salary);
        $this->assertFalse($payroll->items()->where('employee_id', $future->id)->exists());
    }

    /** العلاوة اللي ميعادها أول الشهر بتتطبق في مسيره. */
    public function test_future_dated_raise_applies_in_its_month(): void
    {
        EmployeeSalary::create([
            'employee_id' => $this->employee->id, 'basic_salary' => 8000,
            'effective_date' => '2026-12-01', 'change_reason' => 'annual_raise',
        ]);

        $this->assertEquals(6000, (float) $this->itemFor($this->generate(2026, 11))->basic_salary);
        $this->assertEquals(8000, (float) $this->itemFor($this->generate(2026, 12))->basic_salary);
    }

    /** المشرف مايعتمدش إجازة لنفسه. */
    public function test_admin_cannot_approve_own_leave(): void
    {
        $role = Role::create(['name' => 'Mgr', 'roles' => ['leave_approve']]);
        $manager = Admin::create([
            'name' => 'Mgr', 'email' => 'mgr@test.com', 'password' => bcrypt('password'),
            'main' => false, 'role_id' => $role->id, 'employee_id' => $this->employee->id,
        ]);
        $this->actingAs($manager, 'admin');

        $leave = EmployeeLeave::create([
            'employee_id' => $this->employee->id, 'leave_type' => 'annual',
            'start_date' => '2026-06-01', 'end_date' => '2026-06-02', 'days_count' => 2, 'status' => 'pending',
        ]);

        $this->expectException(InvalidStateException::class);
        app(LeaveService::class)->approve($leave);
    }

    /** إجازة 30 يوم ماتتسجّلش "يوم واحد". */
    public function test_leave_days_count_cannot_be_understated(): void
    {
        $this->expectException(InvalidStateException::class);

        app(LeaveService::class)->create([
            'employee_id' => $this->employee->id, 'leave_type' => 'annual',
            'start_date' => '2026-06-01', 'end_date' => '2026-06-30', 'days_count' => 1,
        ]);
    }

    /** إجازة بتعدّي السنة بتتوزّع على السنتين. */
    public function test_leave_spanning_new_year_is_split_between_balances(): void
    {
        $leave = EmployeeLeave::create([
            'employee_id' => $this->employee->id, 'leave_type' => 'annual',
            'start_date' => '2026-12-28', 'end_date' => '2027-01-03', 'days_count' => 7, 'status' => 'pending',
        ]);

        app(LeaveService::class)->approve($leave);

        $used = fn (int $year) => (float) EmployeeLeaveBalance::where('employee_id', $this->employee->id)
            ->where('year', $year)->value('used_annual');

        $this->assertEquals(4, $used(2026));
        $this->assertEquals(3, $used(2027));
    }

    /** إلغاء إجازة بدون أجر بعد اعتماد مسير شهرها ممنوع. */
    public function test_unpaid_leave_cannot_change_after_its_payroll_is_approved(): void
    {
        $leave = EmployeeLeave::create([
            'employee_id' => $this->employee->id, 'leave_type' => 'unpaid',
            'start_date' => '2026-08-10', 'end_date' => '2026-08-11', 'days_count' => 2, 'status' => 'pending',
        ]);
        app(LeaveService::class)->approve($leave);

        $payroll = $this->generate(2026, 8);
        app(PayrollWorkflow::class)->approve($payroll);

        $this->expectException(InvalidStateException::class);
        app(LeaveService::class)->reject($leave->fresh(), 'إلغاء');
    }
}
