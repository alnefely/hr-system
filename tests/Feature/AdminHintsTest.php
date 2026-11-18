<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * أيقونات الشرح (؟) في شاشات لوحة الإدارة.
 *
 * بيتأكد إن كل شاشة بتعرض الشرح فعلاً، ومفيش مفتاح ترجمة خام ظاهر
 * (اللي بيحصل لو مفتاح hints ناقص في ملف اللغة).
 */
class AdminHintsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);
        $this->actingAs(Admin::create([
            'name' => 'Root', 'email' => 'root@hints.test', 'password' => bcrypt('x'),
            'main' => true, 'role_id' => $role->id,
        ]), 'admin');
    }

    public function test_screens_render_hint_icons_without_raw_keys(): void
    {
        $branch = Branch::create(['name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $job = JobTitle::create(['name' => 'Staff', 'is_active' => true]);
        $employee = Employee::create([
            'branch_id' => $branch->id, 'job_title_id' => $job->id, 'name' => 'Emp', 'employee_code' => 'HQ-0001',
            'phone' => '+201000000001', 'status' => 'active', 'basic_salary' => 6000, 'hire_date' => '2024-01-01',
        ]);

        $screens = [
            'branches'       => route('admin.branches.create'),
            'job titles'     => route('admin.job_titles.create'),
            'advances'       => route('admin.advances.index'),
            'permissions'    => route('admin.permissions.index'),
            'rewards'        => route('admin.rewards.index'),
            'penalties'      => route('admin.penalties.index'),
            'leaves'         => route('admin.leaves.index'),
            'leave balances' => route('admin.leaves.balances'),
            'employee file'  => route('admin.employees.show', $employee),
            'vendors'        => route('admin.vendors.index'),
            'subscriptions'  => route('admin.vendor-subscriptions.create'),
            'sales channels' => route('admin.sales-channels.index'),
            'expense cats'   => route('admin.expense-categories.index'),
            'admins'         => route('admin.admins.create'),
            'roles'          => route('admin.roles.create'),
        ];

        foreach ($screens as $name => $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('fa-circle-question', $html, "{$name}: مفيش أيقونة شرح");
            $this->assertDoesNotMatchRegularExpression('/\b[a-z_]+\.hints\.[a-z_]+\b/', $html, "{$name}: مفتاح شرح خام ظاهر");
        }
    }

    /** كل مفاتيح الشرح الجديدة موجودة في اللغتين بنفس المفاتيح. */
    public function test_hint_keys_match_in_both_languages(): void
    {
        foreach (['branch', 'job_title', 'advance', 'permission', 'reward', 'penalty', 'leave', 'payroll',
                  'salary', 'document', 'skill', 'vendor', 'vendor_subscription', 'sales_channel',
                  'expense_category', 'admin', 'role'] as $module) {
            app()->setLocale('ar');
            $ar = array_keys(__("{$module}.hints"));
            app()->setLocale('en');
            $en = array_keys(__("{$module}.hints"));

            $this->assertNotEmpty($ar, "{$module}.hints فاضي");
            $this->assertEqualsCanonicalizing($ar, $en, "{$module}.hints: المفاتيح مختلفة بين العربي والإنجليزي");
        }

        app()->setLocale('ar');
    }
}
