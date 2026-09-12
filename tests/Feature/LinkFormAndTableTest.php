<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Link;
use App\Models\Role;
use App\Services\Links\ShortCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * شاشة الإنشاء وجدول العرض.
 */
class LinkFormAndTableTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions): Admin
    {
        $role = Role::create(['name' => 'R' . uniqid(), 'roles' => $permissions]);

        return Admin::create([
            'name'     => 'A' . uniqid(),
            'email'    => uniqid() . '@form.test',
            'password' => bcrypt('x'),
            'main'     => true,
            'role_id'  => $role->id,
        ]);
    }

    /**
     * أول صف من جدول DataTables.
     *
     * X-Requested-With ضروري: الكنترولر بيفحص $request->ajax()
     * اللي بيعتمد على الهيدر ده، و getJson لوحده بيبعت Accept بس.
     */
    private function dataTableRow(int $index = 0): array
    {
        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.links.index') . '?draw=1&start=0&length=10');

        $response->assertOk();

        $rows = $response->json('data');

        $this->assertNotEmpty($rows, 'الجدول رجع فاضي');

        return $rows[$index];
    }

    private function link(array $attributes = []): Link
    {
        return Link::create(array_merge([
            'short_code'   => 'Frm1234',
            'original_url' => 'https://example.com/page',
            'is_active'    => true,
        ], $attributes));
    }

    /** شاشة الإنشاء بتعرض كود مولّد جاهز في الحقل. */
    public function test_create_screen_prefills_a_generated_code(): void
    {
        $this->actingAs($this->admin(['link_show', 'link_create']), 'admin');

        $response = $this->get(route('admin.links.create'));

        $response->assertOk();

        // الحقل بيحمل قيمة فعلية مش فاضي
        preg_match('/name="short_code"[^>]*value="([^"]*)"/', $response->getContent(), $m);

        $this->assertNotEmpty($m[1] ?? '', 'الحقل فاضي — مافيش كود مولّد');
        $this->assertEquals(
            config('links.code_length'),
            strlen($m[1]),
            'الكود المولّد مش بطول code_length'
        );
    }

    /** والكود المولّد صالح فعلاً — مش مجرد نص. */
    public function test_prefilled_code_is_usable(): void
    {
        $this->actingAs($this->admin(['link_show', 'link_create']), 'admin');

        $response = $this->get(route('admin.links.create'));
        preg_match('/name="short_code"[^>]*value="([^"]*)"/', $response->getContent(), $m);

        $this->assertEmpty(
            app(ShortCodeGenerator::class)->validateCustom($m[1]),
            'الكود المعروض في الحقل مرفوض من التحقق'
        );
    }

    /** الحقل بيمنع تجاوز الطول المسموح. */
    public function test_code_field_enforces_configured_length(): void
    {
        $this->actingAs($this->admin(['link_show', 'link_create']), 'admin');

        $max = ShortCodeGenerator::maxLength();

        $this->get(route('admin.links.create'))
            ->assertSee('maxlength="' . $max . '"', false)
            ->assertSee('minlength="' . ShortCodeGenerator::MIN_LENGTH . '"', false);
    }

    /**
     * والحد في الواجهة نفس الحد في التحقق.
     *
     * لو الحقل بيمنع أكتر من ٧ والـ validation بيقبل ٣٢، بيبقى
     * فيه تناقض — والعكس بيدي رسالة خطأ للمستخدم من غير سبب ظاهر.
     */
    public function test_field_limit_matches_validation_limit(): void
    {
        $this->actingAs($this->admin(['link_show', 'link_create']), 'admin');

        $tooLong = str_repeat('a', ShortCodeGenerator::maxLength() + 1);

        $this->post(route('admin.links.store'), [
            'original_url' => 'https://example.com',
            'short_code'   => $tooLong,
        ])->assertSessionHasErrors('short_code');

        $this->assertDatabaseCount('links', 0);
    }

    /** زر توليد كود جديد بيشتغل. */
    public function test_generate_code_endpoint_returns_a_valid_code(): void
    {
        $this->actingAs($this->admin(['link_show', 'link_create']), 'admin');

        $response = $this->getJson(route('admin.links.generate-code'));

        $response->assertOk();

        $code = $response->json('code');

        $this->assertNotEmpty($code);
        $this->assertEmpty(app(ShortCodeGenerator::class)->validateCustom($code), 'الكود المولّد مرفوض');
    }

    /**
     * زر سلة المحذوفات موجود في الشاشة.
     *
     * الراوت والشاشة كانوا موجودين من غير أي زر يوصّل ليهم — يعني
     * الصلاحية موجودة والمستخدم مش قادر يستعملها.
     */
    public function test_index_has_a_trash_button(): void
    {
        $this->actingAs($this->admin(['link_show', 'link_trash']), 'admin');

        $this->get(route('admin.links.index'))
            ->assertOk()
            ->assertSee(route('admin.links.trash'), false)
            ->assertSee(__('link.trash'));
    }

    /** ومابيظهرش من غير صلاحية. */
    public function test_trash_button_hidden_without_permission(): void
    {
        $this->actingAs($this->admin(['link_show']), 'admin');

        $this->get(route('admin.links.index'))
            ->assertOk()
            ->assertDontSee(route('admin.links.trash'), false);
    }

    /** وبيعرض عدد المحذوفات. */
    public function test_trash_button_shows_count(): void
    {
        $link = $this->link();
        $link->delete();

        $this->actingAs($this->admin(['link_show', 'link_trash']), 'admin');

        $this->get(route('admin.links.index'))
            ->assertOk()
            ->assertSee('badge bg-danger', false);
    }

    /**
     * حقل الكود متسق الاتجاه.
     *
     * المجموعة لازم تبقى ltr: لو ورثت RTL من الصفحة، البادئة /s/
     * والزر بيتبعتروا حوالين الحقل.
     */
    public function test_code_field_group_is_ltr(): void
    {
        $this->actingAs($this->admin(['link_show', 'link_create']), 'admin');

        $html = $this->get(route('admin.links.create'))->assertOk()->getContent();

        $this->assertStringContainsString('<div class="input-group" dir="ltr">', $html, 'مجموعة الحقل مش ltr');
    }

    /**
     * الاستعادة من السلة بتطلب تأكيد.
     *
     * كانت submit مباشر — ضغطة واحدة بالغلط بترجّع رابط اتشال
     * عن قصد، وممكن يكون اتشال لأنه بيوصّل لمكان مش صح.
     */
    public function test_restore_asks_for_confirmation(): void
    {
        $link = $this->link();
        $link->delete();

        $this->actingAs($this->admin(['link_show', 'link_trash', 'link_restore']), 'admin');

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.links.trash') . '?draw=1&start=0&length=10');

        $response->assertOk();

        $cell = $response->json('data.0.actions');

        $this->assertStringContainsString('btn-swal-confirm', $cell, 'الاستعادة من غير تأكيد');
        $this->assertStringNotContainsString('type="submit"', $cell, 'لسه فيه زر submit مباشر');

        // والرسالة مكتوبة فعلاً مش النص العام
        $this->assertStringContainsString(__('link.confirm.restore_title'), $cell, 'عنوان التأكيد ناقص');
        $this->assertStringContainsString($link->short_code, $cell, 'الرسالة مش بتقول أي رابط');
    }

    /** والاستعادة نفسها بتشتغل. */
    public function test_restore_works(): void
    {
        $link = $this->link();
        $link->delete();

        $this->actingAs($this->admin(['link_show', 'link_trash', 'link_restore']), 'admin');

        $this->post(route('admin.links.restore', $link->id))->assertRedirect();

        $this->assertNull($link->fresh()->deleted_at, 'الرابط مارجعش');
    }

    /** الرابط المختصر في الجدول قابل للضغط وبيفتح في تاب جديدة. */
    public function test_table_short_url_is_clickable_in_new_tab(): void
    {
        $link = $this->link();

        $this->actingAs($this->admin(['link_show']), 'admin');

        $cell = $this->dataTableRow()['short_url'];

        $this->assertStringContainsString('href="' . e($link->short_url) . '"', $cell, 'الرابط مش قابل للضغط');
        $this->assertStringContainsString('target="_blank"', $cell, 'مش بيفتح في تاب جديدة');
        $this->assertStringContainsString('rel="noopener noreferrer"', $cell, 'ناقص حماية noopener');
    }

    /** والوجهة الطويلة بتتقطع. */
    public function test_table_truncates_long_destination(): void
    {
        $long = 'https://example.com/' . str_repeat('very-long-path-segment/', 12);

        $link = $this->link(['short_code' => 'Lng1234', 'original_url' => $long]);

        $this->actingAs($this->admin(['link_show']), 'admin');

        $cell = $this->dataTableRow()['destination'];

        $this->assertStringContainsString('text-truncate', $cell, 'الوجهة مش بتتقطع');
        $this->assertStringContainsString('max-width', $cell, 'مافيش عرض أقصى للعمود');

        // والنص الكامل موجود في التلميح
        $this->assertStringContainsString('title="' . e($long) . '"', $cell, 'النص الكامل مش في التلميح');
    }
}
