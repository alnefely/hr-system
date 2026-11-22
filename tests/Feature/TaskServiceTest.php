<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Events\Tasks\TaskAssigned;
use App\Events\Tasks\TaskStatusChanged;
use App\Exceptions\InvalidStateException;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Services\Tasks\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * قواعد خدمة المهام: التكليف، الحالات، المراجعة، النسبة، والخطوات.
 */
class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $service;

    private Branch $branch;

    private Branch $otherBranch;

    private Employee $manager;

    private Employee $staff;

    private Employee $colleague;

    private Employee $stranger;

    private ?JobTitle $title = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TaskService::class);
        $this->branch = $this->branch('HQ');
        $this->otherBranch = $this->branch('BR2');
        $this->manager = $this->employee('Manager', '01000000001');
        $this->staff = $this->employee('Staff', '01000000002', $this->manager->id);
        $this->colleague = $this->employee('Colleague', '01000000003');
        $this->stranger = $this->employee('Stranger', '01000000004', null, $this->otherBranch);
    }

    private function branch(string $code): Branch
    {
        return Branch::create(['name' => $code, 'code' => $code, 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
    }

    private function employee(string $name, string $phone, ?int $managerId = null, ?Branch $branch = null): Employee
    {
        $this->title ??= JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        return Employee::create([
            'branch_id' => ($branch ?? $this->branch)->id, 'job_title_id' => $this->title->id,
            'name' => $name, 'employee_code' => 'E-' . $phone, 'phone' => $phone, 'manager_id' => $managerId,
            'status' => 'active', 'basic_salary' => 3000, 'hire_date' => '2024-01-01',
        ]);
    }

    private function admin(array $permissions): Admin
    {
        $role = Role::create(['name' => 'R' . uniqid(), 'roles' => $permissions]);
        $admin = Admin::create(['name' => 'A', 'email' => uniqid() . '@t.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'main' => true]);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    private function create(Employee|Admin $actor, array $data = []): Task
    {
        return $this->service->create($data + ['title' => 'T', 'branch_id' => $this->branch->id], $actor);
    }

    public function test_employee_creates_task_for_colleague_with_checklist(): void
    {
        Event::fake([TaskAssigned::class]);

        $task = $this->create($this->colleague, [
            'assignee_ids' => [$this->staff->id],
            'checklist'    => ['one', '', 'two'],
        ]);

        $this->assertSame([$this->staff->id], $task->assignees()->pluck('employees.id')->all());
        $this->assertSame(2, $task->fresh()->checklist_count);
        $this->assertTrue($task->creator->is($this->colleague));
        $this->assertSame(['created', 'assigned'], $task->activities()->reorder('id')->pluck('event')->all());
        Event::assertDispatched(TaskAssigned::class, fn ($e) => $e->employeeIds === [$this->staff->id]);
    }

    public function test_cannot_assign_employee_from_another_branch(): void
    {
        $this->expectException(InvalidStateException::class);

        $this->create($this->colleague, ['assignee_ids' => [$this->stranger->id]]);
    }

    public function test_project_members_from_other_branches_can_be_assigned(): void
    {
        $project = Project::create(['branch_id' => $this->branch->id, 'name' => 'P', 'manager_id' => $this->colleague->id]);
        $project->members()->attach($this->stranger->id, ['role' => 'member']);

        $task = $this->create($this->colleague, ['project_id' => $project->id, 'assignee_ids' => [$this->stranger->id]]);

        $this->assertSame([$this->stranger->id], $task->assignees()->pluck('employees.id')->all());
        $this->assertSame(1, $project->fresh()->tasks_count);
    }

    public function test_employee_cannot_add_tasks_to_a_project_they_are_not_in(): void
    {
        $project = Project::create(['branch_id' => $this->branch->id, 'name' => 'P']);

        $this->expectException(InvalidStateException::class);
        $this->create($this->staff, ['project_id' => $project->id]);
    }

    public function test_assignee_must_send_to_review_instead_of_completing(): void
    {
        $task = $this->create($this->colleague, ['assignee_ids' => [$this->staff->id]]);

        $this->service->changeStatus($task, TaskStatus::InProgress, $this->staff);
        $this->assertNotNull($task->fresh()->started_at);

        try {
            $this->service->changeStatus($task->fresh(), TaskStatus::Completed, $this->staff);
            $this->fail('assignee completed a task that needs review');
        } catch (InvalidStateException $e) {
            $this->assertSame(__('task.errors.needs_review'), $e->getMessage());
        }

        $this->service->changeStatus($task->fresh(), TaskStatus::InReview, $this->staff);

        // اللي أنشأ المهمة يعتمدها
        $this->service->changeStatus($task->fresh(), TaskStatus::Completed, $this->colleague);
        $task->refresh();
        $this->assertSame('completed', $task->status);
        $this->assertSame(100, $task->progress);
        $this->assertNotNull($task->completed_at);
    }

    public function test_direct_manager_can_approve_team_task(): void
    {
        $task = $this->create($this->colleague, ['assignee_ids' => [$this->staff->id]]);
        $this->service->changeStatus($task, TaskStatus::InReview, $this->staff);

        $this->service->changeStatus($task->fresh(), TaskStatus::Completed, $this->manager);

        $this->assertSame('completed', $task->fresh()->status);
    }

    public function test_self_task_without_manager_completes_without_review(): void
    {
        $task = $this->create($this->colleague, ['assignee_ids' => [$this->colleague->id]]);

        $this->service->changeStatus($task, TaskStatus::Completed, $this->colleague);

        $this->assertSame('completed', $task->fresh()->status);
    }

    public function test_task_without_review_can_be_completed_by_assignee(): void
    {
        $task = $this->create($this->colleague, ['assignee_ids' => [$this->staff->id], 'requires_review' => false]);

        $this->service->changeStatus($task, TaskStatus::Completed, $this->staff);

        $this->assertSame('completed', $task->fresh()->status);
    }

    public function test_assignee_cannot_cancel_or_edit(): void
    {
        $task = $this->create($this->colleague, ['assignee_ids' => [$this->staff->id]]);

        foreach ([
            fn () => $this->service->changeStatus($task->fresh(), TaskStatus::Cancelled, $this->staff),
            fn () => $this->service->update($task->fresh(), ['title' => 'X'], $this->staff),
            fn () => $this->service->syncAssignees($task->fresh(), [$this->colleague->id], $this->staff),
            fn () => $this->service->delete($task->fresh(), $this->staff),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('assignee did an owner-only action');
            } catch (InvalidStateException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_stranger_cannot_change_status(): void
    {
        $task = $this->create($this->colleague, ['assignee_ids' => [$this->staff->id]]);

        $this->expectException(InvalidStateException::class);
        $this->service->changeStatus($task, TaskStatus::InProgress, $this->stranger);
    }

    public function test_blocked_task_cannot_start(): void
    {
        $first = $this->create($this->colleague);
        $second = $this->create($this->colleague);
        $second->dependencies()->attach($first->id);

        $this->expectException(InvalidStateException::class);
        $this->service->changeStatus($second, TaskStatus::InProgress, $this->colleague);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $task = $this->create($this->colleague);
        $this->service->changeStatus($task, TaskStatus::Cancelled, $this->colleague);

        $this->expectException(InvalidStateException::class);
        $this->service->changeStatus($task->fresh(), TaskStatus::InReview, $this->colleague);
    }

    public function test_progress_update_records_history_and_starts_task(): void
    {
        Event::fake([TaskStatusChanged::class]);

        $task = $this->create($this->colleague, [
            'assignee_ids'             => [$this->staff->id],
            'starts_at'                => now()->subDays(5)->toDateTimeString(),
            'due_at'                   => now()->addDays(5)->toDateTimeString(),
            'progress_update_interval' => 'custom',
            'progress_update_every_days' => 3,
        ]);

        $this->assertSame(now()->addDays(3)->toDateString(), $task->fresh()->next_progress_update_at->toDateString());

        $update = $this->service->updateProgress($task->fresh(), 30, $this->staff, 'design done');
        $task->refresh();

        $this->assertSame(30, $task->progress);
        $this->assertSame('in_progress', $task->status);
        $this->assertSame(0, $update->previous_progress);
        $this->assertSame(50, $update->expected_progress);
        $this->assertSame('behind', $task->progressHealth());
        $this->assertNotNull($task->last_progress_update_at);
        $this->assertSame(now()->addDays(3)->toDateString(), $task->next_progress_update_at->toDateString());
    }

    public function test_manual_progress_is_refused_for_automatic_tasks(): void
    {
        $task = $this->create($this->colleague, ['progress_source' => 'checklist', 'checklist' => ['a', 'b']]);

        $this->expectException(InvalidStateException::class);
        $this->service->updateProgress($task, 40, $this->colleague);
    }

    public function test_checklist_toggle_drives_automatic_progress(): void
    {
        $task = $this->create($this->colleague, ['progress_source' => 'checklist', 'checklist' => ['a', 'b', 'c', 'd'], 'assignee_ids' => [$this->staff->id]]);

        $items = $task->checklistItems()->get();
        $this->service->toggleChecklistItem($task, $items[0], $this->staff);
        $this->service->toggleChecklistItem($task, $items[1], $this->staff);

        $this->assertSame(50, $task->fresh()->progress);

        $this->service->deleteChecklistItem($task, $items[3], $this->staff);
        $this->assertSame(67, $task->fresh()->progress);
    }

    public function test_subtask_updates_parent_counters(): void
    {
        $parent = $this->create($this->colleague, ['progress_source' => 'subtasks', 'assignee_ids' => [$this->staff->id]]);
        $child = $this->service->create(['title' => 'child', 'parent_id' => $parent->id], $this->staff);

        $this->assertSame($parent->branch_id, $child->branch_id);
        $this->assertSame(1, $parent->fresh()->subtasks_count);

        $this->service->changeStatus($child, TaskStatus::Completed, $this->staff);
        $parent->refresh();

        $this->assertSame(1, $parent->completed_subtasks_count);
        $this->assertSame(100, $parent->progress);
    }

    public function test_decline_and_reassign(): void
    {
        $task = $this->create($this->colleague, ['assignee_ids' => [$this->staff->id]]);

        $this->service->decline($task, $this->staff, 'busy');

        $this->assertSame([], $task->assignees()->pluck('employees.id')->all());
        $this->assertSame('busy', $task->assignments()->first()->decline_reason);

        // إعادة التكليف بترجّع التكليف فعّال
        $this->service->syncAssignees($task->fresh(), [$this->staff->id], $this->colleague);
        $this->assertSame([$this->staff->id], $task->assignees()->pluck('employees.id')->all());
    }

    public function test_creator_cannot_decline_own_task(): void
    {
        $task = $this->create($this->colleague, ['assignee_ids' => [$this->colleague->id]]);

        $this->expectException(InvalidStateException::class);
        $this->service->decline($task, $this->colleague);
    }

    public function test_sync_assignees_adds_and_removes(): void
    {
        $task = $this->create($this->colleague, ['assignee_ids' => [$this->staff->id]]);

        $this->service->syncAssignees($task, [$this->manager->id], $this->colleague);

        $this->assertSame([$this->manager->id], $task->assignees()->pluck('employees.id')->all());
        $this->assertSame(1, $task->assignments()->count());
    }

    public function test_admin_permissions_drive_admin_actions(): void
    {
        $admin = $this->admin(['task_show', 'task_create']);
        $task = $this->create($admin, ['assignee_ids' => [$this->staff->id]]);

        $this->expectException(InvalidStateException::class);
        $this->service->update($task, ['title' => 'X'], $admin);
    }

    public function test_admin_with_update_can_complete_directly(): void
    {
        $admin = $this->admin(['task_show', 'task_create', 'task_update']);
        $task = $this->create($admin, ['assignee_ids' => [$this->staff->id]]);

        $this->service->changeStatus($task, TaskStatus::Completed, $admin);

        $this->assertSame('completed', $task->fresh()->status);
    }

    public function test_delete_and_restore_refresh_project(): void
    {
        $project = Project::create(['branch_id' => $this->branch->id, 'name' => 'P', 'manager_id' => $this->colleague->id]);
        $task = $this->create($this->colleague, ['project_id' => $project->id]);
        $this->assertSame(1, $project->fresh()->tasks_count);

        $this->service->delete($task, $this->colleague);
        $this->assertSame(0, $project->fresh()->tasks_count);

        $this->service->restore($task->id, $this->colleague);
        $this->assertSame(1, $project->fresh()->tasks_count);
    }
}
