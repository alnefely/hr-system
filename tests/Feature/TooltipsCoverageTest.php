<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\JobTitle;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * هـ.٢–هـ.٥ — التلميحات ومكوّن الهاتف.
 *
 * كان في حقول كتير المستخدم مش فاهم وظيفتها، وهواتف العميل كانت
 * input نصي عادي من غير تحقق ولا كود دولة — عكس شاشة الموظف.
 */
class TooltipsCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@tip.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->branch = Branch::create([
            'name' => 'HQ', 'code' => 'TIP', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        JobTitle::create(['name' => 'Staff', 'is_active' => true]);
        ExpenseCategory::create(['name' => 'Office', 'code' => 'OFF', 'is_active' => true]);

        $this->actingAs($this->admin, 'admin');
    }

    // =====================================================
    //  هـ.٢ — مكتبة الهاتف
    // =====================================================

    /** هواتف العميل بتستخدم نفس مكوّن هاتف الموظف. */
    public function test_customer_form_uses_the_phone_component(): void
    {
        foreach (['create', 'edit'] as $action) {
            $url = $action === 'create'
                ? route('admin.customers.create')
                : route('admin.customers.edit', Customer::create([
                    'code' => 'C-PH', 'name' => 'عميل', 'customer_type' => 'company', 'is_active' => true,
                ])->id);

            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('data-iti-phone', $html, "شاشة {$action}: مكوّن الهاتف مش مستخدم");
            $this->assertMatchesRegularExpression(
                '/name="phone"[^>]*data-iti-phone|data-iti-phone[^>]*name="phone"/',
                $html,
                "شاشة {$action}: الهاتف الأساسي مش على المكوّن"
            );
            $this->assertStringContainsString('secondary_phone', $html);
        }
    }

    /** حقل كود الدولة بيتبعت مع الهاتف. */
    public function test_customer_phone_posts_a_country_field(): void
    {
        $html = $this->get(route('admin.customers.create'))->assertOk()->getContent();

        $this->assertStringContainsString('name="phone_country"', $html);
        $this->assertStringContainsString('name="secondary_phone_country"', $html);
    }

    /** العمود بيقبل الصيغة الدولية الكاملة. */
    public function test_customer_accepts_international_number(): void
    {
        $this->post(route('admin.customers.store'), [
            'code' => 'C-INTL', 'name' => 'عميل', 'customer_type' => 'company',
            'phone' => '+201021464469', 'phone_country' => 'EG',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'code' => 'C-INTL', 'phone' => '+201021464469', 'phone_country' => 'EG',
        ]);
    }

    // =====================================================
    //  هـ.٣–هـ.٥ — التلميحات
    // =====================================================

    /**
     * الشاشات بتعرض تلميحات فعلية — ومفيش مفتاح ترجمة خام ظاهر
     * للمستخدم (اللي بيحصل لما المفتاح ناقص).
     */
    public function test_forms_render_resolved_tooltips(): void
    {
        $screens = [
            'customers' => route('admin.customers.create'),
            'sales'     => route('admin.sales.create'),
            'expenses'  => route('admin.expenses.create'),
            'employees' => route('admin.employees.create'),
        ];

        foreach ($screens as $name => $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString(
                'data-bs-toggle="tooltip"',
                $html,
                "شاشة {$name}: مفيش أي tooltip"
            );

            // مفتاح خام زي customer.hints.code معناه ترجمة ناقصة
            $this->assertDoesNotMatchRegularExpression(
                '/\b(customer|sale|expense|employee)\.(hints|tooltips)\.[a-z_]+/',
                $html,
                "شاشة {$name}: فيه مفتاح ترجمة خام ظاهر"
            );
        }
    }

    /** كل مفاتيح التلميحات مترجمة في اللغتين. */
    public function test_hint_keys_exist_in_both_languages(): void
    {
        $groups = [
            'customer.hints'    => ['ar', 'en'],
            'sale.hints'        => ['ar', 'en'],
            'expense.hints'     => ['ar', 'en'],
            'employee.tooltips' => ['ar', 'en'],
        ];

        foreach ($groups as $group => $locales) {
            $reference = null;

            foreach ($locales as $locale) {
                app()->setLocale($locale);
                $values = __($group);

                $this->assertIsArray($values, "{$group} مش موجودة في {$locale}");
                $this->assertNotEmpty($values);

                if ($reference === null) {
                    $reference = array_keys($values);
                    continue;
                }

                $this->assertEqualsCanonicalizing(
                    $reference,
                    array_keys($values),
                    "{$group}: المفاتيح مش متطابقة بين اللغتين"
                );
            }
        }

        app()->setLocale('ar');
    }

    /** الشرح مايبقاش فاضي أو مجرد تكرار لاسم الحقل. */
    public function test_hints_are_meaningful(): void
    {
        foreach (['customer', 'sale', 'expense'] as $module) {
            foreach (__($module . '.hints') as $key => $text) {
                $this->assertIsString($text);
                $this->assertGreaterThan(
                    15,
                    mb_strlen($text),
                    "{$module}.hints.{$key}: الشرح قصير جداً ومش مفيد"
                );
            }
        }
    }

    /** الحقول المالية الحسّاسة عليها شرح — دي أكتر حاجة بتلخبط. */
    public function test_money_fields_are_explained(): void
    {
        foreach ([
            'sale.hints.due_amount',
            'sale.hints.paid_amount',
            'sale.hints.total_amount',
            'customer.hints.credit_limit',
            'customer.hints.payment_terms',
            'employee.tooltips.custom_daily_rate',
        ] as $key) {
            $this->assertNotEquals($key, __($key), "{$key}: مالوش شرح");
        }
    }

    /** و.٣ — الدليل بيفتح وبيغطي التغييرات الجديدة. */
    public function test_user_guide_documents_the_new_features(): void
    {
        $html = $this->get(route('admin.system-guide'))->assertOk()->getContent();

        // موديول مصادر العملاء
        $this->assertStringContainsString('sec-customer-sources', $html, 'قسم مصادر العملاء مش موجود');
        $this->assertStringContainsString(__('customer_source.title'), $html);

        // فلاتر اللوحة
        $this->assertStringContainsString('شريط الفلاتر', $html, 'فلاتر اللوحة مش موثّقة');

        // مابقاش بيقول "اتجاه 6 شهور" الثابتة
        $this->assertStringNotContainsString('اتجاه 6 شهور', $html, 'الدليل لسه بيقول 6 شهور ثابتة');

        // موديول الروابط المختصرة
        $this->assertStringContainsString('sec-short-links', $html, 'قسم الروابط المختصرة مش موجود');
        $this->assertStringContainsString(__('link.title'), $html, 'اسم الموديول مش مذكور');

        // والنقط اللي المستخدم لازم يعرفها عشان مايستغربش
        $this->assertStringContainsString('لا تظهر لحظياً', $html, 'تأخير الإحصائيات مش موثّق');
        $this->assertStringContainsString('زيارات البوتات', $html, 'استبعاد البوتات مش موثّق');

        // وأوامر التشغيل الدوري
        foreach (['links:flush', 'links:aggregate', 'links:prune'] as $command) {
            $this->assertStringContainsString($command, $html, "الأمر {$command} مش موثّق");
        }
    }

    /**
     * الدليل مايوصفش مميزات اتشالت.
     *
     * دليل بيقول للمستخدم يعمل حاجة مش موجودة أسوأ من دليل ناقص —
     * هيدوّر عليها ويلوم نفسه إنه مش لاقيها.
     */
    public function test_user_guide_has_no_stale_claims(): void
    {
        $html = $this->get(route('admin.system-guide'))->assertOk()->getContent();

        $stale = [
            // فلتر العملة اتشال من اللوحة
            'العملة:</strong> تلقائي'      => 'فلتر العملة اتشال من اللوحة',
            // تغيير حالة السلفة من الجدول اتشال
            'تعديل حالة السلفة التفاعلي'    => 'تعديل الحالة من الجدول اتشال',
            'من داخل الجدول مباشرة بنقرة'  => 'تعديل الحالة من الجدول اتشال',
            // قائمة تبديل العملة في رسم الاتجاه اتشالت
            'قائمة تبديل بين العملات'       => 'قائمة تبديل العملات اتشالت',
        ];

        foreach ($stale as $needle => $why) {
            $this->assertStringNotContainsString($needle, $html, "الدليل لسه بيقول: {$why}");
        }
    }

    /**
     * أيقونات المحتويات متحاذية في بداية السطر.
     *
     * من غير الـ flex، الأيقونة والنص بيبقوا inline فالأيقونة
     * بتتزحزح مع طول النص وكل سطر بيبدأ من مكان مختلف.
     */
    public function test_guide_toc_icons_are_aligned(): void
    {
        $html = $this->get(route('admin.system-guide'))->assertOk()->getContent();

        // التنسيق موجود
        $this->assertStringContainsString('#guideToc .nav-link', $html, 'مافيش تنسيق للمحتويات');
        $this->assertStringContainsString('display: flex', $html, 'العناصر مش flex');

        // وكل عنصر نصه ملفوف عشان الـ flex يشتغل
        preg_match('/<nav[^>]*id="guideToc">(.*?)<\/nav>/s', $html, $m);

        $this->assertNotEmpty($m[1] ?? '', 'مالقيناش قائمة المحتويات');

        $linkCount = substr_count($m[1], '<a class="nav-link');
        $textCount = substr_count($m[1], '<span class="toc-text">');

        $this->assertGreaterThan(10, $linkCount, 'القائمة شبه فاضية');
        $this->assertEquals($linkCount, $textCount, 'فيه عناصر نصها مش ملفوف — هتبان مايلة');
    }

    /** الدليل بيغطي فلاتر الجداول الجديدة. */
    public function test_user_guide_documents_table_filters(): void
    {
        $html = $this->get(route('admin.system-guide'))->assertOk()->getContent();

        $this->assertStringContainsString('sec-table-filters', $html, 'قسم فلاتر الجداول مش موجود');
        $this->assertStringContainsString('قائمة منسدلة', $html);
    }

    /**
     * قائمة محتويات الدليل بتتبع القسم اللي إنت فيه.
     *
     * كان كلاس active مكتوب ثابت على البند الأول ومفيش جافاسكريبت
     * بيحدّثه، فالتمييز يفضل على أول عنصر مهما نزلت في الصفحة.
     */
    public function test_guide_toc_tracks_the_current_section(): void
    {
        $html = $this->get(route('admin.system-guide'))->assertOk()->getContent();

        // مفيش عنصر متحدد سلفاً في الـ HTML
        $this->assertStringNotContainsString(
            'nav-link py-2 px-3 active',
            $html,
            'لسه في بند متحدد بشكل ثابت'
        );

        // والجافاسكريبت اللي بيحدّث التحديد موجود
        $this->assertStringContainsString('id="guideToc"', $html, 'القائمة مالهاش معرّف');
        $this->assertStringContainsString('currentSection', $html, 'كود التتبّع مش موجود');
    }

    /** كل رابط في القائمة بيوصّل لقسم موجود فعلاً. */
    public function test_guide_toc_links_point_to_real_sections(): void
    {
        $html = $this->get(route('admin.system-guide'))->assertOk()->getContent();

        preg_match_all('/href="#(sec-[a-z-]+)"/', $html, $links);

        $this->assertNotEmpty($links[1], 'مفيش روابط في القائمة');

        foreach (array_unique($links[1]) as $anchor) {
            $this->assertStringContainsString(
                'id="' . $anchor . '"',
                $html,
                "الرابط #{$anchor} مالوش قسم في الصفحة"
            );
        }
    }
}
