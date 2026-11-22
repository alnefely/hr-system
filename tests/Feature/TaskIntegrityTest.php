<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Exceptions\InvalidStateException;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeNotification;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskTimeEntry;
use App\Services\Projects\ProjectService;
use App\Services\Tasks\TaskBoardService;
use App\Services\Tasks\TaskCollaborationService;
use App\Services\Tasks\TaskReminderService;
use App\Services\Tasks\TaskService;
use App\Services\Tasks\TaskTimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * سلامة البيانات في موديول المهام — حالات طرفية اتصلّحت بعد المراجعة:
 * المهمة المطلوبة الملغية، الحذف المتسلسل والاسترجاع، العدّادات على
 * المحذوف، إكمال الأم والفرعية مفتوحة، ملفات الحذف النهائي، وأعمدة اللوحة.
 */
class TaskIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $tasks;

    private Branch $branch;

    private Admin $admin;

    private Employee $staff;

    private Employee $colleague;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tasks = app(TaskService::class);
        $this->branch = Branch::create(['name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $title = JobTitle::create(['name' => 'Staff', 'is_active' => true]);
        $make = fn (string $name, string $phone) => Employee::create([
            'branch_id' => $this->branch->id, 'job_title_id' => $title->id, 'name' => $name, 'employee_code' => 'E-' . $phone,
            'phone' => $phone, 'status' => 'active', 'basic_salary' => 3000, 'hire_date' => '2024-01-01',
        ]);
        $this->staff = $make('Staff', '01000000001');
        $this->colleague = $make('Colleague', '01000000002');

        $role = Role::create(['name' => 'Full', 'roles' => all_roles()]);
        $this->admin = Admin::create(['name' => 'Admin', 'email' => 'a@t.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'main' => true]);
        $this->actingAs($this->admin, 'admin');
    }

    private function task(array $data = []): Task
    {
        return $this->tasks->create($data + ['title' => 'T', 'branch_id' => $this->branch->id, 'assignee_ids' => [$this->staff->id]], $this->admin);
    }

    // ========================================================
    //  1) المهمة المطلوبة اتلغت
    // ========================================================

    public function test_cancelled_prerequisite_unblocks_dependent_and_notifies(): void
    {
        $pre = $this->task(['title' => 'Prerequisite']);
        $dep = $this->task(['title' => 'Dependent', 'assignee_ids' => [$this->colleague->id]]);
        $this->tasks->addDependency($dep, $pre->id, $this->admin);
        $this->assertTrue($dep->fresh()->isBlocked());

        $this->tasks->changeStatus($pre, TaskStatus::Cancelled, $this->admin);

        $this->assertFalse($dep->fresh()->isBlocked());
        $this->tasks->changeStatus($dep->fresh(), TaskStatus::InProgress, $this->colleague);
        $this->assertSame('in_progress', $dep->fresh()->status);

        // المكلّف بالمهمة المستنية وصله "تقدر تبدأ"
        $this->assertTrue(EmployeeNotification::where('employee_id', $this->colleague->id)->where('type', 'task_unblocked')->exists());
    }

    // ========================================================
    //  2) حذف مهمة + فرعياتها + العدّادات
    // ========================================================

    public function test_delete_cascades_to_subtasks_and_stops_timers(): void
    {
        $parent = $this->task(['title' => 'Parent']);
        $child = $this->task(['title' => 'Child', 'parent_id' => $parent->id]);
        $grandchild = $this->task(['title' => 'Grandchild', 'parent_id' => $child->id]);
        app(TaskTimeService::class)->start($grandchild, $this->staff);

        $this->tasks->delete($parent, $this->admin);

        foreach ([$parent, $child, $grandchild] as $task) {
            $this->assertSoftDeleted('tasks', ['id' => $task->id]);
        }
        $this->assertSame(0, TaskTimeEntry::whereNull('ended_at')->count(), 'running timer on a deleted task');
        // التذكيرات مابتشوفش المحذوف
        $this->assertSame(0, Task::query()->whereKey([$child->id, $grandchild->id])->count());
    }

    public function test_restore_brings_back_only_subtasks_deleted_together(): void
    {
        $parent = $this->task(['title' => 'Parent']);
        $earlier = $this->task(['title' => 'Deleted earlier', 'parent_id' => $parent->id]);
        $child = $this->task(['title' => 'Child', 'parent_id' => $parent->id]);

        $this->tasks->delete($earlier, $this->admin);
        $this->travel(5)->seconds();
        $this->tasks->delete($parent, $this->admin);

        $this->tasks->restore($parent->id, $this->admin);

        $this->assertNotSoftDeleted('tasks', ['id' => $parent->id]);
        $this->assertNotSoftDeleted('tasks', ['id' => $child->id]);
        $this->assertSoftDeleted('tasks', ['id' => $earlier->id]);
    }

    // ========================================================
    //  3) حذف المشروع
    // ========================================================

    public function test_deleting_project_removes_its_tasks_from_reminders_and_restore_brings_them_back(): void
    {
        $projects = app(ProjectService::class);
        $project = $projects->create(['name' => 'Launch', 'branch_id' => $this->branch->id], $this->admin);
        $task = $this->task(['title' => 'Overdue in project', 'project_id' => $project->id, 'due_at' => now()->subDay()->toDateTimeString()]);
        app(TaskTimeService::class)->start($task, $this->staff);

        $projects->delete($project, $this->admin);

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
        $this->assertSame(0, TaskTimeEntry::whereNull('ended_at')->count());
        $this->assertSame(0, app(TaskReminderService::class)->sendOverdue(), 'overdue alert for a deleted project task');

        $projects->restore($project->id);

        $this->assertNotSoftDeleted('tasks', ['id' => $task->id]);
        $this->assertNotSoftDeleted('projects', ['id' => $project->id]);
    }

    // ========================================================
    //  4) الأم والفرعية مفتوحة
    // ========================================================

    public function test_parent_cannot_complete_while_a_subtask_is_open(): void
    {
        $parent = $this->task(['title' => 'Parent']);
        $child = $this->task(['title' => 'Child', 'parent_id' => $parent->id]);

        try {
            $this->tasks->changeStatus($parent, TaskStatus::Completed, $this->admin);
            $this->fail('parent completed with an open subtask');
        } catch (InvalidStateException $e) {
            $this->assertSame(__('task.errors.open_subtasks'), $e->getMessage());
        }

        // الفرعية اتلغت ← الأم تكتمل عادي
        $this->tasks->changeStatus($child, TaskStatus::Cancelled, $this->admin);
        $this->tasks->changeStatus($parent->fresh(), TaskStatus::Completed, $this->admin);
        $this->assertSame('completed', $parent->fresh()->status);
    }

    // ========================================================
    //  7) ملفات الحذف النهائي
    // ========================================================

    public function test_force_delete_removes_attachment_files_including_subtasks(): void
    {
        Storage::fake(config('tasks.attachments.disk', 'local'));
        $collab = app(TaskCollaborationService::class);

        $parent = $this->task(['title' => 'Parent']);
        $child = $this->task(['title' => 'Child', 'parent_id' => $parent->id]);
        [$a] = $collab->addAttachments($parent, [UploadedFile::fake()->create('a.pdf', 5, 'application/pdf')], $this->admin);
        [$b] = $collab->addAttachments($child, [UploadedFile::fake()->create('b.pdf', 5, 'application/pdf')], $this->admin);
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($a->disk);
        $disk->assertExists([$a->path, $b->path]);

        $this->tasks->delete($parent, $this->admin);
        Task::onlyTrashed()->findOrFail($parent->id)->forceDelete();

        $disk->assertMissing([$a->path, $b->path]);
    }

    public function test_project_force_delete_removes_attachment_files(): void
    {
        Storage::fake(config('tasks.attachments.disk', 'local'));
        $project = Project::create(['branch_id' => $this->branch->id, 'name' => 'Launch']);
        $task = $this->task(['project_id' => $project->id]);
        [$file] = app(TaskCollaborationService::class)->addAttachments($task, [UploadedFile::fake()->image('p.png')], $this->admin);

        $project->forceDelete();

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($file->disk);
        $disk->assertMissing($file->path);
    }

    // ========================================================
    //  6) أعمدة اللوحة
    // ========================================================

    public function test_board_columns_have_own_limit_and_real_totals(): void
    {
        $limit = TaskBoardService::COLUMN_LIMIT;

        // عمود "جديدة" أكتر من الحد بكتير، ومهمتين قيد التنفيذ
        for ($i = 0; $i < $limit * 3 + 5; $i++) {
            $this->task(['title' => "New {$i}", 'assignee_ids' => []]);
        }
        foreach ([1, 2] as $i) {
            $this->tasks->changeStatus($this->task(['title' => "Doing {$i}"]), TaskStatus::InProgress, $this->admin);
        }

        $board = app(TaskBoardService::class)->board(Task::query()->select('tasks.*'));

        $this->assertSame($limit * 3 + 5, $board['new']['total']);
        $this->assertCount($limit, $board['new']['tasks']);
        // العمود التاني ماتاكلش
        $this->assertSame(2, $board['in_progress']['total']);
        $this->assertCount(2, $board['in_progress']['tasks']);
    }

    // ========================================================
    //  ملاحظات صغيرة
    // ========================================================

    public function test_first_checked_step_starts_the_task(): void
    {
        $task = $this->task(['checklist' => ['one', 'two'], 'progress_source' => 'checklist']);

        $this->tasks->toggleChecklistItem($task, $task->checklistItems()->first(), $this->staff);

        $this->assertSame('in_progress', $task->fresh()->status);
        $this->assertSame(50, $task->fresh()->progress);
    }

    public function test_timer_cannot_start_on_blocked_task(): void
    {
        $pre = $this->task(['title' => 'Prerequisite']);
        $dep = $this->task(['title' => 'Dependent']);
        $this->tasks->addDependency($dep, $pre->id, $this->admin);

        $this->expectExceptionMessage(__('task.errors.blocked'));
        app(TaskTimeService::class)->start($dep->fresh(), $this->staff);
    }
}
