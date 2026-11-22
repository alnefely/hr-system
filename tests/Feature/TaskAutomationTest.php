<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeNotification;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskRecurrence;
use App\Models\TaskTimeEntry;
use App\Services\Tasks\TaskRecurrenceService;
use App\Services\Tasks\TaskReminderService;
use App\Services\Tasks\TaskService;
use App\Services\Tasks\TaskTimeService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * الأتمتة: المهام المتكررة، التذكيرات، العدادات المنسية، والأمر المجدول.
 */
class TaskAutomationTest extends TestCase
{
    use RefreshDatabase;

    private TaskRecurrenceService $recurrences;

    private TaskReminderService $reminders;

    private Branch $branch;

    private Admin $admin;

    private Employee $staff;

    private Employee $colleague;

    protected function setUp(): void
    {
        parent::setUp();
        // الاتنين 16 نوفمبر 2026، 7 الصبح
        Carbon::setTestNow('2026-11-16 07:00:00');

        $this->recurrences = app(TaskRecurrenceService::class);
        $this->reminders = app(TaskReminderService::class);
        $this->branch = Branch::create(['name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $title = JobTitle::create(['name' => 'Staff', 'is_active' => true]);
        foreach (['staff' => '01000000001', 'colleague' => '01000000002'] as $prop => $phone) {
            $this->{$prop} = Employee::create([
                'branch_id' => $this->branch->id, 'job_title_id' => $title->id, 'name' => ucfirst($prop), 'employee_code' => 'E-' . $phone,
                'phone' => $phone, 'status' => 'active', 'basic_salary' => 3000, 'hire_date' => '2024-01-01',
            ]);
        }

        $role = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $this->admin = Admin::create(['name' => 'Root', 'email' => 'root@test.com', 'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id]);
        $this->actingAs($this->admin, 'admin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function rule(array $data = []): TaskRecurrence
    {
        return $this->recurrences->save($data + [
            'title' => 'Weekly report', 'priority' => 'high', 'branch_id' => $this->branch->id,
            'assignee_ids' => [$this->staff->id], 'checklist' => ['collect', '', 'send'], 'requires_review' => false,
            'frequency' => 'weekly', 'interval' => 1, 'weekdays' => [0, 3], 'run_time' => '08:00',
            'duration_days' => 2, 'starts_on' => '2026-11-01',
        ], $this->admin);
    }

    // ========================================================
    //  حساب المواعيد
    // ========================================================

    public function test_first_occurrence_calculation(): void
    {
        // أحد وأربع، النهاردة اتنين ← الأربع 18
        $this->assertSame('2026-11-18 08:00', $this->rule()->next_run_at->format('Y-m-d H:i'));

        // يومي — 7 الصبح والميعاد 8 ← النهاردة
        $this->assertSame('2026-11-16 08:00', $this->rule(['frequency' => 'daily', 'weekdays' => []])->next_run_at->format('Y-m-d H:i'));

        // شهري يوم 31 ← آخر نوفمبر (30)
        $this->assertSame('2026-11-30', $this->rule(['frequency' => 'monthly', 'month_day' => 31])->next_run_at->toDateString());

        // شهري يوم 5 (عدّى) ← 5 ديسمبر
        $this->assertSame('2026-12-05', $this->rule(['frequency' => 'monthly', 'month_day' => 5])->next_run_at->toDateString());

        // بيبدأ بعد كده
        $this->assertSame('2026-12-02', $this->rule(['starts_on' => '2026-12-01'])->next_run_at->toDateString());

        // انتهت ← مفيش ميعاد
        $this->assertNull($this->rule(['starts_on' => '2026-10-01', 'ends_on' => '2026-10-31'])->next_run_at);
    }

    // ========================================================
    //  إنشاء المهام
    // ========================================================

    public function test_run_due_creates_task_once_and_schedules_next(): void
    {
        $rule = $this->rule();
        Carbon::setTestNow('2026-11-18 08:02:00');

        $this->assertSame(1, $this->recurrences->runDue());
        $this->assertSame(0, $this->recurrences->runDue(), 'second run created a duplicate');

        $task = Task::where('recurrence_id', $rule->id)->sole();
        $this->assertSame('Weekly report', $task->title);
        $this->assertSame('high', $task->priority);
        $this->assertSame([$this->staff->id], $task->assignees()->pluck('employees.id')->all());
        $this->assertSame(2, $task->checklist_count);
        $this->assertSame('2026-11-20 17:00', $task->due_at->format('Y-m-d H:i'));
        $this->assertTrue($task->creator->is($this->admin));

        $rule->refresh();
        $this->assertSame(1, $rule->occurrences_count);
        $this->assertSame('2026-11-22 08:00', $rule->next_run_at->format('Y-m-d H:i'));

        // المكلّف وصله إشعار التكليف
        $this->assertTrue(EmployeeNotification::where('employee_id', $this->staff->id)->where('type', 'task_assigned')->exists());
    }

    public function test_missed_runs_create_only_one_task(): void
    {
        $rule = $this->rule(['frequency' => 'daily', 'weekdays' => []]);
        Carbon::setTestNow('2026-11-25 09:00:00');   // السيرفر كان واقف أسبوع

        $this->recurrences->runDue();

        $this->assertSame(1, Task::where('recurrence_id', $rule->id)->count());
        $this->assertSame('2026-11-26 08:00', $rule->fresh()->next_run_at->format('Y-m-d H:i'));
    }

    public function test_rule_ends_and_skips_invalid_assignees(): void
    {
        $rule = $this->rule(['frequency' => 'daily', 'weekdays' => [], 'ends_on' => '2026-11-16', 'assignee_ids' => [$this->staff->id, $this->colleague->id]]);
        $this->colleague->update(['status' => 'terminated']);

        Carbon::setTestNow('2026-11-16 08:01:00');
        $this->recurrences->runDue();

        $task = Task::where('recurrence_id', $rule->id)->sole();
        $this->assertSame([$this->staff->id], $task->assignees()->pluck('employees.id')->all());
        $this->assertFalse($rule->fresh()->is_active);
        $this->assertNull($rule->fresh()->next_run_at);
    }

    public function test_paused_rule_does_not_run(): void
    {
        $rule = $this->recurrences->toggle($this->rule());
        Carbon::setTestNow('2026-11-18 09:00:00');

        $this->assertSame(0, $this->recurrences->runDue());
        $this->assertNull($rule->fresh()->next_run_at);

        $this->recurrences->toggle($rule->fresh());
        $this->assertNotNull($rule->fresh()->next_run_at);
    }

    // ========================================================
    //  التذكيرات
    // ========================================================

    private function task(array $data): Task
    {
        return app(TaskService::class)->create($data + [
            'title' => 'Deliver', 'branch_id' => $this->branch->id, 'assignee_ids' => [$this->staff->id],
        ], $this->admin);
    }

    public function test_due_soon_reminder_is_sent_once_per_due_date(): void
    {
        $task = $this->task(['due_at' => '2026-11-16 18:00:00']);
        $this->task(['title' => 'Far', 'due_at' => '2026-11-25 18:00:00']);

        $this->assertSame(1, $this->reminders->sendDueSoon());
        $this->assertSame(0, $this->reminders->sendDueSoon());
        $this->assertSame(1, EmployeeNotification::where('employee_id', $this->staff->id)->where('type', 'task_due_soon')->count());

        // الموعد اتغيّر ← تذكير جديد للموعد الجديد
        $task->update(['due_at' => '2026-11-17 06:00:00']);
        $this->assertSame(1, $this->reminders->sendDueSoon());
    }

    public function test_overdue_alert_goes_to_assignees_and_admin_creator_once(): void
    {
        $this->task(['due_at' => '2026-11-15 10:00:00']);
        $this->task(['title' => 'Ancient', 'due_at' => '2026-08-01 10:00:00']);   // أقدم من 30 يوم ← لا
        $this->task(['title' => 'Done', 'due_at' => '2026-11-15 10:00:00'])->forceFill(['status' => 'completed'])->saveQuietly();

        $this->assertSame(1, $this->reminders->sendOverdue());
        $this->assertSame(0, $this->reminders->sendOverdue());

        $this->assertTrue(EmployeeNotification::where('employee_id', $this->staff->id)->where('type', 'task_overdue')->exists());
        $this->assertTrue(AdminNotification::where('admin_id', $this->admin->id)->where('type', 'task_activity')->exists());
    }

    public function test_progress_reminder_and_reschedule(): void
    {
        $task = $this->task(['progress_update_interval' => 'custom', 'progress_update_every_days' => 3]);
        $task->forceFill(['next_progress_update_at' => now()->subHour()])->saveQuietly();
        $auto = $this->task(['title' => 'Auto', 'progress_source' => 'checklist', 'progress_update_interval' => 'daily']);
        $auto->forceFill(['next_progress_update_at' => now()->subHour()])->saveQuietly();

        $this->assertSame(1, $this->reminders->sendProgressDue());
        $this->assertSame(0, $this->reminders->sendProgressDue());
        $this->assertSame('2026-11-19', $task->fresh()->next_progress_update_at->toDateString());
        $this->assertTrue(EmployeeNotification::where('employee_id', $this->staff->id)->where('type', 'task_progress_due')->exists());
    }

    public function test_stale_timers_are_closed_with_cap(): void
    {
        $task = $this->task([]);
        app(TaskTimeService::class)->start($task, $this->staff);
        Carbon::setTestNow(now()->addHours(30));

        $this->assertSame(1, $this->reminders->closeStaleTimers(app(TaskTimeService::class)));
        $this->assertSame(TaskTimeService::maxTimerMinutes(), TaskTimeEntry::sole()->minutes);
    }

    // ========================================================
    //  الأمر والجدولة والشاشات
    // ========================================================

    public function test_command_runs_and_is_scheduled(): void
    {
        $this->rule(['frequency' => 'daily', 'weekdays' => []]);
        Carbon::setTestNow('2026-11-16 08:01:00');

        $this->artisan('tasks:automation')->expectsOutputToContain('recurring tasks created')->assertSuccessful();
        $this->assertSame(1, Task::count());

        $events = collect(app(Schedule::class)->events())->map(fn ($e) => $e->command);
        $this->assertTrue($events->contains(fn ($c) => str_contains((string) $c, 'tasks:automation')));
    }

    public function test_admin_recurrence_screens(): void
    {
        $this->get(route('admin.tasks.recurrences.create'))->assertOk();

        $this->post(route('admin.tasks.recurrences.store'), [
            'title' => 'Daily standup notes', 'priority' => 'medium', 'branch_id' => $this->branch->id,
            'frequency' => 'weekly', 'interval' => 1, 'run_time' => '09:00', 'duration_days' => 0, 'starts_on' => '2026-11-16',
        ])->assertSessionHasErrors('weekdays');

        $this->post(route('admin.tasks.recurrences.store'), [
            'title' => 'Daily standup notes', 'priority' => 'medium', 'branch_id' => $this->branch->id, 'assignee_ids' => [$this->staff->id],
            'frequency' => 'weekly', 'interval' => 1, 'weekdays' => [1, 2], 'run_time' => '09:00', 'duration_days' => 0,
            'starts_on' => '2026-11-16', 'estimated_hours' => 0.5, 'is_active' => '1',
        ])->assertRedirect(route('admin.tasks.recurrences.index'));

        $rule = TaskRecurrence::sole();
        $this->assertSame('2026-11-16 09:00', $rule->next_run_at->format('Y-m-d H:i'));
        $this->assertSame(30, $rule->blueprint['estimated_minutes']);

        $this->get(route('admin.tasks.recurrences.index'))->assertOk()->assertSee('Daily standup notes')->assertSee($rule->describe());
        $this->get(route('admin.tasks.recurrences.edit', $rule->id))->assertOk();

        $this->post(route('admin.tasks.recurrences.toggle', $rule->id))->assertSessionHas('success');
        $this->assertFalse($rule->fresh()->is_active);

        $this->delete(route('admin.tasks.recurrences.destroy', $rule->id))->assertRedirect(route('admin.tasks.recurrences.index'));
        $this->assertSoftDeleted($rule);
    }

    public function test_recurrence_screens_require_permission(): void
    {
        $role = Role::create(['name' => 'NoRec', 'roles' => ['task_show']]);
        $this->actingAs(Admin::create(['name' => 'N', 'email' => 'n@t.com', 'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id]), 'admin');

        $this->get(route('admin.tasks.recurrences.index'))->assertRedirect(route('admin.error_403'));
    }
}
