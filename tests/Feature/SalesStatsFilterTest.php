<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\Sale;
use App\Services\Dashboard\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ب.١ — كروت المبيعات كانت بتتحسب باستعلامات جديدة من غير أي فلتر،
 * فالمستخدم يفلتر بتاريخ والجدول يتغير والأرقام فوق تفضل زي ما هي.
 *
 * ب.٢ — chart الموظفين حسب الفرع كان بيعد النشطين بس، والموظف
 * اللي في إجازة بيختفي من اللوحة خالص.
 */
class SalesStatsFilterTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Branch $branch;
    protected JobTitle $jobTitle;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@stats.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->branch = Branch::create([
            'name' => 'HQ', 'code' => 'STA', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $this->jobTitle = JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        $this->actingAs($this->admin, 'admin');
    }

    private function makeSale(string $number, string $date, string $status, float $amount, string $currency = 'EGP'): Sale
    {
        return Sale::create([
            'invoice_number' => $number, 'sale_type' => 'direct_sale',
            'branch_id' => $this->branch->id, 'sale_date' => $date,
            'subtotal' => $amount, 'total_amount' => $amount, 'currency' => $currency,
            'paid_amount' => $status === 'paid' ? $amount : 0,
            'due_amount'  => $status === 'paid' ? 0 : $amount,
            'payment_status' => $status, 'payment_method' => 'cash',
        ]);
    }

    // =====================================================
    //  ب.١
    // =====================================================

    /** الكروت بتحترم فلتر التاريخ مش بتتجاهله. */
    public function test_sales_cards_respect_date_filter(): void
    {
        $inRange  = now()->subDays(3);
        $outRange = now()->subMonths(6);

        $this->makeSale('S-IN-1',  $inRange->toDateString(),  'paid',   1000);
        $this->makeSale('S-IN-2',  $inRange->toDateString(),  'unpaid', 500);
        $this->makeSale('S-OUT-1', $outRange->toDateString(), 'paid',   7777);

        $response = $this->get(route('admin.sales.index', [
            'branch_id' => $this->branch->id,
            'date_from' => $inRange->copy()->subDay()->toDateString(),
            'date_to'   => $inRange->copy()->addDay()->toDateString(),
        ]))->assertOk();

        $response->assertViewHas('totalInvoicesCount', 2);
        $response->assertViewHas('paidSalesCount', 1);
        $response->assertViewHas('unpaidSalesCount', 1);

        // الفاتورة اللي بره النطاق مالهاش مكان في إجمالي الإيرادات
        $revenues = $response->viewData('revenuesByCurrency');
        $this->assertEquals(1500.0, (float) $revenues['EGP']);
    }

    /** الكروت بتحترم باقي الفلاتر كمان (حالة السداد). */
    public function test_sales_cards_respect_payment_status_filter(): void
    {
        $today = now()->toDateString();

        $this->makeSale('P-1', $today, 'paid',   1000);
        $this->makeSale('P-2', $today, 'unpaid', 500);

        $response = $this->get(route('admin.sales.index', [
            'branch_id'      => $this->branch->id,
            'payment_status' => 'paid',
        ]))->assertOk();

        $response->assertViewHas('totalInvoicesCount', 1);
        $response->assertViewHas('unpaidSalesCount', 0);
    }

    /** من غير فلتر تاريخ بنفضل على السلوك الافتراضي: الشهر الحالي. */
    public function test_cards_default_to_current_month_without_date_filter(): void
    {
        $this->makeSale('C-NOW', now()->toDateString(), 'paid', 300);
        $this->makeSale('C-OLD', now()->subMonths(4)->toDateString(), 'paid', 900);

        $response = $this->get(route('admin.sales.index', [
            'branch_id' => $this->branch->id,
        ]))->assertOk();

        $response->assertViewHas('totalInvoicesCount', 1);
        $this->assertEquals(
            __('sale.stats.period_current_month'),
            $response->viewData('statsPeriodLabel')
        );
    }

    /** وصف الفترة بيتغير ويظهر للمستخدم لما يفلتر. */
    public function test_period_label_reflects_the_active_filter(): void
    {
        $response = $this->get(route('admin.sales.index', [
            'date_from' => '2026-01-01',
            'date_to'   => '2026-01-31',
        ]))->assertOk();

        $label = $response->viewData('statsPeriodLabel');

        $this->assertStringContainsString('2026-01-01', $label);
        $this->assertStringContainsString('2026-01-31', $label);
        $this->assertStringNotContainsString('sale.stats', $label, 'مفتاح ترجمة ناقص');
    }

    // =====================================================
    //  ب.٢
    // =====================================================

    /** chart الفروع بيعرض النشطين واللي في إجازة. */
    public function test_branch_chart_includes_on_leave_employees(): void
    {
        foreach ([['A-1', 'active'], ['A-2', 'active'], ['L-1', 'on_leave']] as [$code, $status]) {
            Employee::create([
                'branch_id' => $this->branch->id, 'job_title_id' => $this->jobTitle->id,
                'name' => $code, 'employee_code' => $code, 'phone' => '0100',
                'status' => $status, 'basic_salary' => 3000,
                'hire_date' => now()->toDateString(),
            ]);
        }

        $row = collect(app(DashboardService::class)->payload()['hr']['by_branch'])
            ->firstWhere('label', $this->branch->name);

        $this->assertNotNull($row, 'الفرع مش ظاهر في الرسم');
        $this->assertEquals(2, $row['active']);
        $this->assertEquals(1, $row['on_leave'], 'الموظف اللي في إجازة مش محسوب');
        $this->assertEquals(3, $row['total']);
    }

    /** المنتهية خدمتهم مايدخلوش الرسم. */
    public function test_branch_chart_excludes_terminated_employees(): void
    {
        Employee::create([
            'branch_id' => $this->branch->id, 'job_title_id' => $this->jobTitle->id,
            'name' => 'Gone', 'employee_code' => 'T-1', 'phone' => '0100',
            'status' => 'terminated', 'basic_salary' => 3000,
            'hire_date' => now()->toDateString(),
        ]);

        $row = collect(app(DashboardService::class)->payload()['hr']['by_branch'])
            ->firstWhere('label', $this->branch->name);

        $this->assertNull($row, 'المنتهية خدمته لازم ما يظهرش في الرسم');
    }
}
