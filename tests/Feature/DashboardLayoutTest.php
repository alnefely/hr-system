<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Role;
use Database\Seeders\ComprehensiveDummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تنظيم الرئيسية: كارت موحّد للأرقام، والأجزاء اللي بتتحدّث مع الفلتر
 * بتترندر من السيرفر بنفس partials الصفحة (مصدر واحد للشكل).
 */
class DashboardLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ComprehensiveDummySeeder::class);

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);
        $this->actingAs(Admin::create([
            'name' => 'Root', 'email' => 'root@dash.test', 'password' => bcrypt('x'),
            'main' => true, 'role_id' => $role->id,
        ]), 'admin');
    }

    public function test_numbers_use_the_shared_stat_card(): void
    {
        $html = $this->get(route('admin.home'))->assertOk()->getContent();

        // 4 كروت للموارد البشرية على الأقل + المالية لكل عملة
        $this->assertGreaterThanOrEqual(4, substr_count($html, 'class="card dash-stat h-100"'));

        // الحدود الملوّنة القديمة والعناوين بـ text-dark (بتختفي في الوضع الليلي) اتشالت
        $this->assertStringNotContainsString('border-start border-success border-4', $html);
        $this->assertDoesNotMatchRegularExpression('/<h[56][^>]*text-dark/', $html);
    }

    public function test_filter_refresh_returns_server_rendered_sections(): void
    {
        $json = $this->getJson(route('admin.home.data', ['preset' => 'this_year']))->assertOk()->json();

        $this->assertArrayHasKey('financialHtml', $json);
        $this->assertArrayHasKey('topCustomersHtml', $json);

        // نفس الكارت الموحّد أو رسالة "مفيش بيانات"
        $this->assertTrue(
            str_contains($json['financialHtml'], 'dash-stat') || str_contains($json['financialHtml'], __('dashboard.no_financial_data'))
        );
    }

    public function test_chart_type_menu_is_rendered_once_per_chart(): void
    {
        $html = $this->get(route('admin.home'))->assertOk()->getContent();

        foreach (['trend', 'expenses', 'channels'] as $target) {
            $this->assertSame(10, substr_count($html, 'data-target="' . $target . '" data-type="'), "{$target}: لازم 10 أشكال");
        }
    }
}
