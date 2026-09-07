<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\Sale;
use App\Services\Dashboard\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * لوحة التحكم: الملخص والرسوم البيانية.
 *
 * الأرقام بتتجمّع في SQL مش حلقات PHP، وكل حاجة بتحترم عزل الفروع
 * تلقائياً. والمبالغ مفصولة بالعملة — مفيش جمع بين عملات مختلفة.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Branch $branch;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@dash.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->branch = Branch::create([
            'name' => 'HQ', 'code' => 'DSH', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $jobTitle = JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        $this->employee = Employee::create([
            'branch_id' => $this->branch->id, 'job_title_id' => $jobTitle->id,
            'name' => 'Worker', 'employee_code' => 'DSH-1', 'phone' => '0100',
            'status' => 'active', 'basic_salary' => 3000, 'hire_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    public function test_dashboard_renders(): void
    {
        $this->get(route('admin.home'))->assertOk();
    }

    public function test_requires_permission(): void
    {
        $limited = Admin::create([
            'name' => 'No Access', 'email' => 'no@dash.test',
            'password' => bcrypt('x'), 'main' => true,
            'role_id' => Role::create(['name' => 'Limited', 'roles' => []])->id,
        ]);

        $this->actingAs($limited, 'admin')
            ->get(route('admin.home'))
            ->assertRedirect(route('admin.error_403'));
    }

    /** المبالغ مفصولة بالعملة — مفيش جمع بين عملات مختلفة. */
    public function test_financials_are_split_per_currency(): void
    {
        Sale::create([
            'invoice_number' => 'D-1', 'sale_type' => 'direct_sale',
            'branch_id' => $this->branch->id, 'sale_date' => now()->toDateString(),
            'subtotal' => 1000, 'total_amount' => 1000, 'currency' => 'EGP',
            'paid_amount' => 1000, 'due_amount' => 0,
            'payment_status' => 'paid', 'payment_method' => 'cash',
        ]);

        Sale::create([
            'invoice_number' => 'D-2', 'sale_type' => 'direct_sale',
            'branch_id' => $this->branch->id, 'sale_date' => now()->toDateString(),
            'subtotal' => 500, 'total_amount' => 500, 'currency' => 'USD',
            'paid_amount' => 0, 'due_amount' => 500,
            'payment_status' => 'unpaid', 'payment_method' => 'cash',
        ]);

        $financial = collect(app(DashboardService::class)->payload()['financial'])
            ->keyBy('currency');

        // USD مالهاش بيانات في الـ seeders، فأرقامها بتاعتنا بالكامل
        $this->assertEquals(500.0, $financial['USD']['revenue']);
        $this->assertEquals(500.0, $financial['USD']['due'], 'المستحق مااتحسبش');

        // والأهم: العملتين منفصلتين مش مجموعتين
        $this->assertNotEquals(
            $financial['EGP']['revenue'],
            $financial['USD']['revenue'],
            'العملات اتجمعت في رقم واحد'
        );
    }

    /** الأرقام المعلّقة هي اللي المدير بيفتح اللوحة عشانها. */
    public function test_pending_counts_are_reported(): void
    {
        $payload = app(DashboardService::class)->payload();

        $this->assertArrayHasKey('leaves', $payload['pending']);
        $this->assertArrayHasKey('penalties', $payload['pending']);
        $this->assertArrayHasKey('payrolls', $payload['pending']);
    }

    /** كل عملة بترجع بنفس عدد النقاط بتاع التسميات. */
    public function test_trend_series_are_aligned(): void
    {
        $trend = app(DashboardService::class)->payload()['trend'];

        $expected = count($trend['labels']);
        $this->assertGreaterThan(0, $expected);

        foreach ($trend['series'] as $serie) {
            $this->assertArrayHasKey('currency', $serie);
            $this->assertCount($expected, $serie['revenue'], 'نقاط الإيرادات مش مطابقة للتسميات');
            $this->assertCount($expected, $serie['expenses'], 'نقاط المصروفات مش مطابقة للتسميات');
        }
    }

    /** ملخص الموارد البشرية بيعد الموظفين صح. */
    public function test_hr_summary_counts_employees(): void
    {
        $hr = app(DashboardService::class)->payload()['hr'];

        // الموظف اللي أنشأناه في setUp لازم يكون ضمن العدد
        $this->assertGreaterThanOrEqual(1, $hr['active']);
        $this->assertGreaterThanOrEqual(1, $hr['new_hires'], 'التعيين الجديد مااتحسبش');

        $this->assertEquals(
            Employee::withoutGlobalScope('branch')->where('status', 'active')->count(),
            $hr['active'],
            'عدد الموظفين النشطين غلط'
        );
    }

    /** توزيع المصروفات بيتجمّع بالتصنيف. */
    public function test_expense_split_groups_by_category(): void
    {
        $category = ExpenseCategory::create(['name' => 'Office', 'code' => 'OF', 'is_active' => true]);

        foreach ([300, 200] as $amount) {
            Expense::create([
                'title' => 'E' . $amount, 'expense_category_id' => $category->id,
                'branch_id' => $this->branch->id, 'amount' => $amount,
                'currency' => 'EGP', 'expense_date' => now()->toDateString(),
                'payment_method' => 'cash',
            ]);
        }

        $expenseSplit = app(DashboardService::class)->payload()['expenseSplit'];

        $this->assertArrayHasKey('currency', $expenseSplit, 'العملة مش راجعة مع التوزيع');

        $split = collect($expenseSplit['rows'])->keyBy('label');

        // المصروفين اتجمّعوا في صف واحد للتصنيف بتاعنا
        $this->assertArrayHasKey('Office', $split->all(), 'التصنيف مش ظاهر');
        $this->assertEquals(500.0, $split['Office']['total'], 'المصروفات مااتجمّعتش');
    }

    /** اللوحة بتحترم عزل الفروع تلقائياً. */
    public function test_dashboard_respects_branch_isolation(): void
    {
        $otherBranch = Branch::create([
            'name' => 'Other', 'code' => 'OTH', 'country' => 'Oman',
            'currency' => 'OMR', 'is_active' => true,
        ]);

        Sale::create([
            'invoice_number' => 'D-OTHER', 'sale_type' => 'direct_sale',
            'branch_id' => $otherBranch->id, 'sale_date' => now()->toDateString(),
            'subtotal' => 9999, 'total_amount' => 9999, 'currency' => 'OMR',
            'paid_amount' => 9999, 'due_amount' => 0,
            'payment_status' => 'paid', 'payment_method' => 'cash',
        ]);

        $branchAdmin = Admin::create([
            'name' => 'Branch Admin', 'email' => 'b@dash.test',
            'password' => bcrypt('x'), 'main' => false,
            'role_id' => Role::create(['name' => 'BR', 'roles' => all_roles()])->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($branchAdmin, 'admin');

        $currencies = collect(app(DashboardService::class)->payload()['financial'])
            ->pluck('currency');

        $this->assertNotContains('OMR', $currencies, 'مبيعات فرع تاني ظهرت في اللوحة');
    }
}
