<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Services\Tasks\TaskService;
use App\Support\BranchScopeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * شاشات المهام في لوحة الإدارة: العرض، الإنشاء، التفاصيل، العمليات، والعزل.
 */
class AdminTaskScreensTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Admin $admin;

    private Employee $staff;

    private Employee $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $title = JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        foreach (['staff' => '01000000001', 'other' => '01000000002'] as $prop => $phone) {
            $this->{$prop} = Employee::create([
                'branch_id' => $this->branch->id, 'job_title_id' => $title->id, 'name' => ucfirst($prop) . ' Person',
                'employee_code' => 'E-' . $phone, 'phone' => $phone, 'status' => 'active', 'basic_salary' => 3000, 'hire_date' => '2024-01-01',
            ]);
        }

        $role = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $this->admin = Admin::create(['name' => 'Root', 'email' => 'root@test.com', 'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id]);
        $this->actingAs($this->admin, 'admin');
    }

    private function makeTask(array $data = []): Task
    {
        return app(TaskService::class)->create($data + [
            'title' => 'Prepare quote', 'branch_id' => $this->branch->id, 'assignee_ids' => [$this->staff->id],
            'checklist' => ['call client', 'send pdf'],
        ], $this->admin);
    }

    public function test_index_page_and_datatable(): void
    {
        $task = $this->makeTask();
        $this->makeTask(['title' => 'Late one', 'due_at' => now()->subDay()->toDateTimeString()]);

        $this->get(route('admin.tasks.index'))
            ->assertOk()
            ->assertSee(__('task.stats.overdue'))
            ->assertSee(__('task.actions.add_new'));

        $json = $this->getJson(route('admin.tasks.index', ['employee_id' => $this->staff->id]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->json();

        $this->assertSame(2, $json['recordsTotal']);
        $this->assertStringContainsString('Prepare quote', collect($json['data'])->pluck('title_html')->implode(''));
        $this->assertStringContainsString(route('admin.tasks.show', $task->id), $json['data'][1]['title_html'] . $json['data'][0]['title_html']);

        // فلتر المتأخرة
        $overdue = $this->getJson(route('admin.tasks.index', ['status' => 'overdue']), ['X-Requested-With' => 'XMLHttpRequest'])->json();
        $this->assertSame(1, $overdue['recordsFiltered']);
    }

    public function test_create_and_store(): void
    {
        $this->get(route('admin.tasks.create'))->assertOk()->assertSee('name="title"', false);

        $response = $this->post(route('admin.tasks.store'), [
            'title'                      => 'New report',
            'branch_id'                  => $this->branch->id,
            'priority'                   => 'high',
            'due_at'                     => now()->addWeek()->format('Y-m-d\TH:i'),
            'estimated_hours'            => 2.5,
            'requires_review'            => '1',
            'progress_source'            => 'manual',
            'progress_update_interval'   => 'custom',
            'progress_update_every_days' => 2,
            'assignee_ids'               => [$this->staff->id, $this->other->id],
            'checklist'                  => ['a', '', 'b'],
        ]);

        $task = Task::where('title', 'New report')->firstOrFail();
        $response->assertRedirect(route('admin.tasks.show', $task->id));

        $this->assertSame(150, $task->estimated_minutes);
        $this->assertSame(2, $task->checklist_count);
        $this->assertSame(2, $task->assignees()->count());
        $this->assertSame(2, $task->progress_update_every_days);
    }

    public function test_store_validates_custom_interval(): void
    {
        $this->post(route('admin.tasks.store'), [
            'title' => 'x', 'priority' => 'low', 'progress_source' => 'manual', 'progress_update_interval' => 'custom',
            'branch_id' => $this->branch->id,
        ])->assertSessionHasErrors('progress_update_every_days');
    }

    public function test_show_page_renders_everything(): void
    {
        $task = $this->makeTask(['due_at' => now()->addDays(3)->toDateTimeString(), 'starts_at' => now()->subDay()->toDateTimeString()]);
        app(TaskService::class)->create(['title' => 'Sub one', 'parent_id' => $task->id, 'assignee_ids' => [$this->other->id]], $this->admin);
        app(TaskService::class)->updateProgress($task, 25, $this->admin, 'started');

        $this->get(route('admin.tasks.show', $task->id))
            ->assertOk()
            ->assertSee('Prepare quote')
            ->assertSee('call client')
            ->assertSee('Sub one')
            ->assertSee('Staff Person')
            ->assertSee(__('task.actions.complete'))
            ->assertSee(__('task.labels_text.timeline'))
            ->assertSee('started');
    }

    public function test_status_progress_checklist_and_assignees_actions(): void
    {
        $task = $this->makeTask();

        $this->post(route('admin.tasks.status', $task->id), ['status' => 'in_progress'])->assertSessionHas('success');
        $this->assertSame('in_progress', $task->fresh()->status);

        $this->post(route('admin.tasks.progress', $task->id), ['progress' => 40, 'note' => 'half'])->assertSessionHas('success');
        $this->assertSame(40, $task->fresh()->progress);

        $this->post(route('admin.tasks.checklist.store', $task->id), ['title' => 'third'])->assertSessionHas('success');
        $item = $task->checklistItems()->where('title', 'third')->firstOrFail();
        $this->patch(route('admin.tasks.checklist.toggle', [$task->id, $item->id]))->assertSessionHas('success');
        $this->assertTrue($item->fresh()->is_done);
        $this->delete(route('admin.tasks.checklist.destroy', [$task->id, $item->id]))->assertSessionHas('success');
        $this->assertSame(2, $task->fresh()->checklist_count);

        $this->put(route('admin.tasks.assignees', $task->id), ['assignee_ids' => [$this->other->id]])->assertSessionHas('success');
        $this->assertSame([$this->other->id], $task->assignees()->pluck('employees.id')->all());

        // انتقال غلط بيرجع رسالة خطأ مش 500
        $this->post(route('admin.tasks.status', $task->id), ['status' => 'completed'])->assertSessionHas('success');
        $this->post(route('admin.tasks.status', $task->id), ['status' => 'in_review'])->assertSessionHas('error');
    }

    public function test_checklist_item_of_another_task_is_refused(): void
    {
        $a = $this->makeTask();
        $b = $this->makeTask();
        $item = $b->checklistItems()->first();

        $this->patch(route('admin.tasks.checklist.toggle', [$a->id, $item->id]))->assertSessionHas('error');
        $this->assertFalse($item->fresh()->is_done);
    }

    public function test_edit_update_delete_trash_restore(): void
    {
        $task = $this->makeTask();

        $this->get(route('admin.tasks.edit', $task->id))->assertOk()->assertSee('Prepare quote');

        $this->put(route('admin.tasks.update', $task->id), [
            'title' => 'Renamed', 'priority' => 'urgent', 'progress_source' => 'checklist', 'progress_update_interval' => 'none',
        ])->assertRedirect(route('admin.tasks.show', $task->id));
        $this->assertSame('Renamed', $task->fresh()->title);
        $this->assertSame('urgent', $task->fresh()->priority);

        $this->delete(route('admin.tasks.destroy', $task->id))->assertRedirect(route('admin.tasks.index'));
        $this->assertSoftDeleted($task);

        $this->get(route('admin.tasks.trash'))->assertOk();
        $this->getJson(route('admin.tasks.trash'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonPath('recordsTotal', 1);

        $this->post(route('admin.tasks.restore', $task->id))->assertSessionHas('success');
        $this->assertNotSoftDeleted($task);
    }

    public function test_permissions_are_enforced(): void
    {
        $task = $this->makeTask();

        $role = Role::create(['name' => 'Viewer', 'roles' => ['task_show']]);
        $viewer = Admin::create(['name' => 'V', 'email' => 'v@test.com', 'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id]);
        $this->actingAs($viewer, 'admin');

        $this->get(route('admin.tasks.show', $task->id))->assertOk()->assertDontSee(__('task.actions.complete'));
        $this->get(route('admin.tasks.create'))->assertRedirect(route('admin.error_403'));
        $this->post(route('admin.tasks.status', $task->id), ['status' => 'in_progress'])->assertSessionHas('error');
        $this->assertSame('new', $task->fresh()->status);
    }

    public function test_branch_admin_cannot_open_other_branch_task(): void
    {
        $other = Branch::create(['name' => 'B2', 'code' => 'B2', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $foreign = $this->makeTask(['branch_id' => $other->id, 'assignee_ids' => []]);
        $mine = $this->makeTask();

        $role = Role::create(['name' => 'Branch', 'roles' => all_roles()]);
        $branchAdmin = Admin::create([
            'name' => 'B', 'email' => 'b@test.com', 'password' => bcrypt('x'), 'main' => false,
            'role_id' => $role->id, 'employee_id' => $this->staff->id,
        ]);
        $this->actingAs($branchAdmin, 'admin');
        BranchScopeResolver::forget();

        $this->get(route('admin.tasks.show', $foreign->id))->assertNotFound();
        $this->get(route('admin.tasks.show', $mine->id))->assertOk();

        // مهمة جديدة بتتحط في فرعه حتى لو بعت فرع تاني
        $this->post(route('admin.tasks.store'), [
            'title' => 'Branch task', 'branch_id' => $other->id, 'priority' => 'low',
            'progress_source' => 'manual', 'progress_update_interval' => 'none',
        ]);
        $this->assertSame($this->branch->id, Task::withoutGlobalScopes()->where('title', 'Branch task')->value('branch_id'));

        BranchScopeResolver::forget();
    }

    public function test_project_filter(): void
    {
        $project = Project::create(['branch_id' => $this->branch->id, 'name' => 'Launch']);
        $this->makeTask(['project_id' => $project->id, 'title' => 'In project']);
        $this->makeTask(['title' => 'Loose']);

        $json = $this->getJson(route('admin.tasks.index', ['project_id' => $project->id]), ['X-Requested-With' => 'XMLHttpRequest'])->json();
        $this->assertSame(1, $json['recordsFiltered']);

        $json = $this->getJson(route('admin.tasks.index', ['project_id' => 'none']), ['X-Requested-With' => 'XMLHttpRequest'])->json();
        $this->assertSame(1, $json['recordsFiltered']);
    }
}
