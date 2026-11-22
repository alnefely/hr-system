<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskAssignee;
use App\Models\TaskChecklistItem;
use App\Models\TaskRecurrence;
use App\Models\TaskWatcher;
use App\Support\BranchScopeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * أساس موديول المهام: الجداول، الأكواد، صلاحيات الرؤية، نسبة الإنجاز،
 * العدادات المخزّنة، المراجع، والتكرار.
 */
class TaskModuleFoundationTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Employee $manager;

    private Employee $staff;

    private Employee $other;

    private ?JobTitle $title = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = $this->branch('HQ');
        $this->manager = $this->employee('Manager', '01000000001');
        $this->staff = $this->employee('Staff', '01000000002', $this->manager->id);
        $this->other = $this->employee('Other', '01000000003');
    }

    private function branch(string $code): Branch
    {
        return Branch::create([
            'name' => $code, 'code' => $code, 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true,
        ]);
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

    private function task(array $attributes = []): Task
    {
        return Task::create($attributes + ['branch_id' => $this->branch->id, 'title' => 'Task']);
    }

    private function assign(Task $task, Employee $employee): void
    {
        TaskAssignee::create(['task_id' => $task->id, 'employee_id' => $employee->id]);
    }

    public function test_all_module_tables_exist(): void
    {
        foreach ([
            'projects', 'project_members', 'project_templates', 'tasks', 'task_assignees', 'task_watchers',
            'task_checklist_items', 'task_dependencies', 'task_labels', 'task_label_task', 'task_comments',
            'task_attachments', 'task_activities', 'task_progress_updates', 'task_reviews', 'task_time_entries',
            'task_reminders', 'task_recurrences', 'employee_notifications',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table {$table}");
        }
    }

    public function test_codes_are_sequential_and_defaults_applied(): void
    {
        $a = $this->task();
        $b = $this->task();
        $project = Project::create(['branch_id' => $this->branch->id, 'name' => 'P']);

        $this->assertSame('TSK-00001', $a->code);
        $this->assertSame('TSK-00002', $b->code);
        $this->assertSame('PRJ-0001', $project->code);
        $this->assertSame(TaskStatus::New->value, $a->status);
        $this->assertSame('medium', $a->priority);
    }

    public function test_subtask_inherits_branch_and_project(): void
    {
        $project = Project::create(['branch_id' => $this->branch->id, 'name' => 'P']);
        $parent = $this->task(['project_id' => $project->id]);

        $child = Task::create(['parent_id' => $parent->id, 'title' => 'Child']);

        $this->assertSame($this->branch->id, $child->branch_id);
        $this->assertSame($project->id, $child->project_id);
    }

    public function test_declined_assignee_is_not_an_active_assignee(): void
    {
        $task = $this->task();
        $this->assign($task, $this->staff);
        TaskAssignee::create(['task_id' => $task->id, 'employee_id' => $this->other->id, 'status' => 'declined']);

        $this->assertSame([$this->staff->id], $task->assignees()->pluck('employees.id')->all());
        $this->assertSame(2, $task->assignments()->count());
        $this->assertSame(1, Task::assignedTo($this->staff->id)->count());
        $this->assertSame(0, Task::assignedTo($this->other->id)->count());
    }

    public function test_employee_visibility_rules(): void
    {
        $assigned = $this->task(['title' => 'assigned to staff']);
        $this->assign($assigned, $this->staff);

        $created = $this->task(['title' => 'created by other', 'creator_type' => Employee::class, 'creator_id' => $this->other->id]);

        $watched = $this->task(['title' => 'watched by other']);
        TaskWatcher::create(['task_id' => $watched->id, 'watcher_type' => Employee::class, 'watcher_id' => $this->other->id]);

        $project = Project::create(['branch_id' => $this->branch->id, 'name' => 'P']);
        $project->members()->attach($this->other->id, ['role' => 'member']);
        $inProject = $this->task(['title' => 'in project', 'project_id' => $project->id]);

        $hidden = $this->task(['title' => 'nobody']);

        $visible = fn (Employee $e) => Task::visibleToEmployee($e)->pluck('id')->sort()->values()->all();

        // الموظف يشوف المكلّف بيه بس
        $this->assertSame([$assigned->id], $visible($this->staff));
        // المدير المباشر يشوف مهام فريقه
        $this->assertSame([$assigned->id], $visible($this->manager));
        // اللي أنشأ + المتابَعة + المشروع
        $this->assertSame(collect([$created->id, $watched->id, $inProject->id])->sort()->values()->all(), $visible($this->other));
        $this->assertNotContains($hidden->id, $visible($this->other));
    }

    public function test_expected_progress_and_health(): void
    {
        Carbon::setTestNow('2026-11-10 00:00:00');

        $task = $this->task([
            'starts_at' => '2026-11-01 00:00:00',
            'due_at'    => '2026-11-21 00:00:00',
            'progress'  => 20,
        ]);

        // ٩ أيام من ٢٠ = ٤٥٪
        $this->assertSame(45, $task->expectedProgress());
        $this->assertSame('behind', $task->progressHealth());

        $task->progress = 50;
        $this->assertSame('on_track', $task->progressHealth());

        $task->progress = 70;
        $this->assertSame('ahead', $task->progressHealth());

        $this->assertNull($this->task()->expectedProgress());

        Carbon::setTestNow();
    }

    public function test_next_progress_update_date(): void
    {
        $from = Carbon::parse('2026-11-01 09:00');

        $this->assertNull($this->task()->nextProgressUpdateDate($from));
        $this->assertEquals('2026-11-02', $this->task(['progress_update_interval' => 'daily'])->nextProgressUpdateDate($from)->toDateString());
        $this->assertEquals('2026-11-08', $this->task(['progress_update_interval' => 'weekly'])->nextProgressUpdateDate($from)->toDateString());
        $this->assertEquals('2026-11-04', $this->task(['progress_update_interval' => 'custom', 'progress_update_every_days' => 3])->nextProgressUpdateDate($from)->toDateString());
    }

    public function test_counters_and_automatic_progress(): void
    {
        $task = $this->task(['progress_source' => 'checklist']);

        foreach ([true, true, false, false] as $i => $done) {
            TaskChecklistItem::create(['task_id' => $task->id, 'title' => "step {$i}", 'is_done' => $done]);
        }

        $task->refreshCounters();
        $task->refresh();

        $this->assertSame(4, $task->checklist_count);
        $this->assertSame(2, $task->completed_checklist_count);
        $this->assertSame(50, $task->progress);

        $parent = $this->task(['progress_source' => 'subtasks']);
        Task::create(['parent_id' => $parent->id, 'title' => 'a', 'status' => 'completed']);
        Task::create(['parent_id' => $parent->id, 'title' => 'b', 'progress' => 50]);
        Task::create(['parent_id' => $parent->id, 'title' => 'c', 'status' => 'cancelled']);

        $parent->refreshCounters();
        $parent->refresh();

        $this->assertSame(2, $parent->subtasks_count);
        $this->assertSame(1, $parent->completed_subtasks_count);
        $this->assertSame(75, $parent->progress);
    }

    public function test_project_stats(): void
    {
        $project = Project::create(['branch_id' => $this->branch->id, 'name' => 'P']);
        $this->task(['project_id' => $project->id, 'status' => 'completed']);
        $this->task(['project_id' => $project->id, 'progress' => 40]);
        $this->task(['project_id' => $project->id, 'status' => 'cancelled']);

        $project->refreshStats();
        $project->refresh();

        $this->assertSame(2, $project->tasks_count);
        $this->assertSame(1, $project->completed_tasks_count);
        $this->assertSame(70, $project->progress);
    }

    public function test_reviewer_resolution(): void
    {
        // موظف كلّف زميله ← هو المراجع
        $task = $this->task(['creator_type' => Employee::class, 'creator_id' => $this->other->id]);
        $this->assign($task, $this->staff);
        $this->assertTrue($task->resolveReviewer()->is($this->other));

        // موظف عمل مهمة لنفسه ← مديره المباشر
        $self = $this->task(['creator_type' => Employee::class, 'creator_id' => $this->staff->id]);
        $this->assign($self, $this->staff);
        $this->assertTrue($self->resolveReviewer()->is($this->manager));

        // مفيش مدير ← من غير مراجع
        $noManager = $this->task(['creator_type' => Employee::class, 'creator_id' => $this->other->id]);
        $this->assign($noManager, $this->other);
        $this->assertNull($noManager->resolveReviewer());

        // مشرف أنشأها ← المشرف
        $role = Role::create(['name' => 'R', 'roles' => []]);
        $admin = Admin::create(['name' => 'A', 'email' => 'a@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id]);
        $byAdmin = $this->task(['creator_type' => Admin::class, 'creator_id' => $admin->id]);
        $this->assertTrue($byAdmin->resolveReviewer()->is($admin));
    }

    public function test_status_transitions(): void
    {
        $this->assertTrue(TaskStatus::New->canTransitionTo(TaskStatus::InProgress));
        $this->assertTrue(TaskStatus::InReview->canTransitionTo(TaskStatus::InProgress));
        $this->assertTrue(TaskStatus::Completed->canTransitionTo(TaskStatus::InProgress));
        $this->assertFalse(TaskStatus::Completed->canTransitionTo(TaskStatus::Cancelled));
        $this->assertFalse(TaskStatus::Completed->isOpen());
        $this->assertSame(['new', 'in_progress', 'in_review'], TaskStatus::openValues());
    }

    public function test_open_and_overdue_scopes(): void
    {
        $this->task(['due_at' => now()->subDay()]);
        $this->task(['due_at' => now()->addDay()]);
        $this->task(['due_at' => now()->subDay(), 'status' => 'completed']);

        $this->assertSame(2, Task::open()->count());
        $this->assertSame(1, Task::overdue()->count());
    }

    public function test_dependencies_block_task(): void
    {
        $first = $this->task();
        $second = $this->task();
        $second->dependencies()->attach($first->id);

        $this->assertTrue($second->isBlocked());

        $first->update(['status' => 'completed']);
        $this->assertFalse($second->fresh()->isBlocked());
    }

    public function test_recurrence_next_occurrence(): void
    {
        $base = ['branch_id' => $this->branch->id, 'blueprint' => ['title' => 'x'], 'starts_on' => '2026-01-01', 'run_time' => '08:00:00'];

        $daily = TaskRecurrence::create($base + ['frequency' => 'daily', 'interval' => 2]);
        $this->assertSame('2026-11-03 08:00', $daily->nextOccurrenceAfter(Carbon::parse('2026-11-01 08:00'))->format('Y-m-d H:i'));

        // الأحد والأربع؛ من الاتنين ٢ نوفمبر ٢٠٢٦ ← الأربع ٤ نوفمبر، ومن الأربع ← الأحد ٨
        $weekly = TaskRecurrence::create($base + ['frequency' => 'weekly', 'weekdays' => [0, 3]]);
        $this->assertSame('2026-11-04', $weekly->nextOccurrenceAfter(Carbon::parse('2026-11-02 08:00'))->toDateString());
        $this->assertSame('2026-11-08', $weekly->nextOccurrenceAfter(Carbon::parse('2026-11-04 08:00'))->toDateString());

        // ٣١ في الشهر ← آخر يوم في الشهور الأقصر
        $monthly = TaskRecurrence::create($base + ['frequency' => 'monthly', 'month_day' => 31]);
        $this->assertSame('2026-02-28', $monthly->nextOccurrenceAfter(Carbon::parse('2026-01-31 08:00'))->toDateString());

        $ended = TaskRecurrence::create($base + ['frequency' => 'daily', 'ends_on' => '2026-11-01']);
        $this->assertNull($ended->nextOccurrenceAfter(Carbon::parse('2026-11-01 08:00')));
    }

    public function test_tasks_are_isolated_by_branch_for_branch_admins(): void
    {
        $otherBranch = $this->branch('BR2');
        $mine = $this->task();
        $theirs = $this->task(['branch_id' => $otherBranch->id]);

        $role = Role::create(['name' => 'Branch', 'roles' => ['task_show']]);
        $admin = Admin::create([
            'name' => 'B', 'email' => 'b@test.com', 'password' => bcrypt('x'),
            'role_id' => $role->id, 'main' => false, 'employee_id' => $this->manager->id,
        ]);
        $this->actingAs($admin, 'admin');
        BranchScopeResolver::forget();

        $this->assertSame([$mine->id], Task::pluck('id')->all());
        $this->assertNull(Task::find($theirs->id));

        BranchScopeResolver::forget();
    }
}
