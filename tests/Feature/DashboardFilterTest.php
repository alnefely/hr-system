<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Role;
use App\Models\Sale;
use App\Services\Dashboard\DashboardFilter;
use App\Services\Dashboard\DashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * ج.١–ج.٥ — فلاتر لوحة التحكم.
 *
 * اللوحة كانت مربوطة بالشهر الحالي بشكل ثابت جوه الكود، ومافيش
 * طريقة يشوف بيها المستخدم فترة تانية أو فرع لوحده. وكمان الرسوم
 * كانت بتعرض العملة الغالبة بس من غير ما تقول دي عملة إيه.
 */
class DashboardFilterTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Branch $branch;
    protected Branch $otherBranch;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@filter.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->branch = Branch::create([
            'name' => 'Alpha', 'code' => 'ALP', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $this->otherBranch = Branch::create([
            'name' => 'Beta', 'code' => 'BET', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    private function makeSale(string $number, string $date, float $amount, string $currency, ?int $branchId = null): Sale
    {
        return Sale::create([
            'invoice_number' => $number, 'sale_type' => 'direct_sale',
            'branch_id' => $branchId ?? $this->branch->id, 'sale_date' => $date,
            'subtotal' => $amount, 'total_amount' => $amount, 'currency' => $currency,
            'paid_amount' => $amount, 'due_amount' => 0,
            'payment_status' => 'paid', 'payment_method' => 'cash',
        ]);
    }

    private function filtered(array $params): array
    {
        $filter = DashboardFilter::fromRequest(new Request($params));

        return app(DashboardService::class)->withFilter($filter)->payload();
    }

    // =====================================================
    //  الفلتر نفسه
    // =====================================================

    /** الفترات الجاهزة بترجّع النطاق الصح. */
    public function test_presets_resolve_expected_ranges(): void
    {
        $now = CarbonImmutable::create(2026, 5, 15);

        $lastMonth = DashboardFilter::fromRequest(new Request(['preset' => 'last_month']), $now);
        $this->assertEquals('2026-04-01', $lastMonth->from->format('Y-m-d'));
        $this->assertEquals('2026-04-30', $lastMonth->to->format('Y-m-d'));

        $thisYear = DashboardFilter::fromRequest(new Request(['preset' => 'this_year']), $now);
        $this->assertEquals('2026-01-01', $thisYear->from->format('Y-m-d'));
        $this->assertEquals('2026-12-31', $thisYear->to->format('Y-m-d'));

        $twelve = DashboardFilter::fromRequest(new Request(['preset' => 'last_12_months']), $now);
        $this->assertEquals(12, $twelve->monthsInRange());
    }

    /** فترة مخصصة بتاخد تواريخ المستخدم. */
    public function test_custom_range_uses_supplied_dates(): void
    {
        $filter = DashboardFilter::fromRequest(new Request([
            'preset' => 'custom', 'date_from' => '2026-02-10', 'date_to' => '2026-03-20',
        ]));

        $this->assertEquals('2026-02-10', $filter->from->format('Y-m-d'));
        $this->assertEquals('2026-03-20', $filter->to->format('Y-m-d'));
    }

    /** تواريخ معكوسة بتتصلّح مش بترجّع نتيجة فاضية. */
    public function test_reversed_dates_are_swapped(): void
    {
        $filter = DashboardFilter::fromRequest(new Request([
            'preset' => 'custom', 'date_from' => '2026-06-01', 'date_to' => '2026-01-01',
        ]));

        $this->assertTrue($filter->from->lessThan($filter->to), 'التواريخ المعكوسة مااتصلحتش');
    }

    /** مدخلات غلط ما بتكسرش اللوحة. */
    public function test_invalid_input_falls_back_safely(): void
    {
        $filter = DashboardFilter::fromRequest(new Request([
            'preset' => 'drop table', 'date_from' => 'not-a-date',
        ]));

        $this->assertEquals('this_month', $filter->preset);
        $this->assertNotEmpty($filter->label());
    }

    // =====================================================
    //  ج.٢ — الملخص المالي
    // =====================================================

    /** الملخص المالي بيحترم فلتر الفترة. */
    public function test_financial_summary_respects_period_filter(): void
    {
        $this->makeSale('F-NOW', now()->toDateString(), 1000, 'KWD');
        $this->makeSale('F-OLD', now()->subMonths(5)->toDateString(), 4000, 'KWD');

        $thisMonth = collect($this->filtered(['preset' => 'this_month'])['financial'])
            ->firstWhere('currency', 'KWD');
        $this->assertEquals(1000.0, $thisMonth['revenue']);

        $wholeYear = collect($this->filtered(['preset' => 'this_year'])['financial'])
            ->firstWhere('currency', 'KWD');
        $this->assertEquals(5000.0, $wholeYear['revenue'], 'الفترة الأوسع مالمّتش الفاتورة القديمة');
    }

    /** الملخص المالي بيحترم فلتر الفرع. */
    public function test_financial_summary_respects_branch_filter(): void
    {
        $this->makeSale('B-A', now()->toDateString(), 700, 'BHD', $this->branch->id);
        $this->makeSale('B-B', now()->toDateString(), 300, 'BHD', $this->otherBranch->id);

        $alpha = collect($this->filtered(['branch_id' => $this->branch->id])['financial'])
            ->firstWhere('currency', 'BHD');

        $this->assertEquals(700.0, $alpha['revenue'], 'فلتر الفرع مش شغال');
    }

    // =====================================================
    //  ج.٣ — اتجاه الإيرادات بكل العملات
    // =====================================================

    /** الاتجاه بيرجّع سلسلة لكل عملة مش العملة الغالبة بس. */
    public function test_trend_returns_a_series_per_currency(): void
    {
        $this->makeSale('T-1', now()->toDateString(), 100, 'QAR');
        $this->makeSale('T-2', now()->toDateString(), 200, 'JOD');

        $trend = $this->filtered(['preset' => 'this_month'])['trend'];

        $currencies = array_column($trend['series'], 'currency');

        $this->assertContains('QAR', $currencies);
        $this->assertContains('JOD', $currencies, 'العملة التانية اتشالت من الرسم');
    }

    /** عدد النقاط بيتغيّر مع طول الفترة. */
    public function test_trend_length_follows_the_selected_period(): void
    {
        $this->assertCount(1, $this->filtered(['preset' => 'this_month'])['trend']['labels']);
        $this->assertCount(12, $this->filtered(['preset' => 'last_12_months'])['trend']['labels']);
    }

    // =====================================================
    //  ج.٤ + ج.٥ — العملة مع الرسوم
    // =====================================================

    /** المبيعات حسب القناة بترجع ومعاها العملة. */
    public function test_sales_by_channel_reports_its_currency(): void
    {
        $this->makeSale('CH-1', now()->toDateString(), 500, 'LYD');

        $channels = $this->filtered(['currency' => 'LYD'])['salesChannels'];

        $this->assertEquals('LYD', $channels['currency'], 'العملة مش راجعة مع القنوات');
        $this->assertArrayHasKey('rows', $channels);
    }

    /** توزيع المصروفات بيحترم الفرع وبيرجّع العملة. */
    public function test_expense_split_respects_branch_and_reports_currency(): void
    {
        $category = ExpenseCategory::create(['name' => 'Rent', 'code' => 'RNT', 'is_active' => true]);

        foreach ([[$this->branch->id, 900], [$this->otherBranch->id, 400]] as [$branchId, $amount]) {
            Expense::create([
                'title' => 'X' . $amount, 'expense_category_id' => $category->id,
                'branch_id' => $branchId, 'amount' => $amount,
                'currency' => 'TND', 'expense_date' => now()->toDateString(),
                'payment_method' => 'cash',
            ]);
        }

        $split = $this->filtered([
            'branch_id' => $this->branch->id,
            'currency'  => 'TND',
        ])['expenseSplit'];

        $this->assertEquals('TND', $split['currency']);

        $rent = collect($split['rows'])->firstWhere('label', 'Rent');
        $this->assertEquals(900.0, $rent['total'], 'فلتر الفرع مش متطبّق على المصروفات');
    }

    // =====================================================
    //  ج.١ — مسار AJAX
    // =====================================================

    /** مسار AJAX بيرجّع JSON بالأجزاء المفلترة. */
    public function test_ajax_endpoint_returns_filtered_json(): void
    {
        $this->makeSale('A-1', now()->toDateString(), 250, 'MAD');

        $response = $this->getJson(route('admin.home.data', [
            'preset'   => 'this_month',
            'currency' => 'MAD',
        ]))->assertOk();

        $response->assertJsonStructure([
            'filter'        => ['preset', 'date_from', 'date_to', 'label'],
            'financial',
            'trend'         => ['labels', 'series', 'currency'],
            'expenseSplit'  => ['currency', 'rows'],
            'salesChannels' => ['currency', 'rows'],
            'topCustomers',
        ]);

        $this->assertEquals('MAD', $response->json('salesChannels.currency'));
    }

    /** المسار محمي بنفس صلاحية اللوحة. */
    public function test_ajax_endpoint_requires_permission(): void
    {
        $limited = Role::create(['name' => 'Limited', 'roles' => []]);

        $viewer = Admin::create([
            'name' => 'Viewer', 'email' => 'viewer@filter.test',
            'password' => bcrypt('x'), 'main' => false, 'role_id' => $limited->id,
        ]);

        $this->actingAs($viewer, 'admin')
            ->get(route('admin.home.data'))
            ->assertRedirect();
    }

    /** الفلتر بيوصل للواجهة عشان الفورم يعيد بناء نفسه. */
    public function test_dashboard_view_receives_filter_state(): void
    {
        $response = $this->get(route('admin.home', [
            'preset' => 'last_month',
        ]))->assertOk();

        $this->assertEquals('last_month', $response->viewData('filter')['preset']);
        $response->assertViewHas('branches');
        $response->assertViewHas('currencies');
    }

    /**
     * الرسوم بتعرض كل العملات مع بعض دلوقتي، فالـ JSON لازم يرجّع
     * مجموعة لكل عملة مش عملة واحدة.
     */
    public function test_charts_return_every_currency(): void
    {
        $this->makeSale('MC-1', now()->toDateString(), 100, 'QAR');
        $this->makeSale('MC-2', now()->toDateString(), 200, 'JOD');

        $json = $this->getJson(route('admin.home.data', ['preset' => 'this_month']))
            ->assertOk()
            ->json();

        $currencies = array_column($json['salesChannels']['groups'], 'currency');

        $this->assertContains('QAR', $currencies);
        $this->assertContains('JOD', $currencies, 'العملة التانية اتشالت من القنوات');

        // كل مجموعة جواها صفوفها وإجماليها
        foreach ($json['salesChannels']['groups'] as $group) {
            $this->assertArrayHasKey('rows', $group);
            $this->assertArrayHasKey('total', $group);
        }
    }

    /** كل مفاتيح الاستجابة اللي الواجهة بتقراها موجودة. */
    public function test_ajax_contract_matches_what_the_page_reads(): void
    {
        $json = $this->getJson(route('admin.home.data'))->assertOk()->json();

        $this->assertArrayHasKey('label', $json['filter']);
        $this->assertArrayHasKey('labels', $json['trend']);
        $this->assertArrayHasKey('series', $json['trend']);
        $this->assertArrayHasKey('groups', $json['expenseSplit']);
        $this->assertArrayHasKey('groups', $json['salesChannels']);

        foreach ($json['trend']['series'] as $serie) {
            $this->assertArrayHasKey('currency', $serie);
            $this->assertArrayHasKey('revenue', $serie);
            $this->assertArrayHasKey('expenses', $serie);
        }
    }

    /** الاستجابة JSON صالحة حتى لو الفلاتر كلها فاضية. */
    public function test_empty_filter_values_do_not_break_the_endpoint(): void
    {
        $this->getJson(route('admin.home.data', [
            'preset' => 'this_month', 'date_from' => '', 'date_to' => '',
            'branch_id' => '', 'currency' => '',
        ]))->assertOk()->assertJsonStructure(['filter', 'financial', 'trend']);
    }

    /**
     * عدد السلاسل بيتغيّر بين الفترات (عملات مختلفة في كل فترة).
     *
     * ده كان بيكسر الرسم: ApexCharts بيربط yaxis بالسلاسل بالفهرس،
     * ولما الكود كان بيحدّث الخيارات والسلاسل في نداءين منفصلين
     * كان بيقرأ فهرس مش موجود:
     *   TypeError: Cannot read properties of undefined (reading '0')
     */
    public function test_series_count_changes_between_periods(): void
    {
        // عملة موجودة في الشهر ده بس
        $this->makeSale('SC-NOW', now()->toDateString(), 100, 'AED');

        // وعملة تانية في فترة أقدم
        $this->makeSale('SC-OLD', now()->subMonths(4)->toDateString(), 200, 'KWD');

        $month = $this->getJson(route('admin.home.data', ['preset' => 'this_month']))
            ->assertOk()->json();

        $year = $this->getJson(route('admin.home.data', ['preset' => 'this_year']))
            ->assertOk()->json();

        $monthCurrencies = array_column($month['trend']['series'], 'currency');
        $yearCurrencies  = array_column($year['trend']['series'], 'currency');

        $this->assertContains('AED', $monthCurrencies);
        $this->assertContains('KWD', $yearCurrencies, 'الفترة الأوسع مالمّتش العملة القديمة');

        // ده الشرط اللي كان بيفجّر الخطأ
        $this->assertNotSame(
            count($monthCurrencies),
            count($yearCurrencies),
            'الاختبار مش بيغطي حالة تغيّر عدد السلاسل'
        );
    }

    /** كل مجموعة عملة سلاسلها متساوية الطول مع التسميات. */
    public function test_every_currency_group_is_well_formed(): void
    {
        $this->makeSale('WF-1', now()->toDateString(), 100, 'AED');
        $this->makeSale('WF-2', now()->toDateString(), 250, 'KWD');

        $json = $this->getJson(route('admin.home.data'))->assertOk()->json();

        $points = count($json['trend']['labels']);

        foreach ($json['trend']['series'] as $serie) {
            $this->assertCount($points, $serie['revenue'], 'سلسلة إيرادات ناقصة نقاط');
            $this->assertCount($points, $serie['expenses'], 'سلسلة مصروفات ناقصة نقاط');
        }

        foreach (['expenseSplit', 'salesChannels'] as $key) {
            foreach ($json[$key]['groups'] as $group) {
                $this->assertNotEmpty($group['currency'], "{$key}: مجموعة من غير عملة");
                $this->assertIsArray($group['rows']);

                foreach ($group['rows'] as $row) {
                    $this->assertArrayHasKey('label', $row);
                    $this->assertArrayHasKey('total', $row);
                }
            }
        }
    }
}
