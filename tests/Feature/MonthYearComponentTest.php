<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * مكوّن اختيار الشهر والسنة.
 *
 * كان في 3 مشاكل: الشهور بالإنجليزي (January)، السنين مكتوبة
 * كرقم ثابت في الكود (2024) فمابتتحدّثش مع الوقت، والحقلين
 * في سطرين منفصلين.
 */
class MonthYearComponentTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@my.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        Branch::create([
            'name' => 'HQ', 'code' => 'MY', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    /** أسماء الشهور موجودة ومترجمة بالكامل. */
    public function test_all_twelve_months_are_translated(): void
    {
        $months = __('global.months');

        $this->assertCount(12, $months);

        foreach ($months as $number => $name) {
            $this->assertIsString($name);
            $this->assertStringNotContainsString('global.months', $name, "الشهر {$number} مالوش ترجمة");
        }
    }

    /** شاشة إصدار المسير بتعرض الشهور بالعربي مش January. */
    public function test_payroll_create_shows_localised_months(): void
    {
        $html = $this->get(route('admin.payrolls.create'))->assertOk()->getContent();

        $this->assertStringContainsString(__('global.months.1'), $html);
        $this->assertStringContainsString(__('global.months.9'), $html);
        $this->assertStringNotContainsString('January', $html, 'لسه بيعرض الشهور بالإنجليزي');
        $this->assertStringNotContainsString('February', $html);
    }

    /** السنين نسبية للسنة الحالية مش رقم ثابت. */
    public function test_year_range_is_relative_to_current_year(): void
    {
        $currentYear = (int) date('Y');

        foreach ([
            route('admin.payrolls.create'),
        ] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString((string) $currentYear, $html, 'السنة الحالية مش في القائمة');
            $this->assertStringContainsString((string) ($currentYear + 1), $html, 'السنة الجاية مش في القائمة');

            // سنة قديمة بره النطاق المفروض ماتظهرش
            $this->assertStringNotContainsString(
                '<option value="' . ($currentYear - 5) . '"',
                $html,
                'النطاق لسه ثابت مش نسبي'
            );
        }
    }

    /** الحقلان في input-group واحد — سطر أفقي. */
    public function test_month_and_year_are_on_one_line(): void
    {
        $html = $this->get(route('admin.payrolls.create'))->assertOk()->getContent();

        $monthPos = strpos($html, 'name="month"');
        $this->assertNotFalse($monthPos, 'حقل الشهر مش موجود');

        // نرجع لورا ندوّر على أقرب input-group
        $before = substr($html, max(0, $monthPos - 300), 300);

        $this->assertStringContainsString(
            'input-group',
            $before,
            'الشهر والسنة مش في input-group واحد'
        );
    }

    /** مفتاح إعادة تعيين الفلاتر مترجم. */
    public function test_reset_filter_is_translated(): void
    {
        $this->assertNotEquals(
            'global.reset_filter',
            __('global.reset_filter'),
            'المفتاح لسه غير مترجم'
        );
    }
}
