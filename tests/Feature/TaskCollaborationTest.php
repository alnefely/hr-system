<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeNotification;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Services\Portal\PortalAccessService;
use App\Services\Tasks\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * التعليقات والمرفقات والإشعارات (البوابة واللوحة).
 */
class TaskCollaborationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Employee $manager;

    private Employee $staff;

    private Employee $colleague;

    private Employee $stranger;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $branch = Branch::create(['name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $title = JobTitle::create(['name' => 'Staff', 'is_active' => true]);
        $make = function (string $name, string $phone, ?int $manager = null) use ($branch, $title) {
            $e = Employee::create([
                'branch_id' => $branch->id, 'job_title_id' => $title->id, 'name' => $name, 'employee_code' => 'E-' . $phone,
                'phone' => $phone, 'manager_id' => $manager, 'status' => 'active', 'basic_salary' => 3000, 'hire_date' => '2024-01-01',
            ]);
            app(PortalAccessService::class)->enable($e, 'Secret123');
            $e->forceFill(['must_change_password' => false])->save();

            return $e->fresh();
        };

        $this->manager = $make('Mona Manager', '01000000001');
        $this->staff = $make('Sara Staff', '01000000002', $this->manager->id);
        $this->colleague = $make('Karim Colleague', '01000000003');
        $this->stranger = $make('Omar Stranger', '01000000004');

        $role = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $this->admin = Admin::create(['name' => 'Root Admin', 'email' => 'root@test.com', 'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id]);
    }

    private function task(?object $creator = null, array $data = []): Task
    {
        return app(TaskService::class)->create($data + [
            'title' => 'Prepare report', 'branch_id' => $this->staff->branch_id, 'assignee_ids' => [$this->staff->id],
        ], $creator ?? $this->colleague);
    }

    private function asEmployee(Employee $e): static
    {
        return $this->actingAs($e, 'employee');
    }

    // ========================================================
    //  الإشعارات من الأحداث
    // ========================================================

    public function test_assignment_notifies_assignee_not_creator(): void
    {
        $task = $this->task();

        $n = EmployeeNotification::where('employee_id', $this->staff->id)->sole();
        $this->assertSame('task_assigned', $n->type);
        $this->assertSame(route('portal.tasks.show', $task->id, false), $n->url);
        $this->assertStringContainsString('Karim Colleague', $n->title);
        $this->assertSame(0, EmployeeNotification::where('employee_id', $this->colleague->id)->count());
    }

    public function test_review_request_notifies_employee_creator(): void
    {
        $task = $this->task();
        app(TaskService::class)->changeStatus($task, TaskStatus::InReview, $this->staff);

        $this->assertTrue(EmployeeNotification::where('employee_id', $this->colleague->id)->where('type', 'task_review_requested')->exists());
    }

    public function test_review_request_on_admin_task_notifies_admin(): void
    {
        $this->actingAs($this->admin, 'admin');
        $task = $this->task($this->admin);
        auth('admin')->logout();

        $this->asEmployee($this->staff)->post(route('portal.tasks.status', $task->id), ['status' => 'in_review'])->assertSessionHas('success');

        $n = AdminNotification::where('admin_id', $this->admin->id)->sole();
        $this->assertSame('task_review_requested', $n->type);
        $this->assertSame(route('admin.tasks.show', $task->id, false), $n->url);
    }

    public function test_self_task_review_goes_to_direct_manager(): void
    {
        $task = $this->task($this->staff);
        app(TaskService::class)->changeStatus($task, TaskStatus::InReview, $this->staff);

        $this->assertTrue(EmployeeNotification::where('employee_id', $this->manager->id)->where('type', 'task_review_requested')->exists());
    }

    public function test_completion_and_decline_notifications(): void
    {
        $task = $this->task();
        app(TaskService::class)->changeStatus($task, TaskStatus::Completed, $this->colleague);
        $this->assertTrue(EmployeeNotification::where('employee_id', $this->staff->id)->where('type', 'task_completed')->exists());

        $other = $this->task(null, ['title' => 'Second']);
        app(TaskService::class)->decline($other, $this->staff, 'busy');
        $n = EmployeeNotification::where('employee_id', $this->colleague->id)->where('type', 'task_declined')->sole();
        $this->assertStringContainsString('busy', $n->body);
    }

    // ========================================================
    //  التعليقات
    // ========================================================

    public function test_comment_with_mention_and_notifications(): void
    {
        $task = $this->task();

        $this->asEmployee($this->staff)->post(route('portal.tasks.comments.store', $task->id), [
            'body' => "first line\nsecond <b>line</b>", 'mentions' => [$this->manager->id, $this->stranger->id],
        ])->assertSessionHas('success');

        $comment = TaskComment::sole();
        // الغريب مش مرتبط بالمهمة ← الإشارة له بتتشال
        $this->assertSame([$this->manager->id], $comment->mentions);
        $this->assertSame(1, $task->fresh()->comments_count);

        $this->assertTrue(EmployeeNotification::where('employee_id', $this->manager->id)->where('type', 'task_mentioned')->exists());
        $this->assertTrue(EmployeeNotification::where('employee_id', $this->colleague->id)->where('type', 'task_commented')->exists());
        $this->assertFalse(EmployeeNotification::where('employee_id', $this->stranger->id)->exists());
        $this->assertFalse(EmployeeNotification::where('employee_id', $this->staff->id)->where('type', 'task_commented')->exists());

        // HTML في التعليق بيتعرض كنص
        $this->asEmployee($this->staff)->get(route('portal.tasks.show', $task->id))
            ->assertOk()->assertSee('second &lt;b&gt;line&lt;/b&gt;', false)->assertDontSee('second <b>line</b>', false);
    }

    public function test_reply_edit_and_delete_rules(): void
    {
        $task = $this->task();
        $this->asEmployee($this->staff)->post(route('portal.tasks.comments.store', $task->id), ['body' => 'question']);
        $parent = TaskComment::sole();

        $this->asEmployee($this->colleague)->post(route('portal.tasks.comments.store', $task->id), ['body' => 'answer', 'parent_id' => $parent->id]);
        $reply = TaskComment::where('body', 'answer')->sole();
        $this->assertSame($parent->id, $reply->parent_id);

        // مش صاحب التعليق ← مايعدّلش
        $this->asEmployee($this->colleague)->put(route('portal.tasks.comments.update', [$task->id, $parent->id]), ['body' => 'hacked'])->assertSessionHas('error');
        $this->asEmployee($this->staff)->put(route('portal.tasks.comments.update', [$task->id, $parent->id]), ['body' => 'question v2'])->assertSessionHas('success');
        $this->assertNotNull($parent->fresh()->edited_at);

        // صاحب المهمة يحذف أي تعليق، والمكلّف مايحذفش تعليق غيره
        $this->asEmployee($this->staff)->delete(route('portal.tasks.comments.destroy', [$task->id, $reply->id]))->assertSessionHas('error');
        $this->asEmployee($this->colleague)->delete(route('portal.tasks.comments.destroy', [$task->id, $parent->id]))->assertSessionHas('success');
        $this->assertSame(0, TaskComment::count());
        $this->assertSame(0, $task->fresh()->comments_count);
    }

    public function test_stranger_cannot_comment(): void
    {
        $task = $this->task();

        $this->asEmployee($this->stranger)->post(route('portal.tasks.comments.store', $task->id), ['body' => 'hi'])->assertNotFound();
        $this->assertSame(0, TaskComment::count());
    }

    public function test_empty_comment_is_rejected(): void
    {
        $task = $this->task();

        $this->asEmployee($this->staff)->post(route('portal.tasks.comments.store', $task->id), ['body' => ''])->assertSessionHasErrors('body');
    }

    // ========================================================
    //  المرفقات
    // ========================================================

    public function test_upload_download_and_delete_attachment(): void
    {
        $task = $this->task();

        $this->asEmployee($this->staff)->post(route('portal.tasks.attachments.store', $task->id), [
            'files' => [UploadedFile::fake()->create('report.pdf', 120, 'application/pdf'), UploadedFile::fake()->image('photo.png')],
        ])->assertSessionHas('success');

        $this->assertSame(2, $task->fresh()->attachments_count);
        $pdf = TaskAttachment::where('original_name', 'report.pdf')->sole();
        Storage::disk('local')->assertExists($pdf->path);
        $this->assertStringStartsWith('tasks/' . $task->id . '/', $pdf->path);

        $this->asEmployee($this->staff)->get(route('portal.tasks.attachments.download', [$task->id, $pdf->id]))
            ->assertOk()->assertHeader('content-disposition', 'attachment; filename=report.pdf');

        $png = TaskAttachment::where('original_name', 'photo.png')->sole();
        $this->asEmployee($this->staff)->get(route('portal.tasks.attachments.download', [$task->id, $png->id, 'inline' => 1]))
            ->assertOk()->assertHeader('content-type', 'image/png');

        // الغريب مايقدرش ينزّل
        $this->asEmployee($this->stranger)->get(route('portal.tasks.attachments.download', [$task->id, $pdf->id]))->assertNotFound();

        // صاحب الملف يحذفه
        $this->asEmployee($this->staff)->delete(route('portal.tasks.attachments.destroy', [$task->id, $pdf->id]))->assertSessionHas('success');
        Storage::disk('local')->assertMissing($pdf->path);
        $this->assertSame(1, $task->fresh()->attachments_count);
    }

    public function test_dangerous_files_are_rejected(): void
    {
        $task = $this->task();

        $this->asEmployee($this->staff)->post(route('portal.tasks.attachments.store', $task->id), [
            'files' => [UploadedFile::fake()->create('evil.php', 1, 'application/x-php')],
        ])->assertSessionHasErrors('files.0');

        $this->asEmployee($this->staff)->post(route('portal.tasks.attachments.store', $task->id), [
            'files' => [UploadedFile::fake()->create('page.html', 1, 'text/html')],
        ])->assertSessionHasErrors('files.0');

        $this->assertSame(0, TaskAttachment::count());
    }

    public function test_attachment_of_another_task_is_not_reachable(): void
    {
        $a = $this->task();
        $b = $this->task(null, ['title' => 'Other']);

        $this->asEmployee($this->staff)->post(route('portal.tasks.attachments.store', $b->id), ['files' => [UploadedFile::fake()->create('b.pdf', 10, 'application/pdf')]]);
        $file = TaskAttachment::sole();

        $this->asEmployee($this->staff)->get(route('portal.tasks.attachments.download', [$a->id, $file->id]))->assertNotFound();
    }

    public function test_comment_with_files_and_admin_side(): void
    {
        $task = $this->task();
        $this->actingAs($this->admin, 'admin');

        $this->post(route('admin.tasks.comments.store', $task->id), [
            'body' => 'see attached', 'files' => [UploadedFile::fake()->create('spec.docx', 20)],
        ])->assertSessionHas('success');

        $comment = TaskComment::sole();
        $this->assertSame(1, $comment->attachments()->count());
        $this->assertTrue($comment->author->is($this->admin));
        $this->assertTrue(EmployeeNotification::where('employee_id', $this->staff->id)->where('type', 'task_commented')->exists());

        $this->get(route('admin.tasks.show', $task->id))->assertOk()->assertSee('see attached')->assertSee('spec.docx');
    }

    // ========================================================
    //  شاشات إشعارات البوابة
    // ========================================================

    public function test_portal_notifications_endpoints(): void
    {
        $task = $this->task();
        $mine = EmployeeNotification::where('employee_id', $this->staff->id)->sole();
        $this->task(null, ['title' => 'For manager', 'assignee_ids' => [$this->manager->id]]);
        $theirs = EmployeeNotification::where('employee_id', $this->manager->id)->sole();

        $this->asEmployee($this->staff)->get(route('portal.notifications.index'))->assertOk()->assertSee($mine->title);
        $this->asEmployee($this->staff)->getJson(route('portal.notifications.latest'))->assertOk()->assertJsonPath('unread_count', 1);

        // إشعار موظف تاني ← 404
        $this->asEmployee($this->staff)->post(route('portal.notifications.read', $theirs->id))->assertNotFound();

        $this->asEmployee($this->staff)->post(route('portal.notifications.read', $mine->id))->assertRedirect(route('portal.tasks.show', $task->id, false));
        $this->assertNotNull($mine->fresh()->read_at);

        $this->asEmployee($this->manager)->postJson(route('portal.notifications.read-all'))->assertJsonPath('unread_count', 0);
        $this->assertNotNull($theirs->fresh()->read_at);

        // الجرس في رأس البوابة
        $this->asEmployee($this->staff)->get(route('portal.home'))->assertOk()->assertSee('portalNotificationsBell', false);
    }

    public function test_employee_channel_authorization(): void
    {
        config(['broadcasting.default' => 'pusher', 'broadcasting.connections.pusher.key' => 'k', 'broadcasting.connections.pusher.secret' => 's', 'broadcasting.connections.pusher.app_id' => '1']);
        app()->forgetInstance('Illuminate\Broadcasting\BroadcastManager');
        \Illuminate\Support\Facades\Broadcast::clearResolvedInstances();
        // القنوات بتتسجّل على الـ broadcaster الجديد
        (new \App\Providers\AppServiceProvider(app()))->boot();

        $this->asEmployee($this->staff)->post(route('portal.broadcasting.auth'), [
            'channel_name' => 'private-employee.' . $this->staff->id, 'socket_id' => '123.456',
        ])->assertOk();

        $this->asEmployee($this->staff)->post(route('portal.broadcasting.auth'), [
            'channel_name' => 'private-employee.' . $this->manager->id, 'socket_id' => '123.456',
        ])->assertForbidden();
    }
}
