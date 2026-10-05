<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Link;
use App\Models\LinkClick;
use App\Models\LinkDailyStat;
use App\Models\Role;
use App\Support\BranchScopeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * التجميع اليومي وشاشة الإحصائيات.
 */
class LinkStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        BranchScopeResolver::forget();
        parent::tearDown();
    }

    private function admin(array $permissions, ?int $employeeId = null): Admin
    {
        $role = Role::create(['name' => 'R' . uniqid(), 'roles' => $permissions]);

        return Admin::create([
            'name'        => 'A' . uniqid(),
            'email'       => uniqid() . '@stats.test',
            'password'    => bcrypt('x'),
            'main'        => false,
            'role_id'     => $role->id,
            'employee_id' => $employeeId,
        ]);
    }

    private function link(array $attributes = []): Link
    {
        return Link::create(array_merge([
            'short_code'   => 'Sts1234',
            'original_url' => 'https://example.com',
            'is_active'    => true,
        ], $attributes));
    }

    private function click(Link $link, array $attributes = []): LinkClick
    {
        return LinkClick::create(array_merge([
            'link_id'      => $link->id,
            'visitor_hash' => 'v1',
            'is_bot'       => false,
            'clicked_at'   => now()->subDay(),
        ], $attributes));
    }

    /** التجميع بيلخّص نقرات اليوم. */
    public function test_aggregate_rolls_up_clicks(): void
    {
        $link = $this->link();

        $this->click($link, ['visitor_hash' => 'a', 'country_code' => 'SA', 'device_type' => 'mobile']);
        $this->click($link, ['visitor_hash' => 'b', 'country_code' => 'EG', 'device_type' => 'desktop']);

        $this->artisan('links:aggregate')->assertSuccessful();

        $stat = LinkDailyStat::where('link_id', $link->id)->first();

        $this->assertNotNull($stat, 'مااتعملش تلخيص');
        $this->assertEquals(2, $stat->clicks);
        $this->assertEquals(2, $stat->unique_clicks);
        $this->assertEquals(['SA' => 1, 'EG' => 1], $stat->by_country);
        $this->assertEquals(['mobile' => 1, 'desktop' => 1], $stat->by_device);
    }

    /** التشغيل تاني بيصحّح مش بيكرر. */
    public function test_aggregate_is_idempotent(): void
    {
        $link = $this->link();
        $this->click($link);

        $this->artisan('links:aggregate');
        $this->artisan('links:aggregate');
        $this->artisan('links:aggregate');

        $this->assertEquals(1, LinkDailyStat::count(), 'اتعمل أكتر من صف لنفس اليوم');
        $this->assertEquals(1, LinkDailyStat::first()->clicks, 'الأرقام اتضاعفت');
    }

    /** البوتات في عمود منفصل مش مخلوطة بالبشر. */
    public function test_aggregate_separates_bots(): void
    {
        $link = $this->link();

        $this->click($link, ['visitor_hash' => 'human']);
        $this->click($link, ['visitor_hash' => 'bot1', 'is_bot' => true, 'bot_name' => 'WhatsApp', 'country_code' => 'SA']);
        $this->click($link, ['visitor_hash' => 'bot2', 'is_bot' => true, 'bot_name' => 'Google', 'country_code' => 'SA']);

        $this->artisan('links:aggregate');

        $stat = LinkDailyStat::first();

        $this->assertEquals(1, $stat->clicks, 'البوتات اتحسبت نقرات');
        $this->assertEquals(2, $stat->bot_clicks, 'البوتات ماتعدّتش');

        // والتوزيعات بتوصف البشر بس
        $this->assertNull($stat->by_country, 'بلد البوت دخل التوزيع');
    }

    /** الزائر المتكرر مرة واحدة في العد الفريد. */
    public function test_aggregate_counts_unique_visitors_once(): void
    {
        $link = $this->link();

        $this->click($link, ['visitor_hash' => 'same']);
        $this->click($link, ['visitor_hash' => 'same']);
        $this->click($link, ['visitor_hash' => 'same']);
        $this->click($link, ['visitor_hash' => 'other']);

        $this->artisan('links:aggregate');

        $stat = LinkDailyStat::first();

        $this->assertEquals(4, $stat->clicks);
        $this->assertEquals(2, $stat->unique_clicks, 'الزوار الفريدون اتضخّموا');
    }

    /**
     * اليوم الجاري بيتلخّص فوراً.
     *
     * التلخيص بيشتغل كل ٥ دقايق، فنقرة النهارده لازم تبان في
     * الإحصائيات من غير ما المستخدم يستنى بكرة.
     */
    public function test_aggregate_includes_today(): void
    {
        $link = $this->link();

        $this->click($link, ['clicked_at' => now()]);

        $this->artisan('links:aggregate')->assertSuccessful();

        $stat = LinkDailyStat::where('date', now()->toDateString())->first();

        $this->assertNotNull($stat, 'نقرة النهارده مااتلخّصتش');
        $this->assertEquals(1, $stat->clicks);
    }

    /**
     * وإعادة التشغيل بتصحّح صف النهارده مش بتكرره.
     *
     * دي الضمانة اللي بتخلي التشغيل كل ٥ دقايق آمن: نقرة جديدة
     * بتتضاف للرقم، والصف بيفضل واحد.
     */
    public function test_repeated_runs_refresh_today_without_duplicating(): void
    {
        $link = $this->link();

        $this->click($link, ['visitor_hash' => 'a', 'clicked_at' => now()]);
        $this->artisan('links:aggregate');

        // نقرة تانية وصلت بعد أول تلخيص
        $this->click($link, ['visitor_hash' => 'b', 'clicked_at' => now()]);
        $this->artisan('links:aggregate');
        $this->artisan('links:aggregate');

        $this->assertEquals(1, LinkDailyStat::count(), 'اتعمل صف مكرر لنفس اليوم');

        $stat = LinkDailyStat::first();

        $this->assertEquals(2, $stat->clicks, 'النقرة الجديدة ماظهرتش');
        $this->assertEquals(2, $stat->unique_clicks);
    }

    /**
     * التاريخ بيفضل بعد حذف النقرات الخام.
     *
     * ده سبب وجود الجدول التجميعي أصلاً.
     */
    public function test_aggregated_data_survives_prune(): void
    {
        $link = $this->link();

        $this->click($link, ['clicked_at' => now()->subDays(100)]);

        $this->artisan('links:aggregate --date=' . now()->subDays(100)->toDateString());

        $this->assertEquals(1, LinkDailyStat::count());

        $this->artisan('links:prune --days=90');

        $this->assertEquals(0, LinkClick::count(), 'الخام مااتحذفش');
        $this->assertEquals(1, LinkDailyStat::count(), 'التلخيص ضاع مع الخام');
    }

    /**
     * الشاشة مابتعدّش من جدول النقرات.
     *
     * لو رجع الـ COUNT(*) المباشر، الصفحة بتنهار بعد ملايين الصفوف.
     */
    public function test_stats_screen_does_not_query_clicks_table(): void
    {
        $link = $this->link();
        $this->click($link);
        $this->artisan('links:aggregate');

        $this->actingAs($this->admin(['link_show', 'link_stats']), 'admin');

        $touchedClicks = false;
        DB::listen(function ($query) use (&$touchedClicks) {
            if (str_contains(strtolower($query->sql), 'link_clicks')) {
                $touchedClicks = true;
            }
        });

        $this->get(route('admin.links.stats', $link->id))->assertOk();

        $this->assertFalse($touchedClicks, 'الشاشة استعلمت من link_clicks');
    }

    /** الشاشة بتعرض الأرقام المجمّعة. */
    public function test_stats_screen_shows_aggregated_numbers(): void
    {
        $link = $this->link();

        LinkDailyStat::create([
            'link_id'       => $link->id,
            'date'          => now()->subDay()->toDateString(),
            'clicks'        => 42,
            'unique_clicks' => 17,
            'bot_clicks'    => 5,
            'by_country'    => ['SA' => 30, 'EG' => 12],
        ]);

        $this->actingAs($this->admin(['link_show', 'link_stats']), 'admin');

        $response = $this->get(route('admin.links.stats', $link->id));

        $response->assertOk();
        $response->assertSee('42');
        $response->assertSee('17');
        $response->assertSee('SA');
    }

    /** الصلاحية مطلوبة. */
    public function test_stats_requires_permission(): void
    {
        $link = $this->link();

        $this->actingAs($this->admin(['link_show']), 'admin');

        $response = $this->get(route('admin.links.stats', $link->id));

        $this->assertNotEquals(200, $response->getStatusCode(), 'الشاشة اتفتحت من غير صلاحية');
    }

    /** وعزل الفروع بيسري على الشاشة كمان. */
    public function test_stats_respects_branch_scope(): void
    {
        $job = JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        $mine = Branch::create(['name' => 'M', 'code' => 'SM', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $other = Branch::create(['name' => 'O', 'code' => 'SO', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);

        $employee = Employee::create([
            'branch_id' => $mine->id, 'job_title_id' => $job->id,
            'name' => 'E', 'employee_code' => 'STS-1', 'phone' => '01',
            'status' => 'active', 'basic_salary' => 1000,
            'hire_date' => now()->subYear()->toDateString(),
        ]);

        $theirs = $this->link(['short_code' => 'Othr123', 'branch_id' => $other->id]);

        BranchScopeResolver::forget();
        $this->actingAs($this->admin(['link_show', 'link_stats'], $employee->id), 'admin');

        $this->get(route('admin.links.stats', $theirs->id))->assertNotFound();
    }
}
