<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Role;
use Database\Seeders\ComprehensiveDummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * الواجهة على الموبايل والتابلت: الجداول بتعمل scroll جوّه الكارت،
 * والاتجاه من لغة التطبيق، ومفيش ملفات زيادة (customizer / pickr / algolia)،
 * والأزرار اللي أيقونة بس ليها aria-label.
 */
class ResponsiveUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ComprehensiveDummySeeder::class);

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);
        $this->actingAs(Admin::create([
            'name' => 'Root', 'email' => 'root@responsive.test', 'password' => bcrypt('x'),
            'main' => true, 'role_id' => $role->id,
        ]), 'admin');
    }

    public function test_layout_direction_follows_the_locale(): void
    {
        app()->setLocale('ar');
        $this->assertSame('rtl', vuexy_setting('dir'));
        $this->assertSame('ar', vuexy_setting('lang'));

        app()->setLocale('en');
        $this->assertSame('ltr', vuexy_setting('dir'));
        $this->assertSame('en', vuexy_setting('lang'));

        $this->assertStringContainsString('customizer-hide', vuexy_setting('class'));
    }

    public function test_admin_layout_drops_demo_assets_and_allows_zoom(): void
    {
        app()->setLocale('ar');
        $html = $this->get(route('admin.home'))->assertOk()->getContent();

        $this->assertStringContainsString('content="width=device-width, initial-scale=1"', $html);
        $this->assertStringNotContainsString('user-scalable=no', $html);
        $this->assertStringNotContainsString('template-customizer.js', $html);
        $this->assertStringNotContainsString('pickr', $html);
        $this->assertStringNotContainsString('autocomplete-js', $html);

        // الخط بيتحمّل بـ link + preconnect (عائلة واحدة)
        $this->assertStringContainsString('rel="preconnect" href="https://fonts.googleapis.com"', $html);
        $this->assertStringContainsString('family=Rubik', $html);

        // أزرار الشريط العلوي اللي أيقونة بس
        $this->assertMatchesRegularExpression('/id="nav-theme"[^>]*aria-label="/s', $html);
        $this->assertStringContainsString('notifications-dropdown', $html);
        $this->assertStringNotContainsString('min-width: 340px', $html);
    }

    public function test_custom_css_no_longer_forces_the_font_on_every_element(): void
    {
        $css = file_get_contents(public_path('admin/custom.css'));

        $this->assertStringNotContainsString('@import', $css);
        $this->assertDoesNotMatchRegularExpression("/font-family:\s*'Rubik'[^;]*!important/", $css);
    }

    public function test_datatables_scroll_inside_the_card(): void
    {
        $html = $this->get(route('admin.employees'))->assertOk()->getContent();

        $this->assertStringContainsString('dt-card-wrapper', $html);
        $this->assertStringNotContainsString('table-striped0', $html);
        $this->assertStringNotContainsString('#3a405a', $html);

        $js = file_get_contents(public_path('admin/vendor/libs/datatables-bs5/dtHelpers.js'));
        $this->assertStringContainsString('dt-scroll-x', $js);
        $this->assertStringNotContainsString('row me-2', $js);
    }

    public function test_employee_profile_tabs_scroll_horizontally(): void
    {
        $employeeId = \App\Models\Employee::query()->value('id');
        $html = $this->get(route('admin.employees.show', $employeeId))->assertOk()->getContent();

        $this->assertStringContainsString('nav-tabs-scroll', $html);
    }

    public function test_payslip_uses_local_assets(): void
    {
        $views = [
            resource_path('views/admin/hr/payrolls/payslip.blade.php'),
            resource_path('views/admin/hr/payrolls/payslips_bulk.blade.php'),
        ];

        foreach ($views as $view) {
            $source = file_get_contents($view);
            $this->assertStringNotContainsString('cdn.jsdelivr.net', $source);
            $this->assertStringNotContainsString('cdnjs.cloudflare.com', $source);
            $this->assertStringContainsString('/admin/vendor/css/core.css', $source);
        }
    }

    public function test_button_component_renders_waves_as_a_class(): void
    {
        $html = $this->blade('<x-inputs.btn text="Go" />')->__toString();

        $this->assertMatchesRegularExpression('/class="btn btn-primary waves-effect/', $html);
        $this->assertDoesNotMatchRegularExpression('/\swaves-effect\s*\n/', $html);
    }
}
