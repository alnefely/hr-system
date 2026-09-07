<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\JobTitle;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * تنبيه تأكيد واحد بس.
 *
 * كان في المشروع مصدرين للتأكيد بيشتغلوا مع بعض:
 *  - confirm() المدمج في المتصفح (onclick / onsubmit)
 *  - SweetAlert عبر btn-swal-confirm / data-confirm
 *
 * النتيجة إن المستخدم كان بيشوف تنبيهين ورا بعض. الاختبارات دي
 * بتتأكد إن confirm() المدمج مااتسابش في أي شاشة.
 */
class ConfirmDialogTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@c.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        Branch::create([
            'name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);
        JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        $this->actingAs($this->admin, 'admin');
    }

    /** مكوّن الزر المشترك بيعتمد على data-confirm بس. */
    public function test_shared_button_component_has_no_native_confirm(): void
    {
        $html = Blade::render('<x-inputs.btn action="delete" text="Delete" />');

        $this->assertStringContainsString('data-confirm', $html, 'المفروض يعتمد على data-confirm');
        $this->assertStringNotContainsString('return confirm(', $html, 'لسه فيه confirm مدمج');
    }

    /** كل أنواع الإجراءات مالهاش confirm مدمج. */
    public function test_all_button_actions_use_a_single_confirm_source(): void
    {
        foreach (['create', 'update', 'delete', 'save'] as $action) {
            $html = Blade::render('<x-inputs.btn action="' . $action . '" text="Go" />');

            $this->assertStringNotContainsString(
                'return confirm(',
                $html,
                "الإجراء [{$action}] لسه فيه confirm مدمج"
            );
        }
    }

    /** شاشات سلة المحذوفات مابتستخدمش confirm المدمج. */
    public function test_trash_screens_have_no_native_confirm(): void
    {
        $routes = [
            'admin.customers.trash',
            'admin.expenses.trash',
            'admin.expense-categories.trash',
            'admin.branches.trash',
            'admin.employees.trash',
            'admin.job_titles.trash',
            'admin.permissions.trash',
            'admin.sales.trash',
            'admin.sales-channels.trash',
            'admin.vendor-subscriptions.trash',
            'admin.vendors.trash',
        ];

        foreach ($routes as $route) {
            $response = $this->get(route($route));

            $this->assertTrue(
                in_array($response->getStatusCode(), [200, 302], true),
                "المسار [{$route}] رجّع " . $response->getStatusCode()
            );

            if ($response->getStatusCode() !== 200) {
                continue;
            }

            $content = $response->getContent();

            // confirm() المدمج ممنوع إلا كخطة بديلة لو SweetAlert مش متحمّلة،
            // وساعتها بيبقى محاط بـ typeof Swal === 'undefined' فمايظهرش مع swal.
            $this->assertStringNotContainsString(
                'return confirm(',
                $content,
                "شاشة [{$route}] لسه فيها confirm مدمج غير محمي"
            );
        }
    }

    /** شاشة تعديل المشرف — دي اللي المستخدم شاف فيها التنبيهين. */
    public function test_admin_edit_screen_shows_one_confirm_only(): void
    {
        $response = $this->get(route('admin.admins'))->assertOk();

        $this->assertStringNotContainsString(
            'return confirm(',
            $response->getContent(),
            'شاشة المشرفين لسه فيها confirm مدمج'
        );
    }

    /** المعترض القديم اللي كان بيسبب الازدواج اتشال. */
    public function test_legacy_onsubmit_interceptor_is_gone(): void
    {
        $alert = file_get_contents(resource_path('views/layouts/alert.blade.php'));

        $this->assertStringNotContainsString(
            "form[onsubmit*=\"confirm\"]",
            $alert,
            'المعترض القديم لسه موجود'
        );
        $this->assertStringContainsString(
            '.btn-swal-confirm, [data-confirm]',
            $alert,
            'المعالج الموحّد مش موجود'
        );
    }
}
