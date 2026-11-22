<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Events\TaskBoardChanged;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Services\Portal\PortalAccessService;
use App\Services\Tasks\TaskService;
use App\Support\BranchScopeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * طرق العرض: لوحة كانبان (السحب، الترتيب، التحديث اللحظي) والتقويم.
 */
class TaskViewsTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Admin $admin;

    private Employee $staff;

    private Employee $colleague;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $title = JobTitle::create(['name' => 'Staff', 'is_active' => true]);
        foreach (['staff' => '01000000001', 'colleague' => '01000000002'] as $prop => $phone) {
            $e = Employee::create([
                'branch_id' => $this->branch->id, 'job_title_id' => $title->id, 'name' => ucfirst($prop), 'employee_code' => 'E-' . $phone,
                'phone' => $phone, 'status' => 'active', 'basic_salary' => 3000, 'hire_date' => '2024-01-01',
            ]);
            app(PortalAccessService::class)->enable($e, 'Secret123');
            $e->forceFill(['must_change_password' => false])->save();
            $this->{$prop} = $e->fresh();
        }

        $role = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $this->admin = Admin::create(['name' => 'Root', 'email' => 'root@test.com', 'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id]);
        $this->actingAs($this->admin, 'admin');
    }

    private function task(string $title, array $data = [], ?object $creator = null): Task
    {
        return app(TaskService::class)->create($data + [
            'title' => $title, 'branch_id' => $this->branch->id, 'assignee_ids' => [$this->staff->id],
        ], $creator ?? $this->admin);
    }

    public function test_admin_board_columns(): void
    {
        $this->task('Fresh one');
        $doing = $this->task('Doing one');
        app(TaskService::class)->changeStatus($doing, TaskStatus::InProgress, $this->admin);
        $old = $this->task('Done long ago');
        app(TaskService::class)->changeStatus($old, TaskStatus::Completed, $this->admin);
        $old->forceFill(['completed_at' => now()->subDays(40)])->saveQuietly();
        $cancelled = $this->task('Cancelled one');
        app(TaskService::class)->changeStatus($cancelled, TaskStatus::Cancelled, $this->admin);

        $this->get(route('admin.tasks.board'))
            ->assertOk()
            ->assertSee('Fresh one')->assertSee('Doing one')
            ->assertDontSee('Done long ago')->assertDontSee('Cancelled one')
            ->assertSee('data-status="in_review"', false)
            ->assertSee('Sortable.min.js', false)
            ->assertSee('tasks-board.all', false);
    }

    public function test_move_changes_status_and_order(): void
    {
        $a = $this->task('A');
        $b = $this->task('B');
        app(TaskService::class)->changeStatus($b, TaskStatus::InProgress, $this->admin);

        $this->postJson(route('admin.tasks.move', $a->id), ['status' => 'in_progress', 'order' => [$a->id, $b->id]])
            ->assertOk()->assertJsonPath('ok', true);

        $this->assertSame('in_progress', $a->fresh()->status);
        $this->assertSame(0, $a->fresh()->position);
        $this->assertSame(1, $b->fresh()->position);

        // ترتيب بس في نفس العمود
        $this->postJson(route('admin.tasks.move', $b->id), ['status' => 'in_progress', 'order' => [$b->id, $a->id]])->assertOk();
        $this->assertSame(0, $b->fresh()->position);
    }

    public function test_move_errors_are_reported(): void
    {
        $task = $this->task('Review me', [], $this->colleague);
        app(TaskService::class)->changeStatus($task, TaskStatus::InReview, $this->staff);

        // الإرجاع من غير سبب ← 422 ومعاه طلب السبب
        $this->postJson(route('admin.tasks.move', $task->id), ['status' => 'in_progress'])
            ->assertStatus(422)->assertJsonPath('needs_note', true);

        $this->postJson(route('admin.tasks.move', $task->id), ['status' => 'in_progress', 'note' => 'fix it'])->assertOk();
        $this->assertSame('in_progress', $task->fresh()->status);

        $this->postJson(route('admin.tasks.move', $task->id), ['status' => 'cancelled'])->assertStatus(422);
    }

    public function test_live_card_endpoint_respects_filters(): void
    {
        $project = Project::create(['branch_id' => $this->branch->id, 'name' => 'P']);
        $inside = $this->task('Inside', ['project_id' => $project->id]);
        $outside = $this->task('Outside');

        $this->getJson(route('admin.tasks.board.card', $inside->id) . '?project_id=' . $project->id)
            ->assertOk()->assertJsonPath('status', 'new')->assertSee('Inside');

        $this->get(route('admin.tasks.board.card', $outside->id) . '?project_id=' . $project->id)->assertNoContent();

        app(TaskService::class)->delete($outside, $this->admin);
        $this->get(route('admin.tasks.board.card', $outside->id))->assertNoContent();
    }

    public function test_changes_broadcast_to_board_channels(): void
    {
        config(['broadcasting.default' => 'log']);
        Event::fake([TaskBoardChanged::class]);

        $task = $this->task('Live');
        app(TaskService::class)->updateProgress($task, 30, $this->admin);

        // البث بيتأجّل لبعد الرد (App\Support\Realtime) — بننفّذه زي آخر الطلب
        app(\Illuminate\Support\Defer\DeferredCallbackCollection::class)->invoke();

        Event::assertDispatched(TaskBoardChanged::class, fn ($e) => $e->taskId === $task->id && $e->action === 'created');
        Event::assertDispatched(TaskBoardChanged::class, function ($e) use ($task) {
            $channels = array_map(fn ($c) => $c->name, $e->broadcastOn());

            return $e->taskId === $task->id && $e->action === 'updated'
                && $channels === ['private-tasks-board.' . $this->branch->id, 'private-tasks-board.all'];
        });
    }

    public function test_board_requires_permission(): void
    {
        $role = Role::create(['name' => 'Viewer', 'roles' => ['task_show']]);
        $this->actingAs(Admin::create(['name' => 'V', 'email' => 'v@t.com', 'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id]), 'admin');

        $this->get(route('admin.tasks.board'))->assertRedirect(route('admin.error_403'));
        $this->get(route('admin.tasks.calendar'))->assertOk();
        $this->get(route('admin.tasks.index'))->assertOk()->assertDontSee(route('admin.tasks.board'));
    }

    public function test_branch_admin_board_channel(): void
    {
        $role = Role::create(['name' => 'Branch', 'roles' => all_roles()]);
        $this->actingAs(Admin::create(['name' => 'B', 'email' => 'b@t.com', 'password' => bcrypt('x'), 'main' => false, 'role_id' => $role->id, 'employee_id' => $this->staff->id]), 'admin');
        BranchScopeResolver::forget();

        $this->get(route('admin.tasks.board'))->assertOk()->assertSee('tasks-board.' . $this->branch->id, false);

        BranchScopeResolver::forget();
    }

    public function test_calendar_groups_by_due_date(): void
    {
        $this->task('Due on 10th', ['due_at' => '2026-11-10 15:00:00']);
        $this->task('Next month', ['due_at' => '2026-12-15 10:00:00']);
        $this->task('No date');

        $this->get(route('admin.tasks.calendar', ['month' => '2026-11']))
            ->assertOk()->assertSee('Due on 10th')->assertDontSee('Next month')->assertDontSee('No date');

        // شهر غلط ← الشهر الحالي من غير خطأ
        $this->get(route('admin.tasks.calendar', ['month' => '2026-13']))->assertOk();
    }

    public function test_portal_board_move_and_calendar(): void
    {
        $task = $this->task('Mine', ['due_at' => now()->addDay()->toDateTimeString()]);
        $other = $this->task('Not mine', ['assignee_ids' => [$this->colleague->id]]);

        $this->actingAs($this->staff, 'employee');
        $this->get(route('portal.tasks.board'))->assertOk()->assertSee('Mine')->assertDontSee('Not mine');
        $this->get(route('portal.tasks.calendar'))->assertOk()->assertSee('Mine');

        $this->postJson(route('portal.tasks.move', $task->id), ['status' => 'in_progress', 'order' => [$task->id, $other->id]])->assertOk();
        $this->assertSame('in_progress', $task->fresh()->status);

        // المكلّف مايقفلش مهمة محتاجة مراجعة بالسحب
        $this->postJson(route('portal.tasks.move', $task->id), ['status' => 'completed'])
            ->assertStatus(422)->assertJsonPath('message', __('task.errors.needs_review'));

        // مهمة مش ظاهرة له ← 404
        $this->postJson(route('portal.tasks.move', $other->id), ['status' => 'in_progress'])->assertNotFound();
        // الترتيب مالمسش مهمة مش بتاعته
        $this->assertSame(0, $other->fresh()->position);
    }

    public function test_board_channel_authorization(): void
    {
        config(['broadcasting.default' => 'pusher', 'broadcasting.connections.pusher.key' => 'k', 'broadcasting.connections.pusher.secret' => 's', 'broadcasting.connections.pusher.app_id' => '1']);
        app()->forgetInstance('Illuminate\Broadcasting\BroadcastManager');
        \Illuminate\Support\Facades\Broadcast::clearResolvedInstances();
        (new \App\Providers\AppServiceProvider(app()))->boot();

        $auth = fn (string $channel) => $this->post(route('admin.broadcasting.auth'), ['channel_name' => 'private-' . $channel, 'socket_id' => '1.2']);

        // مشرف الكل: all وأي فرع
        $auth('tasks-board.all')->assertOk();
        $auth('tasks-board.' . $this->branch->id)->assertOk();

        // من غير صلاحية اللوحة
        $role = Role::create(['name' => 'NoBoard', 'roles' => ['task_show']]);
        $this->actingAs(Admin::create(['name' => 'N', 'email' => 'n@t.com', 'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id]), 'admin');
        $auth('tasks-board.all')->assertForbidden();

        // مشرف فرع: فرعه بس، مش all ولا فرع تاني
        $other = Branch::create(['name' => 'B2', 'code' => 'B2', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $branchRole = Role::create(['name' => 'Branch', 'roles' => all_roles()]);
        $this->actingAs(Admin::create(['name' => 'B', 'email' => 'b@t.com', 'password' => bcrypt('x'), 'main' => false, 'role_id' => $branchRole->id, 'employee_id' => $this->staff->id]), 'admin');
        BranchScopeResolver::forget();

        $auth('tasks-board.' . $this->branch->id)->assertOk();
        BranchScopeResolver::forget();
        $auth('tasks-board.all')->assertForbidden();
        BranchScopeResolver::forget();
        $auth('tasks-board.' . $other->id)->assertForbidden();
        BranchScopeResolver::forget();
    }
}
