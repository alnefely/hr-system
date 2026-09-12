<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Link;
use App\Models\Role;
use App\Support\BranchScopeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * الصلاحيات وعزل الفروع.
 */
class LinkPermissionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions, ?int $employeeId = null, bool $main = false): Admin
    {
        $role = Role::create(['name' => 'R' . uniqid(), 'roles' => $permissions]);

        return Admin::create([
            'name'        => 'A' . uniqid(),
            'email'       => uniqid() . '@link.test',
            'password'    => bcrypt('x'),
            'main'        => $main,
            'role_id'     => $role->id,
            'employee_id' => $employeeId,
        ]);
    }

    protected function tearDown(): void
    {
        BranchScopeResolver::forget();
        parent::tearDown();
    }

    /** من غير صلاحية العرض مافيش وصول للشاشة. */
    public function test_index_requires_permission(): void
    {
        $this->actingAs($this->admin(['dashboard_show']), 'admin');

        $response = $this->get(route('admin.links.index'));

        $this->assertNotEquals(200, $response->getStatusCode(), 'الشاشة اتفتحت من غير صلاحية');
    }

    public function test_index_allowed_with_permission(): void
    {
        $this->actingAs($this->admin(['link_show']), 'admin');

        $this->get(route('admin.links.index'))->assertOk();
    }

    /** الإنشاء له صلاحيته المستقلة. */
    public function test_store_requires_create_permission(): void
    {
        $this->actingAs($this->admin(['link_show']), 'admin');

        $this->post(route('admin.links.store'), [
            'original_url' => 'https://example.com',
        ]);

        $this->assertDatabaseCount('links', 0);
    }

    public function test_store_works_with_create_permission(): void
    {
        $this->actingAs($this->admin(['link_show', 'link_create']), 'admin');

        $this->post(route('admin.links.store'), [
            'original_url' => 'https://example.com/page',
        ])->assertRedirect();

        $this->assertDatabaseHas('links', ['original_url' => 'https://example.com/page']);
    }

    /** الوجهة الخطرة مابتتحفظش حتى مع الصلاحية. */
    public function test_store_rejects_internal_url(): void
    {
        $this->actingAs($this->admin(['link_show', 'link_create']), 'admin');

        $this->post(route('admin.links.store'), [
            'original_url' => 'http://192.168.1.1/admin',
        ])->assertSessionHasErrors('original_url');

        $this->assertDatabaseCount('links', 0);
    }

    /**
     * عزل الفروع.
     *
     * مشرف مربوط بفرع مالوش يشوف روابط فرع تاني — لا في القايمة
     * ولا بفتح الرابط المباشر (IDOR).
     */
    public function test_links_are_scoped_to_branch(): void
    {
        $job = JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        $branchA = Branch::create(['name' => 'A', 'code' => 'LA', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $branchB = Branch::create(['name' => 'B', 'code' => 'LB', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);

        $employee = Employee::create([
            'branch_id' => $branchA->id, 'job_title_id' => $job->id,
            'name' => 'E', 'employee_code' => 'LNK-1', 'phone' => '01',
            'status' => 'active', 'basic_salary' => 1000,
            'hire_date' => now()->subYear()->toDateString(),
        ]);

        $mine = Link::create(['short_code' => 'Mine123', 'original_url' => 'https://example.com/a', 'branch_id' => $branchA->id, 'is_active' => true]);
        $theirs = Link::create(['short_code' => 'Their12', 'original_url' => 'https://example.com/b', 'branch_id' => $branchB->id, 'is_active' => true]);

        BranchScopeResolver::forget();
        $this->actingAs($this->admin(['link_show', 'link_update'], $employee->id), 'admin');

        $visible = Link::pluck('short_code')->all();

        $this->assertContains('Mine123', $visible, 'مشفش رابط فرعه');
        $this->assertNotContains('Their12', $visible, 'شاف رابط فرع تاني');

        // والوصول المباشر بالرابط لازم يدي 404 مش يفتح
        $this->get(route('admin.links.edit', $theirs->id))->assertNotFound();
        $this->get(route('admin.links.edit', $mine->id))->assertOk();
    }

    /** التحويل العام مايتأثرش بعزل الفروع — الزائر مش مشرف. */
    public function test_redirect_ignores_branch_scope(): void
    {
        $branch = Branch::create(['name' => 'C', 'code' => 'LC', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);

        Link::create(['short_code' => 'Pub1234', 'original_url' => 'https://example.com/pub', 'branch_id' => $branch->id, 'is_active' => true]);

        $this->get('/s/Pub1234')->assertStatus(302);
    }
}
