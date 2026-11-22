<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Role;
use App\Rules\SafeFileReference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * الثغرات اللي اتقفلت في المراجعة الشاملة: رفع الصلاحيات، حقن الكود
 * عبر مسارات الملفات، ومدير الملفات، وسجل النشاط بين الفروع.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branchA;
    protected Branch $branchB;
    protected Employee $empA;
    protected Employee $empB;

    protected function setUp(): void
    {
        parent::setUp();

        $job = JobTitle::create(['name' => 'Engineer', 'is_active' => true]);

        foreach (['A', 'B'] as $code) {
            $branch = Branch::create([
                'name' => "Branch $code", 'code' => "BR-$code", 'country' => 'Egypt',
                'currency' => 'EGP', 'is_active' => true,
            ]);
            $this->{"branch$code"} = $branch;
            $this->{"emp$code"} = Employee::create([
                'branch_id' => $branch->id, 'job_title_id' => $job->id,
                'name' => "Emp $code", 'employee_code' => "E-$code", 'phone' => "+20100000$code",
                'status' => 'active', 'basic_salary' => 5000, 'hire_date' => '2024-01-01',
            ]);
        }
    }

    private function branchAdmin(array $permissions, ?Employee $employee = null): Admin
    {
        $role = Role::create(['name' => 'R' . uniqid(), 'roles' => $permissions]);
        $admin = Admin::create([
            'name' => 'Branch Admin', 'email' => uniqid() . '@test.com', 'password' => bcrypt('password'),
            'main' => false, 'role_id' => $role->id, 'employee_id' => ($employee ?? $this->empA)->id,
        ]);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    /** مشرف الفرع مايفكّش ربطه بالفرع ولا يدّي نفسه دور أعلى. */
    public function test_branch_admin_cannot_edit_own_account(): void
    {
        $admin = $this->branchAdmin(['admin_show', 'admin_update']);
        $super = Role::create(['name' => 'Super', 'roles' => all_roles()]);

        $this->put(route('admin.admins.update', $admin->id), [
            'name' => 'x', 'email' => $admin->email, 'role_id' => $super->id, 'employee_id' => null,
        ]);

        $fresh = $admin->fresh();
        $this->assertSame($this->empA->id, $fresh->employee_id);
        $this->assertNotSame($super->id, $fresh->role_id);
    }

    /** مايمنحش دور فيه صلاحيات مش عنده، ولا يربط بموظف فرع تاني. */
    public function test_branch_admin_cannot_grant_more_than_they_have(): void
    {
        $this->branchAdmin(['admin_show', 'admin_create']);
        $super = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $small = Role::create(['name' => 'Small', 'roles' => ['admin_show']]);

        $this->post(route('admin.admins.store'), [
            'name' => 'New', 'email' => 'n1@test.com', 'password' => 'Secret123',
            'role_id' => $super->id, 'employee_id' => null,
        ])->assertSessionHasErrors('role_id');

        $this->post(route('admin.admins.store'), [
            'name' => 'New', 'email' => 'n2@test.com', 'password' => 'Secret123',
            'role_id' => $small->id, 'employee_id' => $this->empB->id,
        ])->assertSessionHasErrors('employee_id');

        $this->assertFalse(Admin::whereIn('email', ['n1@test.com', 'n2@test.com'])->exists());
    }

    /** مسار الصورة/المرفق مايقبلش كود. */
    public function test_file_references_reject_script_payloads(): void
    {
        foreach (['x" onerror="alert(1)', 'javascript:alert(1)', 'data:text/html,hi', '//evil.com/a.png', "a'b"] as $bad) {
            $this->assertFalse(SafeFileReference::isSafe($bad), $bad);
        }

        foreach (['/uploads/files/a.png', 'uploads/shares/b.pdf', 'https://cdn.example.com/x.jpg'] as $good) {
            $this->assertTrue(SafeFileReference::isSafe($good), $good);
        }
    }

    /** صورة الموظف بتتعمل escape في جدول الموظفين حتى لو القيمة قديمة. */
    public function test_employee_table_escapes_avatar(): void
    {
        Employee::whereKey($this->empA->id)->update(['avatar' => 'x" onerror="alert(1)']);
        $role = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $this->actingAs(Admin::create([
            'name' => 'Root', 'email' => 'root@test.com', 'password' => bcrypt('password'),
            'main' => true, 'role_id' => $role->id,
        ]), 'admin');

        $json = $this->getJson(route('admin.employees') . '?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->getContent();

        $this->assertStringNotContainsString('onerror=\"alert', $json);
    }

    /** "عرض مدير الملفات" مابقتش تسمح بالحذف. */
    public function test_file_manager_delete_needs_its_own_permission(): void
    {
        $this->branchAdmin(['filemanager_show']);

        $this->get('/filemanager/delete?items[]=x.png')->assertForbidden();
    }

    /** مشرف الفرع مايشوفش حركات موظف في فرع تاني. */
    public function test_activity_log_is_scoped_to_branch(): void
    {
        $log = ActivityLog::create([
            'event' => 'updated', 'subject_type' => Employee::class, 'subject_id' => $this->empB->id,
            'old_values' => ['basic_salary' => 5000], 'new_values' => ['basic_salary' => 9000],
        ]);

        $this->branchAdmin(['activity_log_show']);

        $this->getJson(route('admin.activity-logs.show', $log->id))->assertNotFound();
    }

    /** قيم الراتب في السجل بتتخفي عن اللي مالوش صلاحية الرواتب. */
    public function test_activity_log_hides_salary_without_permission(): void
    {
        $log = ActivityLog::create([
            'event' => 'updated', 'subject_type' => Employee::class, 'subject_id' => $this->empA->id,
            'old_values' => ['basic_salary' => 5000], 'new_values' => ['basic_salary' => 9000],
        ]);

        $this->branchAdmin(['activity_log_show']);

        $json = $this->getJson(route('admin.activity-logs.show', $log->id))->assertOk()->json('changes');

        $this->assertSame('***', collect($json)->firstWhere('field', 'basic_salary')['new']);
    }
}
