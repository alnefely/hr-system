<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

/**
 * تفويض قنوات البث.
 *
 * المشكلة اللي الاختبارات دي بتحرسها: مسار broadcasting/auth كان
 * بيتسجّل على الـ guard الافتراضي (web) بينما المشرفين على guard
 * اسمه admin — فالمستخدم كان بيوصل null والنتيجة 403 على كل اشتراك،
 * يعني البث اللحظي مابيشتغلش خالص.
 */
class BroadcastAuthTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $adminA;
    protected Admin $adminB;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);

        $this->adminA = Admin::create([
            'name' => 'Admin A', 'email' => 'a@b.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);

        $this->adminB = Admin::create([
            'name' => 'Admin B', 'email' => 'b@b.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id,
        ]);
    }

    public function test_auth_route_is_registered_under_the_admin_group(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('admin.broadcasting.auth'),
            'مسار تفويض البث مش مسجّل'
        );
    }

    /** المشرف بيقدر يفوّض قناته هو. */
    public function test_admin_can_authorize_their_own_channel(): void
    {
        $response = $this->actingAs($this->adminA, 'admin')
            ->post(route('admin.broadcasting.auth'), [
                'channel_name' => 'private-admin.' . $this->adminA->id,
                'socket_id'    => '123.456',
            ]);

        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'المشرف مقدرش يفوّض قناته — البث اللحظي مش هيشتغل'
        );
    }

    /**
     * منطق التفويض نفسه: صاحب القناة بس هو اللي يعدّي.
     *
     * ملاحظة: الاختبارات بتشتغل بـ BROADCAST_CONNECTION=null، و
     * NullBroadcaster::auth() بترجع null لأي قناة من غير ما تستشير
     * الـ callback أصلاً — فاختبار المسار مايقيسش المنطق. عشان كده
     * بننادي الـ callback المسجّل مباشرة.
     */
    public function test_channel_callback_allows_owner_and_denies_others(): void
    {
        $callback = $this->channelCallback('admin.{adminId}');

        $this->assertNotNull($callback, 'قناة admin.{adminId} مش مسجّلة');

        $this->actingAs($this->adminA, 'admin');

        // الـ callback بياخد المشرف كأول مُعامل لأن القناة معرّفة
        // بـ ['guards' => ['admin']]، فالفريمورك بيحلّه قبل النداء.
        $this->assertTrue(
            (bool) $callback($this->adminA, $this->adminA->id),
            'صاحب القناة المفروض يعدّي'
        );

        $this->assertFalse(
            (bool) $callback($this->adminA, $this->adminB->id),
            'مشرف قدر يشترك في قناة مشرف تاني'
        );
    }

    /** الزائر مايعديش على مستوى المنطق كمان. */
    public function test_channel_callback_denies_guests(): void
    {
        $callback = $this->channelCallback('admin.{adminId}');

        // المستخدم null = زائر
        $this->assertFalse(
            (bool) $callback(null, $this->adminA->id),
            'زائر عدّى من الـ callback'
        );
    }

    /** الزائر غير المسجّل مايقدرش يفوّض أي قناة. */
    public function test_guest_cannot_authorize_any_channel(): void
    {
        $response = $this->post(route('admin.broadcasting.auth'), [
            'channel_name' => 'private-admin.' . $this->adminA->id,
            'socket_id'    => '123.456',
        ]);

        $this->assertNotEquals(200, $response->getStatusCode(), 'زائر قدر يفوّض قناة');
    }

    /** الواجهة بتشاور على المسار الصح. */
    public function test_frontend_points_at_the_admin_auth_endpoint(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/admin/app.blade.php'));

        $this->assertStringContainsString(
            "route('admin.broadcasting.auth')",
            $layout,
            'الواجهة لسه بتستخدم المسار الافتراضي اللي بيرجع 403'
        );
    }

    /** بيجيب الـ callback المسجّل لقناة معيّنة من الـ broadcaster. */
    private function channelCallback(string $channel): ?\Closure
    {
        $broadcaster = Broadcast::driver();

        $reflection = new \ReflectionObject($broadcaster);

        if (!$reflection->hasProperty('channels')) {
            return null;
        }

        $property = $reflection->getProperty('channels');
        $property->setAccessible(true);

        return $property->getValue($broadcaster)[$channel] ?? null;
    }
}
