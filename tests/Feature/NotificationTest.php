<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Events\AdminNotificationCreated;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveBalance;
use App\Models\JobTitle;
use App\Models\Role;
use App\Services\Notification\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * نظام الإشعارات: الإنشاء، العزل بالصلاحية والفرع، والبث.
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $mainAdmin;
    protected Admin $branchAdmin;
    protected Admin $unrelatedAdmin;
    protected Branch $branchA;
    protected Branch $branchB;
    protected Employee $employeeA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchA = Branch::create([
            'name' => 'Branch A', 'code' => 'NA', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);
        $this->branchB = Branch::create([
            'name' => 'Branch B', 'code' => 'NB', 'country' => 'Oman',
            'currency' => 'OMR', 'is_active' => true,
        ]);

        $jobTitle = JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        $fullRole = Role::create(['name' => 'Full', 'roles' => all_roles()]);
        $noLeaveRole = Role::create(['name' => 'No Leave', 'roles' => ['dashboard_show']]);

        $this->mainAdmin = Admin::create([
            'name' => 'Root', 'email' => 'root@n.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $fullRole->id,
        ]);

        $this->employeeA = Employee::create([
            'branch_id' => $this->branchA->id, 'job_title_id' => $jobTitle->id,
            'name' => 'Manager A', 'employee_code' => 'N-A1', 'phone' => '0100',
            'status' => 'active', 'basic_salary' => 5000, 'hire_date' => '2024-01-01',
            'annual_leave_entitlement' => 21,
        ]);

        $this->branchAdmin = Admin::create([
            'name' => 'Branch A Admin', 'email' => 'a@n.test',
            'password' => bcrypt('x'), 'main' => false,
            'role_id' => $fullRole->id, 'employee_id' => $this->employeeA->id,
        ]);

        // مشرف مالوش صلاحية الإجازات
        $this->unrelatedAdmin = Admin::create([
            'name' => 'No Access', 'email' => 'no@n.test',
            'password' => bcrypt('x'), 'main' => true, 'role_id' => $noLeaveRole->id,
        ]);
    }

    // ========================================================
    //  الاستهداف
    // ========================================================

    public function test_only_admins_with_the_required_permission_are_notified(): void
    {
        $this->actingAs($this->mainAdmin, 'admin');

        app(NotificationService::class)->notifyEligible(
            type: NotificationType::LeaveRequested,
            title: 'Test',
            body: 'Body',
        );

        $this->assertDatabaseHas('admin_notifications', ['admin_id' => $this->mainAdmin->id]);
        $this->assertDatabaseMissing('admin_notifications', ['admin_id' => $this->unrelatedAdmin->id]);
    }

    public function test_branch_admin_is_not_notified_about_another_branch(): void
    {
        $this->actingAs($this->mainAdmin, 'admin');

        app(NotificationService::class)->notifyEligible(
            type: NotificationType::LeaveRequested,
            title: 'Branch B event',
            branchId: $this->branchB->id,
        );

        $this->assertDatabaseMissing('admin_notifications', ['admin_id' => $this->branchAdmin->id]);
        // المدير العام بيستقبل كل حاجة
        $this->assertDatabaseHas('admin_notifications', ['admin_id' => $this->mainAdmin->id]);
    }

    public function test_branch_admin_is_notified_about_own_branch(): void
    {
        $this->actingAs($this->mainAdmin, 'admin');

        app(NotificationService::class)->notifyEligible(
            type: NotificationType::LeaveRequested,
            title: 'Branch A event',
            branchId: $this->branchA->id,
        );

        $this->assertDatabaseHas('admin_notifications', ['admin_id' => $this->branchAdmin->id]);
    }

    public function test_causer_is_not_notified_about_their_own_action(): void
    {
        $this->actingAs($this->mainAdmin, 'admin');

        app(NotificationService::class)->notifyEligible(
            type: NotificationType::LeaveRequested,
            title: 'Self action',
            excludeAdminId: $this->mainAdmin->id,
        );

        $this->assertDatabaseMissing('admin_notifications', ['admin_id' => $this->mainAdmin->id]);
    }

    // ========================================================
    //  البث
    // ========================================================

    public function test_creating_a_notification_broadcasts_it(): void
    {
        Event::fake([AdminNotificationCreated::class]);
        config(['broadcasting.default' => 'log']);

        $this->actingAs($this->mainAdmin, 'admin');

        app(NotificationService::class)->send(
            adminId: $this->mainAdmin->id,
            type: NotificationType::LeaveRequested,
            title: 'Broadcast me',
        );

        Event::assertDispatched(AdminNotificationCreated::class);
    }

    public function test_broadcast_channel_is_private_to_the_recipient(): void
    {
        $notification = AdminNotification::create([
            'admin_id' => $this->branchAdmin->id,
            'type'     => NotificationType::LeaveRequested->value,
            'title'    => 'Private',
        ]);

        $channels = (new AdminNotificationCreated($notification))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals(
            'private-admin.' . $this->branchAdmin->id,
            $channels[0]->name
        );
    }

    // ========================================================
    //  الواجهة
    // ========================================================

    public function test_admin_only_sees_their_own_notifications(): void
    {
        AdminNotification::create([
            'admin_id' => $this->mainAdmin->id,
            'type' => NotificationType::LeaveRequested->value, 'title' => 'Mine',
        ]);
        AdminNotification::create([
            'admin_id' => $this->branchAdmin->id,
            'type' => NotificationType::LeaveRequested->value, 'title' => 'Not mine',
        ]);

        $response = $this->actingAs($this->mainAdmin, 'admin')
            ->getJson(route('admin.notifications.latest'))
            ->assertOk();

        $titles = collect($response->json('notifications'))->pluck('title');

        $this->assertContains('Mine', $titles);
        $this->assertNotContains('Not mine', $titles);
    }

    public function test_cannot_mark_another_admins_notification_as_read(): void
    {
        $other = AdminNotification::create([
            'admin_id' => $this->branchAdmin->id,
            'type' => NotificationType::LeaveRequested->value, 'title' => 'Theirs',
        ]);

        $this->actingAs($this->mainAdmin, 'admin')
            ->post(route('admin.notifications.read', $other->id))
            ->assertNotFound();

        $this->assertNull($other->fresh()->read_at);
    }

    public function test_mark_all_as_read_only_affects_own_notifications(): void
    {
        AdminNotification::create([
            'admin_id' => $this->mainAdmin->id,
            'type' => NotificationType::LeaveRequested->value, 'title' => 'Mine',
        ]);
        $other = AdminNotification::create([
            'admin_id' => $this->branchAdmin->id,
            'type' => NotificationType::LeaveRequested->value, 'title' => 'Theirs',
        ]);

        $this->actingAs($this->mainAdmin, 'admin')
            ->post(route('admin.notifications.read-all'));

        $this->assertEquals(0, AdminNotification::forAdmin($this->mainAdmin->id)->unread()->count());
        $this->assertNull($other->fresh()->read_at, 'اتغيّرت إشعارات مشرف تاني');
    }

    public function test_notifications_page_renders(): void
    {
        $this->actingAs($this->mainAdmin, 'admin')
            ->get(route('admin.notifications.index'))
            ->assertOk();
    }

    public function test_notifications_require_permission(): void
    {
        $this->actingAs($this->unrelatedAdmin, 'admin')
            ->get(route('admin.notifications.index'))
            ->assertRedirect(route('admin.error_403'));
    }

    // ========================================================
    //  التكامل مع الإجازات
    // ========================================================

    public function test_submitting_a_leave_request_notifies_reviewers(): void
    {
        $this->actingAs($this->mainAdmin, 'admin');

        EmployeeLeaveBalance::create([
            'employee_id' => $this->employeeA->id, 'year' => 2026,
            'annual_balance' => 21, 'casual_balance' => 7, 'sick_balance' => 14,
        ]);

        $this->post(route('admin.leaves.store'), [
            'employee_id' => $this->employeeA->id,
            'leave_type'  => 'annual',
            'start_date'  => '2026-06-01',
            'end_date'    => '2026-06-03',
            'days_count'  => 3,
        ])->assertSessionHas('success');

        // مشرف الفرع بيتبلّغ، ومقدّم الطلب لأ
        $this->assertDatabaseHas('admin_notifications', [
            'admin_id' => $this->branchAdmin->id,
            'type'     => NotificationType::LeaveRequested->value,
        ]);
        $this->assertDatabaseMissing('admin_notifications', [
            'admin_id' => $this->mainAdmin->id,
            'type'     => NotificationType::LeaveRequested->value,
        ]);
    }

    public function test_notification_failure_does_not_break_the_original_action(): void
    {
        $this->actingAs($this->mainAdmin, 'admin');

        // نوع غير موجود في جدول الإشعارات هيفشل، لكن مايرميش استثناء
        $result = app(NotificationService::class)->send(
            adminId: 999999, // مشرف غير موجود => قيد أجنبي
            type: NotificationType::LeaveRequested,
            title: 'Should fail quietly',
        );

        $this->assertNull($result, 'المفروض يرجع null بدل ما يرمي استثناء');
    }

    /**
     * زرار "عرض" بيودّي لمكان مفيد.
     *
     * كان بيعمل ريلود وبس لأن الإشعارات كان رابطها هو صفحة
     * الإشعارات نفسها، فالتحويل بيرجّعك لنفس المكان.
     */
    public function test_view_redirects_to_the_related_record(): void
    {
        $this->actingAs($this->mainAdmin, 'admin');

        $notification = AdminNotification::create([
            'admin_id' => $this->mainAdmin->id,
            'type'     => 'leave_requested',
            'title'    => 'طلب إجازة',
            'url'      => route('admin.leaves.index', ['employee_id' => 5]),
        ]);

        $this->post(route('admin.notifications.read', $notification->id))
            ->assertRedirect($notification->url);

        $this->assertNotNull($notification->fresh()->read_at, 'الإشعار مااتعلّمش كمقروء');
    }

    /**
     * الضغط على إشعار في الجرس: بيتعلّم مقروء (JSON) والعداد بيقل.
     * قبل كده الجرس كان رابط مباشر للصفحة ومابيعدّيش على مسار القراءة،
     * فالإشعار كان بيفضل غير مقروء.
     */
    public function test_bell_marks_notification_read_before_navigating(): void
    {
        $this->actingAs($this->mainAdmin, 'admin');

        $make = fn () => AdminNotification::create([
            'admin_id' => $this->mainAdmin->id, 'type' => 'leave_requested', 'title' => 'طلب',
            'url' => route('admin.leaves.index'),
        ]);
        $first = $make();
        $make();

        // الجرس بيعلّم الإشعار وبيعرف حالته
        $html = $this->get(route('admin.notifications.index'))->assertOk()->getContent();
        $this->assertStringContainsString('data-unread=', $html);
        $this->assertStringContainsString('notification-item', $html);
        $this->assertStringContainsString(str_replace('/', '\/', route('admin.notifications.read', ['notification' => '__ID__'])), $html);

        // نفس الطلب اللي الجرس بيبعته
        $this->postJson(route('admin.notifications.read', $first->id))
            ->assertOk()
            ->assertJson(['success' => true, 'unread_count' => 1]);

        $this->assertNotNull($first->fresh()->read_at);
        $this->getJson(route('admin.notifications.latest'))->assertJson(['unread_count' => 1]);
    }

    /** أزرار القراءة في شاشة الإشعارات مالهاش نافذة تأكيد (مش إجراء مهم). */
    public function test_notification_read_forms_skip_confirmation(): void
    {
        $this->actingAs($this->mainAdmin, 'admin');

        AdminNotification::create([
            'admin_id' => $this->mainAdmin->id, 'type' => 'leave_requested', 'title' => 'طلب',
            'url' => route('admin.leaves.index'),
        ]);

        $html = $this->get(route('admin.notifications.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/notifications\/read-all"[^>]*data-no-confirm/', $html);
        $this->assertMatchesRegularExpression('/notifications\/\d+\/read"[^>]*data-no-confirm/', $html);
    }

    /** لو الرابط هو نفس الصفحة الحالية بنوضّح بدل التحويل الصامت. */
    public function test_same_page_link_explains_instead_of_reloading(): void
    {
        $this->actingAs($this->mainAdmin, 'admin');

        $index = route('admin.notifications.index');

        $notification = AdminNotification::create([
            'admin_id' => $this->mainAdmin->id,
            'type'     => 'leave_requested',
            'title'    => 'إشعار من غير تفاصيل',
            'url'      => $index,
        ]);

        $this->withHeaders(['referer' => $index])
            ->post(route('admin.notifications.read', $notification->id))
            ->assertRedirect($index)
            ->assertSessionHas('info');
    }

    /** إشعار من غير رابط بيرجّع لصفحة الإشعارات. */
    public function test_notification_without_url_falls_back(): void
    {
        $this->actingAs($this->mainAdmin, 'admin');

        $notification = AdminNotification::create([
            'admin_id' => $this->mainAdmin->id,
            'type'     => 'leave_requested',
            'title'    => 'من غير رابط',
            'url'      => null,
        ]);

        $this->post(route('admin.notifications.read', $notification->id))
            ->assertRedirect(route('admin.notifications.index'));
    }

    // ========================================================
    //  روابط الإشعارات
    // ========================================================

    /**
     * الإشعار اللي اتعمل من أمر مجدول بياخد دومين APP_URL — لو كان
     * localhost، الضغط كان بيودّي على localhost. الرابط بقى نسبي.
     */
    private function notificationWithUrl(string $url): \App\Models\AdminNotification
    {
        // كتابة مباشرة بالرابط الكامل زي الصفوف القديمة اللي في قاعدة البيانات
        $id = \Illuminate\Support\Facades\DB::table('admin_notifications')->insertGetId([
            'admin_id'   => $this->mainAdmin->id,
            'type'       => 'petty_cash_low',
            'title'      => 'تنبيه',
            'url'        => $url,
            'level'      => 'danger',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return \App\Models\AdminNotification::find($id);
    }

    public function test_stored_absolute_url_is_served_as_relative(): void
    {
        $notification = $this->notificationWithUrl('http://localhost/admin/expenses?branch_id=1&month=9');

        $this->assertEquals('/admin/expenses?branch_id=1&month=9', $notification->url);
        $this->assertEquals('/admin/expenses?branch_id=1&month=9', $notification->toPayload()['url'], 'الجرس لسه بياخد الرابط الكامل');
    }

    /** الضغط من شاشة الإشعارات بيحوّل على نفس الدومين اللي المستخدم فاتح عليه. */
    public function test_reading_redirects_to_current_host_not_stored_host(): void
    {
        $notification = $this->notificationWithUrl('http://localhost/admin/expenses?branch_id=1');

        $this->actingAs($this->mainAdmin, 'admin');

        $response = $this->post(route('admin.notifications.read', $notification->id));

        $location = $response->headers->get('Location');

        $this->assertStringNotContainsString('localhost', $location, 'لسه بيحوّل على localhost');
        $this->assertStringEndsWith('/admin/expenses?branch_id=1', $location);
    }

    /** والإشعارات الجديدة بتتخزّن نسبي من الأول. */
    public function test_new_notifications_store_relative_urls(): void
    {
        config(['app.url' => 'http://localhost']);

        $notification = app(NotificationService::class)->send(
            $this->mainAdmin->id,
            \App\Enums\NotificationType::PettyCashLow,
            'تنبيه',
            null,
            'http://localhost/admin/sales/5',
        );

        $this->assertEquals(
            '/admin/sales/5',
            \Illuminate\Support\Facades\DB::table('admin_notifications')->where('id', $notification->id)->value('url'),
            'الرابط اتخزّن بالدومين'
        );
    }

    /** البث فوري — مايستناش worker مش موجود. */
    public function test_broadcast_does_not_wait_for_a_queue_worker(): void
    {
        $this->assertInstanceOf(
            \Illuminate\Contracts\Broadcasting\ShouldBroadcastNow::class,
            new AdminNotificationCreated($this->notificationWithUrl('/admin')),
            'البث لسه بيعدّي على الطابور'
        );
    }

    // ========================================================
    //  حذف الإشعارات القديمة
    // ========================================================

    private function aged(int $daysAgo, bool $read): int
    {
        return \Illuminate\Support\Facades\DB::table('admin_notifications')->insertGetId([
            'admin_id'   => $this->mainAdmin->id,
            'type'       => 'petty_cash_low',
            'title'      => "{$daysAgo}d " . ($read ? 'read' : 'unread'),
            'level'      => 'info',
            'read_at'    => $read ? now()->subDays($daysAgo) : null,
            'created_at' => now()->subDays($daysAgo),
            'updated_at' => now()->subDays($daysAgo),
        ]);
    }

    /**
     * المقروء بيتحذف بعد ٣٠ يوم، وأي إشعار بعد ٩٠.
     */
    public function test_prune_respects_read_and_overall_retention(): void
    {
        $readOld     = $this->aged(40, true);    // مقروء وأقدم من ٣٠ ← يتحذف
        $readNew     = $this->aged(10, true);    // مقروء وحديث ← يفضل
        $unreadMid   = $this->aged(40, false);   // ماتقراش، أقل من ٩٠ ← يفضل
        $unreadOld   = $this->aged(100, false);  // ماتقراش وأقدم من ٩٠ ← يتحذف

        $this->artisan('notifications:prune')->assertSuccessful();

        $remaining = \App\Models\AdminNotification::pluck('id')->all();

        $this->assertNotContains($readOld, $remaining, 'المقروء القديم مااتحذفش');
        $this->assertNotContains($unreadOld, $remaining, 'غير المقروء الأقدم من ٩٠ يوم مااتحذفش');
        $this->assertContains($readNew, $remaining, 'اتحذف مقروء حديث');
        $this->assertContains($unreadMid, $remaining, 'اتحذف غير مقروء لسه في المدة');
    }

    /** المعاينة مابتحذفش. */
    public function test_prune_dry_run_deletes_nothing(): void
    {
        $this->aged(100, false);
        $before = \App\Models\AdminNotification::count();

        $this->artisan('notifications:prune --dry-run')->assertSuccessful();

        $this->assertEquals($before, \App\Models\AdminNotification::count());
    }

    /** ومتجدول يومياً. */
    public function test_prune_is_scheduled(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->map(fn ($e) => $e->command);

        $this->assertTrue(
            $events->contains(fn ($c) => str_contains((string) $c, 'notifications:prune')),
            'حذف الإشعارات مش متجدول'
        );
    }
}
