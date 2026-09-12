<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\JobTitle;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * شاشة متابعة مستندات الموظفين.
 *
 * المستندات كانت متفرّقة على ملفات الموظفين، فمفيش مكان واحد يقول
 * إيه اللي قرب ينتهي أو لسه محتاج اعتماد.
 *
 * وخاصية "ينتهي قريباً" كانت مكسورة: diffInDays(now()) بترجّع رقم
 * سالب للتواريخ المستقبلية، والشرط "<= 30" بيتحقق على أي سالب —
 * فمستند بينتهي سنة 2030 كان بيتحسب "قرب ينتهي".
 */
class DocumentsMonitorTest extends TestCase
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
            'name' => 'Root', 'email' => 'root@docs.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->branch = Branch::create([
            'name' => 'HQ', 'code' => 'DOCM', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $job = JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        $this->employee = Employee::create([
            'branch_id' => $this->branch->id, 'job_title_id' => $job->id,
            'name' => 'أحمد', 'employee_code' => 'DOCM-1', 'phone' => '0100',
            'status' => 'active', 'basic_salary' => 3000,
            'hire_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    private function makeDoc(array $attributes = []): EmployeeDocument
    {
        return $this->employee->documents()->create(array_merge([
            'document_type' => 'passport',
            'title'         => 'مستند',
            'file_path'     => '/files/x.pdf',
            'is_verified'   => true,
        ], $attributes));
    }

    // =====================================================
    //  الخاصية المكسورة
    // =====================================================

    /**
     * "ينتهي قريباً" بتحترم حدود النطاق.
     *
     * قبل الإصلاح كان أي تاريخ مستقبلي بيرجّع true.
     */
    public function test_expiring_soon_respects_the_window(): void
    {
        $cases = [
            10   => true,
            30   => true,
            31   => false,
            365  => false,
            1460 => false,
        ];

        foreach ($cases as $days => $expected) {
            $doc = new EmployeeDocument(['expiry_date' => now()->addDays($days)]);

            $this->assertSame(
                $expected,
                $doc->is_expiring_soon,
                "مستند بينتهي بعد {$days} يوم: النتيجة غلط"
            );
        }
    }

    /** المستند المنتهي مش محسوب "قرب ينتهي". */
    public function test_an_expired_document_is_not_expiring_soon(): void
    {
        $doc = new EmployeeDocument(['expiry_date' => now()->subDays(5)]);

        $this->assertTrue($doc->is_expired);
        $this->assertFalse($doc->is_expiring_soon, 'المنتهي محسوب قرب ينتهي');
        $this->assertSame(-5, $doc->days_until_expiry);
    }

    /** المستند اللي مالوش تاريخ انتهاء مالوش حالة. */
    public function test_a_document_without_expiry_has_no_status(): void
    {
        $doc = new EmployeeDocument(['expiry_date' => null]);

        $this->assertFalse($doc->is_expired);
        $this->assertFalse($doc->is_expiring_soon);
        $this->assertNull($doc->days_until_expiry);
    }

    // =====================================================
    //  الشاشة
    // =====================================================

    /** الشاشة بتفتح لصاحب الصلاحية. */
    public function test_screen_is_accessible(): void
    {
        $this->get(route('admin.reports.documents'))->assertOk();
    }

    /** ومن غير صلاحية مافيش دخول. */
    public function test_screen_requires_permission(): void
    {
        $limited = Role::create(['name' => 'Limited', 'roles' => ['dashboard_show']]);

        $viewer = Admin::create([
            'name' => 'Viewer', 'email' => 'viewer@docs.test',
            'password' => bcrypt('x'), 'main' => false, 'role_id' => $limited->id,
        ]);

        $this->actingAs($viewer, 'admin')
            ->get(route('admin.reports.documents'))
            ->assertRedirect();
    }

    /** كل حالة بترجّع مستنداتها بس. */
    public function test_each_status_returns_only_its_documents(): void
    {
        $expiring = $this->makeDoc(['expiry_date' => now()->addDays(10)]);
        $expired  = $this->makeDoc(['expiry_date' => now()->subDays(5)]);
        $pending  = $this->makeDoc(['is_verified' => false]);

        // بعيد الانتهاء — مالوش مكان في أي حالة
        $far = $this->makeDoc(['expiry_date' => now()->addDays(300)]);

        foreach ([
            'expiring' => $expiring,
            'expired'  => $expired,
            'pending'  => $pending,
        ] as $status => $expectedDoc) {
            $ids = $this->get(route('admin.reports.documents', ['status' => $status]))
                ->assertOk()
                ->viewData('documents')
                ->pluck('id');

            $this->assertContains($expectedDoc->id, $ids->all(), "الحالة «{$status}»: المستند المطابق مش راجع");
            $this->assertNotContains($far->id, $ids->all(), "الحالة «{$status}»: رجّع مستند بعيد الانتهاء");
        }
    }

    /** العرض الافتراضي بيجمع كل اللي محتاج انتباه. */
    public function test_default_view_collects_everything_needing_attention(): void
    {
        $expiring = $this->makeDoc(['expiry_date' => now()->addDays(10)]);
        $expired  = $this->makeDoc(['expiry_date' => now()->subDays(5)]);
        $pending  = $this->makeDoc(['is_verified' => false]);
        $far      = $this->makeDoc(['expiry_date' => now()->addDays(300)]);

        $ids = $this->get(route('admin.reports.documents'))
            ->assertOk()
            ->viewData('documents')
            ->pluck('id');

        foreach ([$expiring, $expired, $pending] as $doc) {
            $this->assertContains($doc->id, $ids->all(), 'مستند محتاج انتباه مش ظاهر');
        }

        $this->assertNotContains($far->id, $ids->all(), 'مستند سليم ظاهر في قائمة الانتباه');
    }

    /** مدة التنبيه بتغيّر اللي بيتحسب "قرب ينتهي". */
    public function test_the_alert_window_is_configurable(): void
    {
        $doc = $this->makeDoc(['expiry_date' => now()->addDays(60)]);

        $within30 = $this->get(route('admin.reports.documents', ['status' => 'expiring']))
            ->assertOk()->viewData('documents')->pluck('id');

        $this->assertNotContains($doc->id, $within30->all(), '60 يوم ظهرت في نطاق 30');

        $within90 = $this->get(route('admin.reports.documents', ['status' => 'expiring', 'days' => 90]))
            ->assertOk()->viewData('documents')->pluck('id');

        $this->assertContains($doc->id, $within90->all(), '60 يوم مش ظاهرة في نطاق 90');
    }

    /** المدة بتتحبس في نطاق معقول. */
    public function test_the_alert_window_is_clamped(): void
    {
        $this->assertEquals(
            365,
            $this->get(route('admin.reports.documents', ['days' => 9999]))->assertOk()->viewData('days')
        );

        $this->assertEquals(
            1,
            $this->get(route('admin.reports.documents', ['days' => 0]))->assertOk()->viewData('days')
        );
    }

    /**
     * العدّادات بتعكس كل حالة.
     *
     * السيدرز فيها مستندات غير معتمدة كمان، فبنقيس الفرق اللي
     * مستنداتنا عملته مش الإجمالي.
     */
    public function test_counters_reflect_each_status(): void
    {
        $before = $this->get(route('admin.reports.documents'))->assertOk()->viewData('counts');

        $this->makeDoc(['expiry_date' => now()->addDays(10)]);
        $this->makeDoc(['expiry_date' => now()->subDays(5)]);
        $this->makeDoc(['is_verified' => false]);

        $after = $this->get(route('admin.reports.documents'))->assertOk()->viewData('counts');

        foreach (['expiring', 'expired', 'pending'] as $key) {
            $this->assertEquals(
                $before[$key] + 1,
                $after[$key],
                "العدّاد «{$key}» مازادش بمستند واحد"
            );
        }
    }

    /** فلتر الفرع بيشتغل على فرع الموظف. */
    public function test_branch_filter_uses_the_employees_branch(): void
    {
        $other = Branch::create([
            'name' => 'فرع تاني', 'code' => 'DOCM-2', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $otherEmployee = Employee::create([
            'branch_id' => $other->id,
            'job_title_id' => JobTitle::first()->id,
            'name' => 'سعيد', 'employee_code' => 'DOCM-9', 'phone' => '0101',
            'status' => 'active', 'basic_salary' => 3000,
            'hire_date' => now()->toDateString(),
        ]);

        $mine   = $this->makeDoc(['is_verified' => false]);
        $theirs = $otherEmployee->documents()->create([
            'document_type' => 'passport', 'title' => 'مستند', 'file_path' => '/y.pdf',
            'is_verified' => false,
        ]);

        $ids = $this->get(route('admin.reports.documents', ['branch_id' => $this->branch->id]))
            ->assertOk()->viewData('documents')->pluck('id');

        $this->assertContains($mine->id, $ids->all());
        $this->assertNotContains($theirs->id, $ids->all(), 'فلتر الفرع رجّع مستند فرع تاني');
    }

    /** الشاشة بتعرض بيانات المستند مش مفاتيح خام. */
    public function test_screen_renders_translated_labels(): void
    {
        $this->makeDoc(['expiry_date' => now()->addDays(10), 'title' => 'جواز السفر']);

        $html = $this->get(route('admin.reports.documents'))->assertOk()->getContent();

        $this->assertStringContainsString('جواز السفر', $html);
        $this->assertStringContainsString($this->employee->name, $html);

        $this->assertDoesNotMatchRegularExpression(
            '/\b(report|document)\.(doc_status|doc_hints|doc_filters|types|fields)\.[a-z_]+/',
            $html,
            'فيه مفتاح ترجمة خام ظاهر'
        );
    }

    /** الصلاحية والرابط مسجّلين. */
    public function test_permission_and_menu_are_registered(): void
    {
        $this->assertContains('report_documents', all_roles(), 'الصلاحية مش مسجّلة');

        $this->assertNotEquals(
            'role.action.documents',
            __('role.action.documents'),
            'تسمية الصلاحية مش مترجمة'
        );
    }

    /** الاعتماد بيسأل تأكيد قبل ما ينفّذ. */
    public function test_verification_asks_for_confirmation(): void
    {
        $this->makeDoc(['is_verified' => false, 'title' => 'جواز السفر']);

        $html = $this->get(route('admin.reports.documents'))->assertOk()->getContent();

        $this->assertStringContainsString('btn-swal-confirm', $html, 'زرار الاعتماد مش بيسأل تأكيد');

        // الرسالة بتذكر المستند والموظف مش نص عام
        $this->assertStringContainsString('جواز السفر', $html);
        $this->assertStringContainsString($this->employee->name, $html);

        $this->assertStringNotContainsString(
            'document.confirm.',
            $html,
            'مفتاح ترجمة خام ظاهر في رسالة التأكيد'
        );
    }

    /**
     * التأكيدات بتستخدم أسماء الخصائص اللي المعالج بيقراها.
     *
     * المعالج في alert.blade.php بيقرا data-title و data-text،
     * وشاشة ملف الموظف كانت بتمرّر data-swal-* — فالتأكيد كان
     * بيظهر بالنص الافتراضي العام بدل الرسالة المكتوبة.
     */
    public function test_confirm_dialogs_use_the_supported_attributes(): void
    {
        $offenders = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            if (str_contains(file_get_contents($file->getPathname()), 'data-swal-')) {
                $offenders[] = str_replace(resource_path('views/'), '', $file->getPathname());
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "شاشات بتستخدم data-swal-* واللي المعالج مابيقراهاش:
" . implode("
", $offenders)
        );
    }

    /** النتائج بتتقسّم على صفحات والفلتر بيتحفظ بينها. */
    public function test_results_are_paginated_and_keep_the_filter(): void
    {
        for ($i = 0; $i < 45; $i++) {
            $this->makeDoc(['is_verified' => false, 'title' => 'مستند ' . $i]);
        }

        $first = $this->get(route('admin.reports.documents', ['status' => 'pending']))->assertOk();
        $page  = $first->viewData('documents');

        $this->assertGreaterThan(1, $page->lastPage(), 'النتائج مش متقسّمة على صفحات');
        $this->assertEquals($page->perPage(), $page->count(), 'الصفحة الأولى مش مليانة');

        // روابط الترقيم ظاهرة
        $this->assertStringContainsString('pagination', $first->getContent(), 'روابط الترقيم مش ظاهرة');

        // والصفحة التانية بتفضل على نفس الفلتر
        $second = $this->get(route('admin.reports.documents', [
            'status' => 'pending',
            'page'   => 2,
        ]))->assertOk();

        $this->assertEquals('pending', $second->viewData('status'), 'الفلتر ضاع في الصفحة التانية');
        $this->assertNotEmpty($second->viewData('documents')->items(), 'الصفحة التانية فاضية');
    }
}
