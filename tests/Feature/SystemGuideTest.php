<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * دليل الاستخدام: يتأكد إن الصفحة بتفتح وإن الأقسام الجديدة ظاهرة فيها.
 */
class SystemGuideTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Super Admin', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Guide Reader', 'email' => 'guide@test.com',
            'password' => bcrypt('password'), 'main' => true, 'role_id' => $role->id,
        ]);
    }

    public function test_guide_page_renders_for_permitted_admin(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.system-guide'))
            ->assertOk();
    }

    public function test_guide_contains_the_new_governance_sections(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.system-guide'))
            ->assertOk();

        foreach ([
            'sec-dashboard',
            'sec-approval-cycle',
            'sec-activity-log',
            'sec-branch-isolation',
            'sec-auth-security',
            'sec-notifications',
            'sec-maintenance',
        ] as $section) {
            $response->assertSee($section, false);
        }
    }

    public function test_guide_lists_the_audited_modules_dynamically(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.system-guide'))
            ->assertOk();

        // الشارات مبنية من config، فأي موديول جديد يظهر تلقائياً
        foreach (array_slice(activity_subjects(), 0, 5) as $label) {
            $response->assertSee($label, false);
        }
    }

    public function test_guide_shows_configured_retention_period(): void
    {
        config(['activity.retention_days' => 120]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.system-guide'))
            ->assertOk()
            ->assertSee('120', false);
    }

    public function test_guide_requires_permission(): void
    {
        $limitedRole = Role::create(['name' => 'Limited', 'roles' => []]);
        $limited = Admin::create([
            'name' => 'No Access', 'email' => 'noaccess@test.com',
            'password' => bcrypt('password'), 'main' => true, 'role_id' => $limitedRole->id,
        ]);

        $this->actingAs($limited, 'admin')
            ->get(route('admin.system-guide'))
            ->assertRedirect(route('admin.error_403'));
    }
}
