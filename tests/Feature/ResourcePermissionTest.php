<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CustomerSource;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * كل إجراء في مسارات الـ resource بيتحقق من صلاحيته هو بس (middlewareFor).
 */
class ResourcePermissionTest extends TestCase
{
    use RefreshDatabase;

    private function actingWith(array $permissions): void
    {
        $role = Role::create(['name' => 'Limited ' . uniqid(), 'roles' => $permissions]);

        $this->actingAs(Admin::create([
            'name' => 'Limited', 'email' => uniqid() . '@test.com',
            'password' => bcrypt('password'), 'main' => false, 'role_id' => $role->id,
        ]), 'admin');
    }

    /** صلاحية العرض لوحدها مابتديش إضافة ولا تعديل ولا حذف. */
    public function test_show_permission_alone_cannot_modify_customer_sources(): void
    {
        $source = CustomerSource::create(['code' => 'SRC-T', 'name' => 'Facebook', 'is_active' => true]);

        $this->actingWith(['customer_source_show']);

        $this->get(route('admin.customer-sources.index'))->assertOk();

        $this->post(route('admin.customer-sources.store'), ['code' => 'NEW', 'name' => 'New'])
            ->assertRedirect(route('admin.error_403'));

        $this->put(route('admin.customer-sources.update', $source->id), ['code' => 'SRC-T', 'name' => 'Changed'])
            ->assertRedirect(route('admin.error_403'));

        $this->delete(route('admin.customer-sources.destroy', $source->id))
            ->assertRedirect(route('admin.error_403'));

        $this->assertDatabaseMissing('customer_sources', ['code' => 'NEW']);
        $this->assertSame('Facebook', $source->fresh()->name);
        $this->assertNotSoftDeleted('customer_sources', ['id' => $source->id]);
    }

    /** صفحة الإضافة بتورث صلاحية الحفظ، والتعديل بيورث صلاحية التحديث. */
    public function test_create_and_edit_pages_inherit_store_and_update_permissions(): void
    {
        $this->actingWith(['role_show']);

        $this->get(route('admin.roles.create'))->assertRedirect(route('admin.error_403'));

        $this->actingWith(['role_show', 'role_create']);

        $this->get(route('admin.roles.create'))->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.customer-sources.index'))->assertRedirect(route('admin.login'));
    }
}
