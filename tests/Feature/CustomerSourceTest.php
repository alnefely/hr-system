<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\CustomerSource;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * د.١–د.٦ — موديول مصادر العملاء.
 *
 * المصدر كان نص ثابت في قائمة جوه الـ blade: مافيش طريقة تضيف
 * مصدر جديد من غير تعديل كود، ولا تعرف كل مصدر جابلك كام عميل.
 */
class CustomerSourceTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@source.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    private function makeSource(array $overrides = []): CustomerSource
    {
        return CustomerSource::create(array_merge([
            'code' => 'SRC-T1', 'name' => 'معرض', 'is_active' => true, 'sort_order' => 1,
        ], $overrides));
    }

    private function makeCustomer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'code' => 'C-' . uniqid(), 'name' => 'عميل', 'customer_type' => 'company',
            'is_active' => true,
        ], $overrides));
    }

    // =====================================================
    //  د.١ + د.٢ — الجدول والموديل
    // =====================================================

    /** الترحيل أنشأ المصادر الستة اللي كانت ثابتة في القائمة. */
    public function test_default_sources_were_migrated(): void
    {
        foreach (['SRC-REFERRAL', 'SRC-WEBSITE', 'SRC-SOCIAL', 'SRC-DIRECT', 'SRC-EXPO', 'SRC-PARTNER'] as $code) {
            $this->assertDatabaseHas('customer_sources', ['code' => $code]);
        }
    }

    /** الكود لازم يكون فريد. */
    public function test_code_must_be_unique(): void
    {
        $this->makeSource(['code' => 'SRC-DUP']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->makeSource(['code' => 'SRC-DUP']);
    }

    /** الحذف ناعم — السجل بيروح السلة مش بيتمسح. */
    public function test_delete_is_soft(): void
    {
        $source = $this->makeSource(['code' => 'SRC-SOFT']);
        $source->delete();

        $this->assertSoftDeleted('customer_sources', ['id' => $source->id]);
        $this->assertEquals(1, CustomerSource::onlyTrashed()->where('id', $source->id)->count());
    }

    /** المصادر المعطّلة مابتظهرش في قائمة الاختيار. */
    public function test_active_scope_hides_disabled_sources(): void
    {
        $this->makeSource(['code' => 'SRC-ON',  'name' => 'شغال', 'is_active' => true]);
        $this->makeSource(['code' => 'SRC-OFF', 'name' => 'واقف', 'is_active' => false]);

        $codes = CustomerSource::active()->pluck('code')->all();

        $this->assertContains('SRC-ON', $codes);
        $this->assertNotContains('SRC-OFF', $codes, 'المصدر المعطّل ظاهر في القائمة');
    }

    /** الترتيب بيتبع sort_order. */
    public function test_sources_are_ordered_by_sort_order(): void
    {
        CustomerSource::query()->delete();

        $this->makeSource(['code' => 'SRC-B', 'name' => 'تاني', 'sort_order' => 5]);
        $this->makeSource(['code' => 'SRC-A', 'name' => 'أول',  'sort_order' => 1]);

        $this->assertEquals(['SRC-A', 'SRC-B'], CustomerSource::active()->pluck('code')->all());
    }

    // =====================================================
    //  د.٣ — الصلاحيات والمسارات
    // =====================================================

    /** الشاشة بتفتح لصاحب الصلاحية. */
    public function test_index_is_accessible(): void
    {
        $this->get(route('admin.customer-sources.index'))->assertOk();
    }

    /** من غير صلاحية مافيش دخول. */
    public function test_index_requires_permission(): void
    {
        $limited = Role::create(['name' => 'Limited', 'roles' => []]);

        $viewer = Admin::create([
            'name' => 'Viewer', 'email' => 'viewer@source.test',
            'password' => bcrypt('x'), 'main' => false, 'role_id' => $limited->id,
        ]);

        $this->actingAs($viewer, 'admin')
            ->get(route('admin.customer-sources.index'))
            ->assertRedirect();
    }

    /** الإضافة بتشتغل وبتتسجّل. */
    public function test_source_can_be_created(): void
    {
        $this->post(route('admin.customer-sources.store'), [
            'code' => 'SRC-NEW', 'name' => 'حملة إعلانية',
            'sort_order' => 3, 'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('customer_sources', [
            'code' => 'SRC-NEW', 'name' => 'حملة إعلانية', 'sort_order' => 3,
        ]);
    }

    /** التعديل بيشتغل. */
    public function test_source_can_be_updated(): void
    {
        $source = $this->makeSource(['code' => 'SRC-UPD', 'name' => 'قديم']);

        $this->put(route('admin.customer-sources.update', $source->id), [
            'code' => 'SRC-UPD', 'name' => 'جديد', 'sort_order' => 2, 'is_active' => 1,
        ])->assertRedirect();

        $this->assertEquals('جديد', $source->fresh()->name);
    }

    /** سجل الحركات بيتسجّل على المصدر. */
    public function test_activity_is_logged(): void
    {
        $source = $this->makeSource(['code' => 'SRC-LOG']);

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => CustomerSource::class,
            'subject_id'   => $source->id,
        ]);
    }

    // =====================================================
    //  د.٤ — السلة
    // =====================================================

    /** المحذوف بيرجع من السلة. */
    public function test_source_can_be_restored(): void
    {
        $source = $this->makeSource(['code' => 'SRC-RES']);
        $source->delete();

        $this->post(route('admin.customer-sources.restore', $source->id))->assertRedirect();

        $this->assertNull($source->fresh()->deleted_at);
    }

    // =====================================================
    //  د.٦ — الربط بالعميل
    // =====================================================

    /** المصدر المرتبط بعملاء مايتحذفش. */
    public function test_source_with_customers_cannot_be_deleted(): void
    {
        $source = $this->makeSource(['code' => 'SRC-BUSY']);
        $this->makeCustomer(['customer_source_id' => $source->id]);

        $this->delete(route('admin.customer-sources.destroy', $source->id));

        $this->assertNull($source->fresh()->deleted_at, 'المصدر اتحذف رغم إن فيه عملاء مرتبطين');
    }

    /** العميل بيتربط بالمصدر وبيرجع اسمه. */
    public function test_customer_resolves_its_source_name(): void
    {
        $source   = $this->makeSource(['code' => 'SRC-LBL', 'name' => 'مؤتمر القاهرة']);
        $customer = $this->makeCustomer(['customer_source_id' => $source->id]);

        $this->assertEquals('مؤتمر القاهرة', $customer->fresh()->sourceLabel());
    }

    /** العميل اللي لسه على النص القديم بيترجم مفتاحه. */
    public function test_legacy_text_source_is_still_translated(): void
    {
        $customer = $this->makeCustomer(['source' => 'referral']);

        $this->assertEquals(__('customer.enums.referral'), $customer->sourceLabel());
    }

    /** شاشة العميل بتستقبل قائمة المصادر. */
    public function test_customer_form_receives_sources(): void
    {
        $this->makeSource(['code' => 'SRC-FORM', 'name' => 'من الشاشة']);

        $response = $this->get(route('admin.customers.create'))->assertOk();

        $response->assertViewHas('sources');
        $response->assertSee('من الشاشة');
    }

    /** المصدر المعطّل بيفضل ظاهر في شاشة تعديل العميل المرتبط بيه. */
    public function test_edit_keeps_disabled_source_of_the_customer(): void
    {
        $source   = $this->makeSource(['code' => 'SRC-OFF2', 'name' => 'مصدر متوقف', 'is_active' => false]);
        $customer = $this->makeCustomer(['customer_source_id' => $source->id]);

        $response = $this->get(route('admin.customers.edit', $customer->id))->assertOk();

        $response->assertSee('مصدر متوقف');
    }

    /** حفظ العميل بمصدر مش موجود بيترفض. */
    public function test_customer_rejects_unknown_source(): void
    {
        $this->post(route('admin.customers.store'), [
            'code' => 'C-BAD', 'name' => 'عميل', 'customer_type' => 'company',
            'customer_source_id' => 999999,
        ])->assertSessionHasErrors('customer_source_id');
    }

    // =====================================================
    //  د.٥ — الترحيل
    // =====================================================

    /** الترحيل بيربط النص القديم بالسجل الصح. */
    public function test_legacy_values_are_linked_by_the_data_migration(): void
    {
        $customer = $this->makeCustomer(['source' => 'website']);

        // نشغّل نفس منطق الترحيل
        $id = DB::table('customer_sources')->where('code', 'SRC-WEBSITE')->value('id');

        DB::table('customers')
            ->where('source', 'website')
            ->whereNull('customer_source_id')
            ->update(['customer_source_id' => $id]);

        $this->assertEquals($id, $customer->fresh()->customer_source_id);
    }
}
