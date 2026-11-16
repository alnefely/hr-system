<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeReward;
use App\Models\JobTitle;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\Sale;
use App\Services\HR\PayrollGenerator;
use App\Support\CodeSequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * توليد الأكواد التسلسلية + أداء صرف المسير.
 */
class CodeSequenceAndPayrollPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Branch $branch;
    protected JobTitle $jobTitle;
    protected Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $this->jobTitle = JobTitle::create(['name' => 'Engineer', 'is_active' => true]);
        $this->role = Role::create(['name' => 'Super Admin', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@test.com',
            'password' => bcrypt('password'), 'main' => true, 'role_id' => $this->role->id,
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    private function employee(string $code, ?Branch $branch = null, float $salary = 6000): Employee
    {
        return Employee::create([
            'branch_id' => ($branch ?? $this->branch)->id, 'job_title_id' => $this->jobTitle->id,
            'name' => 'Emp ' . $code, 'employee_code' => $code,
            'phone' => '0100' . random_int(1000000, 9999999), 'status' => 'active',
            'basic_salary' => $salary, 'hire_date' => '2024-01-01',
        ]);
    }

    // ========================================================
    //  الأكواد
    // ========================================================

    /** الرقم التالي = أكبر رقم مستخدم + 1، مش عدد السجلات + 1. */
    public function test_next_code_follows_highest_used_number_not_count(): void
    {
        $this->employee('HQ-0001');
        $this->employee('HQ-0007');

        $this->assertSame('HQ-0008', CodeSequence::next(Employee::class, 'employee_code', 'HQ-'));
    }

    /**
     * موظف اتنقل لفرع تاني: count() كان بيقل ويطلّع كود موجود → خطأ unique.
     */
    public function test_transferred_employee_does_not_cause_duplicate_code(): void
    {
        $other = Branch::create([
            'name' => 'Other', 'code' => 'OT', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $this->employee('HQ-0001');
        $this->employee('HQ-0002')->update(['branch_id' => $other->id]);

        $next = CodeSequence::next(Employee::class, 'employee_code', 'HQ-');

        $this->assertSame('HQ-0003', $next);
        $this->assertFalse(Employee::withoutGlobalScopes()->where('employee_code', $next)->exists());
    }

    /** المحذوف ناعماً بيفضل حاجز رقمه. */
    public function test_soft_deleted_records_still_reserve_their_code(): void
    {
        $this->employee('HQ-0005')->delete();

        $this->assertSame('HQ-0006', CodeSequence::next(Employee::class, 'employee_code', 'HQ-'));
    }

    /**
     * رقم الفاتورة unique على النظام كله: مشرف الفرع لازم يشوف آخر رقم
     * في كل الفروع، مش فرعه بس (Global Scope).
     */
    public function test_invoice_number_ignores_branch_scope(): void
    {
        $other = Branch::create([
            'name' => 'Other', 'code' => 'OT', 'country' => 'Oman',
            'currency' => 'OMR', 'is_active' => true,
        ]);

        Sale::create([
            'invoice_number' => 'INV-202611-0009', 'sale_type' => 'direct_sale',
            'branch_id' => $other->id, 'sale_date' => '2026-11-01',
            'subtotal' => 10, 'total_amount' => 10, 'currency' => 'OMR',
            'paid_amount' => 10, 'due_amount' => 0,
            'payment_status' => 'paid', 'payment_method' => 'cash',
        ]);

        $branchAdmin = Admin::create([
            'name' => 'Branch', 'email' => 'b@test.com', 'password' => bcrypt('password'),
            'main' => false, 'role_id' => $this->role->id,
            'employee_id' => $this->employee('HQ-0001')->id,
        ]);
        $this->actingAs($branchAdmin, 'admin');

        $this->assertSame('INV-202611-0010', Sale::nextInvoiceNumber('202611'));
    }

    public function test_customer_code_uses_highest_number(): void
    {
        // رقم أكبر من أي بيانات تجريبية متاحة، والعدد الفعلي أقل منه بكتير
        Customer::create(['name' => 'A', 'code' => 'CUST-0040']);

        $this->get(route('admin.customers.create'))
            ->assertOk()
            ->assertViewHas('nextCode', 'CUST-0041');
    }

    // ========================================================
    //  صرف المسير
    // ========================================================

    /**
     * صرف المسير كله: عدد الاستعلامات لازم مايكبرش مع عدد البنود بشكل
     * كبير. قبل الإصلاح كل بند كان بيعمل حوالي 8 استعلامات زيادة
     * (lazy load للمسير + 4 SUM + save + find للسلفة).
     */
    public function test_paying_whole_payroll_settles_everything_with_bounded_queries(): void
    {
        $employees = collect(range(1, 20))->map(fn ($i) => $this->employee(sprintf('HQ-%04d', $i)));

        $advance = EmployeeAdvance::create([
            'employee_id' => $employees[0]->id, 'amount' => 1000,
            'installments_count' => 2, 'monthly_installment' => 500,
            'paid_amount' => 0, 'waived_amount' => 0, 'remaining_amount' => 1000,
            'advance_date' => '2026-10-01', 'start_date' => '2026-10-01', 'status' => 'approved',
        ]);

        $reward = EmployeeReward::create([
            'employee_id' => $employees[1]->id, 'title' => 'Bonus', 'amount' => 300,
            'reward_date' => '2026-11-10', 'status' => 'approved',
        ]);

        $payroll = app(PayrollGenerator::class)->generate(2026, 11, $this->branch->id);
        $payroll->update(['status' => 'approved']);

        DB::enableQueryLog();
        $this->post(route('admin.payrolls.pay', $payroll->id))->assertRedirect();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $payroll->refresh();
        $this->assertSame('paid', $payroll->status);
        $this->assertSame(0, $payroll->items()->where('status', '!=', 'paid')->count());

        // السلفة اتخصم منها قسط واحد بس، والمكافأة اتصرفت
        $this->assertEquals(500, (float) $advance->fresh()->paid_amount);
        $this->assertSame('paid', $reward->fresh()->status);

        // 20 بند: كل بند UPDATE + سجل حركة. الحد ده كان بيتعدى بكتير قبل الإصلاح.
        $this->assertLessThan(20 * 4, $queries, "صرف المسير عمل {$queries} استعلام");
    }

    /** الصرف مايغيّرش إجماليات المسير. */
    public function test_paying_selected_items_keeps_totals_intact(): void
    {
        foreach (range(1, 3) as $i) {
            $this->employee(sprintf('HQ-%04d', $i), salary: 1000 * $i);
        }

        $payroll = app(PayrollGenerator::class)->generate(2026, 11, $this->branch->id);
        $payroll->update(['status' => 'approved']);
        $net = (float) $payroll->total_net_salary;

        $ids = $payroll->items()->limit(2)->pluck('id')->all();
        $this->post(route('admin.payrolls.pay-selected', $payroll->id), ['item_ids' => $ids]);

        $payroll->refresh();
        $this->assertSame('approved', $payroll->status, 'المسير اتقفل وفيه بند لسه ماتصرفش');
        $this->assertEquals($net, (float) $payroll->total_net_salary);
        $this->assertSame(2, $payroll->items()->where('status', 'paid')->count());
    }
}
