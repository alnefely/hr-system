<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * أسماء الشهور في كل الشاشات.
 *
 * كشف تدقيق الرواتب كان بيبني مفتاح الترجمة من قيمة مصفوفة بصفر
 * (global.months.01)، والمفاتيح في ملف الترجمة 1..12 من غير صفر —
 * فالشهور من 1 لـ 9 كانت بتظهر كمفتاح خام، و 10 لـ 12 بس شغالين.
 */
class MonthTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $admin = Admin::create([
            'name' => 'Root', 'email' => 'root@month.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        Branch::create([
            'name' => 'HQ', 'code' => 'MTH', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin');
    }

    /** الشاشات اللي فيها اختيار شهر. */
    private const SCREENS = [
        'admin.payrolls.create',
        'admin.permissions.index',
    ];

    /** كل الشهور الاتناشر ظاهرة بأسمائها في كل شاشة. */
    public function test_every_month_name_is_rendered(): void
    {
        foreach (self::SCREENS as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            foreach (__('global.months') as $number => $name) {
                $this->assertStringContainsString(
                    $name,
                    $html,
                    "الشاشة {$route}: الشهر {$number} «{$name}» مش ظاهر"
                );
            }
        }
    }

    /** مفيش مفتاح شهر خام ظاهر للمستخدم. */
    public function test_no_raw_month_key_is_rendered(): void
    {
        foreach (self::SCREENS as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            $this->assertStringNotContainsString(
                'global.months.',
                $html,
                "الشاشة {$route}: فيه مفتاح شهر خام — الأغلب القيمة مصفوفة بصفر"
            );
        }
    }

    /**
     * مفاتيح الشهور أرقام مجرّدة من غير صفر.
     *
     * أي كود بيبني المفتاح من date('m') أو str_pad هيدوّر على
     * مفتاح مش موجود.
     */
    public function test_month_keys_are_unpadded_numbers(): void
    {
        $months = __('global.months');

        $this->assertCount(12, $months);

        foreach (range(1, 12) as $m) {
            $this->assertArrayHasKey($m, $months, "المفتاح {$m} مش موجود");
        }

        // والمصفوف بصفر ماينفعش يبقى موجود — عشان مايبقاش في مصدرين
        foreach (['01', '02', '09'] as $padded) {
            $this->assertArrayNotHasKey(
                $padded,
                $months,
                "المفتاح المصفوف «{$padded}» موجود — لازم مصدر واحد"
            );
        }
    }

    /**
     * كشف التدقيق بقى بنطاق تواريخ بدل شهر/سنة.
     *
     * البيانات كلها كانت أصلاً بتتفلتر بنطاق محسوب من الشهر، فالتحويل
     * وحّد الشكل مع تقرير كشف حساب الموظف.
     */
    public function test_salary_audit_uses_a_date_range(): void
    {
        $html = $this->get(route('admin.reports.salary-audit'))->assertOk()->getContent();

        $this->assertStringContainsString('name="from_date"', $html, 'مفيش حقل من تاريخ');
        $this->assertStringContainsString('name="to_date"', $html, 'مفيش حقل إلى تاريخ');

        $this->assertStringNotContainsString('name="month"', $html, 'لسه فيه اختيار شهر');
        $this->assertStringNotContainsString('name="year"', $html, 'لسه فيه اختيار سنة');
    }

    /** الافتراضي: الشهر الحالي كامل. */
    public function test_salary_audit_defaults_to_the_current_month(): void
    {
        $response = $this->get(route('admin.reports.salary-audit'))->assertOk();

        $this->assertEquals(now()->startOfMonth()->format('Y-m-d'), $response->viewData('startDate'));
        $this->assertEquals(now()->endOfMonth()->format('Y-m-d'), $response->viewData('endDate'));
    }

    /** التواريخ المعكوسة بتتصلّح. */
    public function test_salary_audit_swaps_reversed_dates(): void
    {
        $response = $this->get(route('admin.reports.salary-audit', [
            'from_date' => '2026-09-30',
            'to_date'   => '2026-09-01',
        ]))->assertOk();

        $this->assertEquals('2026-09-01', $response->viewData('startDate'));
        $this->assertEquals('2026-09-30', $response->viewData('endDate'));
    }

    /**
     * فلتر كشف التدقيق على سطر واحد وفيه زرار إعادة تعيين.
     *
     * مجموع أعمدة الشبكة كان 13 (أكبر من 12) فكان بينزل لسطر تاني،
     * ومكانش فيه طريقة يرجّع الفلتر لوضعه الافتراضي.
     */
    public function test_salary_audit_filter_fits_one_row_and_has_a_reset(): void
    {
        $html = $this->get(route('admin.reports.salary-audit'))->assertOk()->getContent();

        // مجموع أعمدة الفورم لازم مايزيدش عن 12
        preg_match('/<form[^>]*salary-audit[^>]*>(.*?)<\/form>/s', $html, $form);
        $this->assertNotEmpty($form, 'فورم الفلتر مش موجود');

        preg_match_all('/col-md-(\d+)/', $form[1], $cols);
        $total = array_sum(array_map('intval', $cols[1]));

        $this->assertLessThanOrEqual(
            12,
            $total,
            "مجموع الأعمدة {$total} — الفلتر هينزل على أكتر من سطر"
        );

        // وزرار إعادة التعيين موجود
        $this->assertStringContainsString(
            route('admin.reports.salary-audit'),
            $html,
            'مفيش رابط لإعادة تعيين الفلتر'
        );
        $this->assertStringContainsString('fa-rotate-left', $html, 'زرار إعادة التعيين مش موجود');
    }
}
