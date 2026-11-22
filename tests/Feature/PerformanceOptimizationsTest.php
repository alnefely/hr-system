<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\JobTitle;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Settings;
use App\Services\Cache\CacheService;
use App\Services\Cache\Keys\RoleCache;
use App\Services\Dashboard\DashboardService;
use App\Services\Export\ExcelExportService;
use App\Services\Notification\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * تحسينات الأداء: كاش، تجميع استعلامات، ونطاقات تاريخ بدل whereDate.
 * كل اختبار بيتأكد إن النتيجة زي ما هي وإن عدد الاستعلامات قلّ.
 */
class PerformanceOptimizationsTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Role $role;
    protected Branch $branch;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->role = Role::create(['name' => 'Full', 'roles' => all_roles()]);
        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@perf.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $this->role->id,
        ]);

        $this->branch = Branch::create([
            'name' => 'HQ', 'code' => 'PRF', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'branch_id' => $this->branch->id,
            'job_title_id' => JobTitle::create(['name' => 'Staff', 'is_active' => true])->id,
            'name' => 'Worker', 'employee_code' => 'PRF-1', 'phone' => '0100',
            'status' => 'active', 'basic_salary' => 3000, 'hire_date' => '2024-01-01',
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    private function countQueries(\Closure $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    // ========================================================
    //  الأدوار
    // ========================================================

    /** حفظ المشرف من غير تغيير الدور (زي remember_token) مايلغيش كاش الأدوار. */
    public function test_admin_save_without_role_change_keeps_role_cache(): void
    {
        $cache = app(CacheService::class);
        $cache->put(RoleCache::class, 'probe', 'cached');

        $this->admin->forceFill(['remember_token' => 'abc'])->save();
        $this->assertSame('cached', $cache->get(RoleCache::class, 'probe'));

        $this->admin->update(['role_id' => Role::create(['name' => 'Other', 'roles' => []])->id]);
        $this->assertNull($cache->get(RoleCache::class, 'probe'), 'تغيير الدور المفروض يلغي الكاش');
    }

    /** اسم الدور في النافبار من الكاش — من غير lazy load في كل صفحة. */
    public function test_role_name_is_served_from_cache(): void
    {
        $admin = Admin::find($this->admin->id);
        $this->assertSame('Full', $admin->role_name);

        $fresh = Admin::find($this->admin->id);
        $this->assertSame(0, $this->countQueries(fn () => $fresh->role_name));
        $this->assertFalse($fresh->relationLoaded('role'));

        $this->role->update(['name' => 'Renamed']);
        $this->assertSame('Renamed', Admin::find($this->admin->id)->role_name);
    }

    // ========================================================
    //  الإعدادات
    // ========================================================

    /** كل الإعدادات في مفتاح كاش واحد: استعلام واحد لأي عدد من المفاتيح. */
    public function test_settings_are_loaded_with_a_single_query(): void
    {
        Settings::create(['key' => 'perf_a', 'group' => 'general', 'value' => 'A', 'type' => 'text']);
        Settings::create(['key' => 'perf_b', 'group' => 'general', 'value' => null, 'type' => 'text']);

        $queries = $this->countQueries(function () {
            $this->assertSame('A', Settings::get('perf_a'));
            $this->assertSame('fallback', Settings::get('perf_b', 'fallback'));
            $this->assertSame('missing', Settings::get('perf_missing', 'missing'));
        });
        $this->assertSame(1, $queries);

        Settings::where('key', 'perf_a')->first()->update(['value' => 'A2']);
        $this->assertSame('A2', Settings::get('perf_a'), 'الحفظ المفروض يلغي الكاش');
    }

    // ========================================================
    //  سجل الحركات
    // ========================================================

    /** نوع وهمي عشان سجلات الـ setUp (الفرع والموظف) ماتدخلش في العد. */
    private const PROBE = 'Tests\\Probe';

    private function log(string $event, string $createdAt): void
    {
        DB::table('activity_logs')->insert([
            'event' => $event, 'subject_type' => self::PROBE, 'subject_id' => 1,
            'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);
    }

    /** فلتر التاريخ شامل يوم النهاية كله (نطاق بدل whereDate). */
    public function test_activity_log_date_range_includes_whole_end_day(): void
    {
        $this->log('created', '2026-11-09 23:59:59');
        $this->log('created', '2026-11-10 00:00:00');
        $this->log('updated', '2026-11-11 23:59:59');
        $this->log('deleted', '2026-11-12 00:00:00');

        $this->assertSame(2, ActivityLog::forSubjectType(self::PROBE)->betweenDates('2026-11-10', '2026-11-11')->count());
        $this->assertSame(4, ActivityLog::forSubjectType(self::PROBE)->betweenDates('garbage', null)->count());
    }

    /** إحصائيات الشاشة من استعلام واحد. */
    public function test_activity_log_stats_use_one_query(): void
    {
        $this->log('created', now()->toDateTimeString());
        $this->log('updated', now()->subDays(3)->toDateTimeString());
        $this->log('force_deleted', now()->subDays(3)->toDateTimeString());

        $controller = app(\App\Http\Controllers\Admin\ActivityLogController::class);
        $method = new \ReflectionMethod($controller, 'stats');

        $stats = null;
        $queries = $this->countQueries(function () use ($method, $controller, &$stats) {
            $stats = $method->invoke($controller, self::PROBE);
        });

        $this->assertSame(1, $queries);
        $this->assertSame(['total' => 3, 'today' => 1, 'created' => 1, 'updated' => 1, 'deleted' => 1], $stats);
    }

    // ========================================================
    //  المستندات
    // ========================================================

    public function test_document_expiry_scopes_include_boundary_days(): void
    {
        $make = fn (?string $date) => EmployeeDocument::create([
            'employee_id' => $this->employee->id, 'document_type' => 'id',
            'file_path' => 'x.pdf', 'expiry_date' => $date,
        ]);

        $today    = $make(now()->toDateString());
        $boundary = $make(now()->addDays(30)->toDateString());
        $make(now()->addDays(31)->toDateString());
        $expired  = $make(now()->subDay()->toDateString());

        $this->assertEqualsCanonicalizing(
            [$today->id, $boundary->id],
            EmployeeDocument::expiringSoon(30)->pluck('id')->all()
        );
        $this->assertSame([$expired->id], EmployeeDocument::expired()->pluck('id')->all());
    }

    // ========================================================
    //  المسيرات
    // ========================================================

    public function test_payroll_totals_are_recalculated_in_one_query(): void
    {
        $payroll = Payroll::create([
            'title' => 'Nov', 'year' => 2026, 'month' => 11,
            'branch_id' => $this->branch->id, 'status' => 'draft',
        ]);

        PayrollItem::create([
            'payroll_id' => $payroll->id, 'employee_id' => $this->employee->id,
            'basic_salary' => 3000, 'allowances' => 500, 'deductions' => 200, 'net_salary' => 3300,
        ]);

        $queries = $this->countQueries(fn () => $payroll->recalculateTotals());

        // SUM واحد + UPDATE واحد
        $this->assertSame(2, $queries);
        $payroll->refresh();
        $this->assertEquals(3000, (float) $payroll->total_basic_salary);
        $this->assertEquals(500, (float) $payroll->total_allowances);
        $this->assertEquals(200, (float) $payroll->total_deductions);
        $this->assertEquals(3300, (float) $payroll->total_net_salary);
    }

    // ========================================================
    //  لوحة التحكم
    // ========================================================

    private function sale(string $number, float $total): void
    {
        Sale::create([
            'invoice_number' => $number, 'sale_type' => 'direct_sale',
            'branch_id' => $this->branch->id, 'sale_date' => now()->toDateString(),
            'subtotal' => $total, 'total_amount' => $total, 'currency' => 'EGP',
            'paid_amount' => $total, 'due_amount' => 0,
            'payment_status' => 'paid', 'payment_method' => 'cash',
        ]);
    }

    /** اللوحة متكاشة، والفاتورة الجديدة بتلغي الكاش. */
    public function test_dashboard_payload_is_cached_and_invalidated_by_sales(): void
    {
        $this->sale('P-1', 100);

        $first = (new DashboardService())->payload();

        // ولا استعلام: الأرقام من الكاش، وعدّاد الإشعارات متذكّر في نفس الطلب
        $this->assertSame(0, $this->countQueries(fn () => (new DashboardService())->payload()));

        $this->sale('P-2', 50);
        $second = (new DashboardService())->payload();

        $this->assertNotEquals($first['financial'], $second['financial'], 'الكاش مااتلغاش بعد فاتورة جديدة');
    }

    /** عدّاد الإشعارات برّه الكاش — خاص بكل مشرف. */
    public function test_dashboard_unread_count_is_not_cached(): void
    {
        $this->assertSame(0, (new DashboardService())->payload()['pending']['unread_notifications']);

        AdminNotification::create([
            'admin_id' => $this->admin->id,
            'type' => NotificationType::LeaveRequested->value, 'title' => 'x',
        ]);
        // طلب جديد = ذاكرة الطلب جديدة
        $this->app->instance('request', \Illuminate\Http\Request::create('/admin'));

        $this->assertSame(1, (new DashboardService())->payload()['pending']['unread_notifications']);
    }

    /** العدّاد بيتحسب مرة واحدة في الطلب (النافبار + الرئيسية). */
    public function test_unread_count_is_memoized_per_request(): void
    {
        NotificationService::unreadCountForRequest($this->admin->id);

        $this->assertSame(0, $this->countQueries(fn () => NotificationService::unreadCountForRequest($this->admin->id)));
        $this->assertSame(0, NotificationService::unreadCountForRequest(null));
    }

    // ========================================================
    //  الإشعارات
    // ========================================================

    /** المشرفين بيتحمّلوا مرة واحدة لكل نوع، والفرع بيتفلتر في PHP. */
    public function test_eligible_recipients_loaded_once_per_type_across_branches(): void
    {
        $other = Branch::create([
            'name' => 'Other', 'code' => 'PRO', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $branchAdmin = Admin::create([
            'name' => 'Branch', 'email' => 'branch@perf.test', 'password' => bcrypt('x'),
            'main' => false, 'role_id' => $this->role->id, 'employee_id' => $this->employee->id,
        ]);

        $service = app(NotificationService::class);
        $type = NotificationType::LeaveRequested;

        $mine = $service->eligibleRecipients($type, $this->branch->id)->pluck('id')->all();

        $queries = $this->countQueries(function () use ($service, $type, $other, &$theirs, &$excluded) {
            $theirs   = $service->eligibleRecipients($type, $other->id)->pluck('id')->all();
            $excluded = $service->eligibleRecipients($type, $this->branch->id, $this->admin->id)->pluck('id')->all();
        });

        $this->assertSame(0, $queries);
        // (ممكن يكون فيه مشرفين متزرعين من الـ seed — بنشيّك على بتوعنا بس)
        $this->assertContains($this->admin->id, $mine);
        $this->assertContains($branchAdmin->id, $mine);
        $this->assertContains($this->admin->id, $theirs);
        $this->assertNotContains($branchAdmin->id, $theirs, 'مشرف الفرع وصله إشعار فرع تاني');
        $this->assertContains($branchAdmin->id, $excluded);
        $this->assertNotContains($this->admin->id, $excluded);
    }

    // ========================================================
    //  التصدير والفهارس والإعدادات
    // ========================================================

    public function test_excel_export_streams_a_valid_xlsx(): void
    {
        $response = ExcelExportService::download('Title', ['#', 'Name'], [[1, str_repeat('x', 30)], [2, 'y']]);

        $this->assertSame(200, $response->getStatusCode());

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringStartsWith('PK', $content, 'الملف مش xlsx');
        $this->assertSame(5000, ExcelExportService::MAX_ROWS);
    }

    public function test_performance_indexes_exist(): void
    {
        $this->assertTrue(Schema::hasIndex('employee_salaries', ['employee_id', 'effective_date']));
        $this->assertTrue(Schema::hasIndex('employee_documents', ['expiry_date']));
        $this->assertTrue(Schema::hasIndex('employee_documents', ['is_verified']));
        $this->assertTrue(Schema::hasIndex('vendor_subscriptions', ['status', 'next_billing_date']));
        $this->assertTrue(Schema::hasIndex('employees', ['hire_date']));
        $this->assertTrue(Schema::hasIndex('payrolls', ['status']));
        $this->assertTrue(Schema::hasIndex('employee_leave_balances', ['year']));
        $this->assertTrue(Schema::hasIndex('settings', ['key']));
    }

    public function test_activity_writes_are_queued_by_default(): void
    {
        $config = require config_path('activity.php');

        // من غير متغير البيئة الافتراضي true (والاختبارات sync فبيتجاهل)
        if (env('ACTIVITY_LOG_QUEUE_WRITES') === null) {
            $this->assertTrue($config['queue_writes']);
        }

        $this->assertSame('sync', config('queue.default'));
    }
}
