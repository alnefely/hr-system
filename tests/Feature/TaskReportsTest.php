<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\Task;
use App\Services\Portal\PortalAccessService;
use App\Services\Tasks\TaskMetricsService;
use App\Services\Tasks\TaskService;
use App\Services\Tasks\TaskTimeService;
use App\Support\BranchScopeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * التقارير: صحة المقاييس، الفترات، صفحة الشركة والتصدير، أدائي وفريقي.
 */
class TaskReportsTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $tasks;

    private TaskMetricsService $metrics;

    private Branch $branch;

    private Admin $admin;

    private Employee $manager;

    private Employee $staff;

    private Employee $colleague;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-20 12:00:00');

        $this->tasks = app(TaskService::class);
        $this->metrics = app(TaskMetricsService::class);
        $this->branch = Branch::create(['name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $title = JobTitle::create(['name' => 'Staff', 'is_active' => true]);
        $make = function (string $name, string $phone, ?int $manager = null) use ($title) {
            $e = Employee::create([
                'branch_id' => $this->branch->id, 'job_title_id' => $title->id, 'name' => $name, 'employee_code' => 'E-' . $phone,
                'phone' => $phone, 'manager_id' => $manager, 'status' => 'active', 'basic_salary' => 3000, 'hire_date' => '2024-01-01',
            ]);
            app(PortalAccessService::class)->enable($e, 'Secret123');
            $e->forceFill(['must_change_password' => false])->save();

            return $e->fresh();
        };
        $this->manager = $make('Mona Manager', '01000000001');
        $this->staff = $make('Sara Staff', '01000000002', $this->manager->id);
        $this->colleague = $make('Karim Colleague', '01000000003', $this->manager->id);

        $role = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $this->admin = Admin::create(['name' => 'Root', 'email' => 'root@test.com', 'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id]);
        $this->actingAs($this->admin, 'admin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** مهمة اكتملت في وقت معيّن بمواعيد معيّنة. */
    private function completed(Employee $assignee, string $due, string $doneAt, array $extra = []): Task
    {
        $task = $this->tasks->create($extra + [
            'title' => 'T' . uniqid(), 'branch_id' => $this->branch->id, 'assignee_ids' => [$assignee->id],
            'due_at' => $due, 'requires_review' => false,
        ], $this->admin);

        Carbon::setTestNow($doneAt);
        $this->tasks->changeStatus($task, TaskStatus::InProgress, $this->admin);
        $this->tasks->changeStatus($task->fresh(), TaskStatus::Completed, $this->admin);
        Carbon::setTestNow('2026-11-20 12:00:00');

        return $task->fresh();
    }

    public function test_summary_metrics(): void
    {
        // في الميعاد
        $this->completed($this->staff, '2026-11-10 17:00', '2026-11-09 10:00', ['estimated_minutes' => 60]);
        // متأخرة
        $late = $this->completed($this->staff, '2026-11-10 17:00', '2026-11-12 10:00', ['estimated_minutes' => 60]);
        // من غير موعد (مابتدخلش في نسبة الالتزام)
        $this->completed($this->colleague, '', '2026-11-15 10:00');
        // مفتوحة ومتأخرة
        $this->tasks->create(['title' => 'Open late', 'branch_id' => $this->branch->id, 'assignee_ids' => [$this->staff->id], 'due_at' => '2026-11-18 10:00'], $this->admin);
        // اكتملت الشهر اللي فات ← برّه الفترة
        $this->completed($this->staff, '2026-10-10 17:00', '2026-10-09 10:00');

        app(TaskTimeService::class)->addManual($late, $this->staff, 90, Carbon::parse('2026-11-11'));

        $period = TaskMetricsService::period('this_month');
        $s = $this->metrics->summary(Task::query(), $period['from'], $period['to']);

        $this->assertSame(3, $s['completed']);
        $this->assertSame(1, $s['open']);
        $this->assertSame(1, $s['overdue']);
        $this->assertSame(50, $s['on_time_rate']);
        $this->assertSame(2, $s['on_time_base']);
        // المهمة اللي اتسجّل عليها وقت بس: 90 فعلي ÷ 60 متوقع
        $this->assertSame(150, $s['estimate_ratio']);
        $this->assertSame(90, $s['time_logged']);
        $this->assertNotNull($s['avg_cycle_minutes']);
    }

    public function test_rework_rate_and_employee_table(): void
    {
        $task = $this->tasks->create(['title' => 'Review me', 'branch_id' => $this->branch->id, 'assignee_ids' => [$this->staff->id]], $this->colleague);
        $this->tasks->changeStatus($task, TaskStatus::InReview, $this->staff);
        $this->tasks->changeStatus($task->fresh(), TaskStatus::InProgress, $this->colleague, 'fix');
        $this->tasks->changeStatus($task->fresh(), TaskStatus::InReview, $this->staff);
        $this->tasks->changeStatus($task->fresh(), TaskStatus::Completed, $this->colleague);

        $period = TaskMetricsService::period('this_month');
        $this->assertSame(50, $this->metrics->summary(Task::query(), $period['from'], $period['to'])['rework_rate']);

        $row = $this->metrics->employees(Task::query(), $period['from'], $period['to'])->firstWhere('employee.id', $this->staff->id);
        $this->assertSame(1, $row['completed']);
        $this->assertSame(1, $row['returned']);
        $this->assertSame(0, $row['open']);
    }

    public function test_period_presets(): void
    {
        $this->assertSame('2026-10-01', TaskMetricsService::period('last_month')['from']->toDateString());
        $this->assertSame('2026-10-31', TaskMetricsService::period('last_month')['to']->toDateString());
        $this->assertSame('2026-10-22', TaskMetricsService::period('last_30')['from']->toDateString());
        $custom = TaskMetricsService::period('custom', '2026-11-15', '2026-11-01');   // مقلوبة ← بتتعدل
        $this->assertSame('2026-11-01', $custom['from']->toDateString());
        $this->assertSame('this_month', TaskMetricsService::period('bogus')['preset']);
    }

    public function test_weekly_trend(): void
    {
        $this->completed($this->staff, '', '2026-11-19 10:00');
        $this->completed($this->staff, '', '2026-11-18 10:00');
        $this->completed($this->staff, '', '2026-11-05 10:00');

        $trend = $this->metrics->weeklyTrend(Task::query());

        $this->assertCount(8, $trend['values']);
        $this->assertSame(2, end($trend['values']));
        $this->assertSame(3, array_sum($trend['values']));
    }

    public function test_admin_reports_page_and_export(): void
    {
        $this->completed($this->staff, '2026-11-10 17:00', '2026-11-09 10:00');

        $this->get(route('admin.tasks.reports'))->assertOk()
            ->assertSee(__('task.reports.on_time'))->assertSee('Sara Staff')->assertSee('tasksTrendChart', false);
        $this->get(route('admin.tasks.reports', ['period' => 'custom', 'from' => '2026-11-01', 'to' => '2026-11-30']))->assertOk();

        $response = $this->get(route('admin.tasks.reports.export'));
        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
    }

    public function test_reports_permission_and_branch_isolation(): void
    {
        $other = Branch::create(['name' => 'B2', 'code' => 'B2', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $this->tasks->create(['title' => 'Foreign overdue', 'branch_id' => $other->id, 'due_at' => '2026-11-01 10:00'], $this->admin);

        $role = Role::create(['name' => 'NoReport', 'roles' => ['task_show']]);
        $this->actingAs(Admin::create(['name' => 'N', 'email' => 'n@t.com', 'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id]), 'admin');
        $this->get(route('admin.tasks.reports'))->assertRedirect(route('admin.error_403'));

        $branchRole = Role::create(['name' => 'Branch', 'roles' => all_roles()]);
        $this->actingAs(Admin::create(['name' => 'B', 'email' => 'b@t.com', 'password' => bcrypt('x'), 'main' => false, 'role_id' => $branchRole->id, 'employee_id' => $this->staff->id]), 'admin');
        BranchScopeResolver::forget();

        $this->get(route('admin.tasks.reports'))->assertOk()->assertDontSee('Foreign overdue');

        BranchScopeResolver::forget();
    }

    public function test_portal_performance_for_employee_and_manager(): void
    {
        $this->completed($this->staff, '2026-11-10 17:00', '2026-11-09 10:00');
        $this->tasks->create(['title' => 'Team late one', 'branch_id' => $this->branch->id, 'assignee_ids' => [$this->colleague->id], 'due_at' => '2026-11-15 10:00'], $this->admin);

        // الموظف: أرقامه بس، من غير جدول فريق
        $this->actingAs($this->staff, 'employee')->get(route('portal.tasks.performance'))
            ->assertOk()->assertSee(__('task.reports.my_title'))->assertDontSee(__('task.reports.team_title'));

        // المدير: فريقه
        $this->actingAs($this->manager, 'employee')->get(route('portal.tasks.performance'))
            ->assertOk()->assertSee(__('task.reports.team_title'))->assertSee('Sara Staff')->assertSee('Karim Colleague')->assertSee('Team late one');
    }

    public function test_portal_home_shows_my_tasks(): void
    {
        $this->tasks->create(['title' => 'Due soon task', 'branch_id' => $this->branch->id, 'assignee_ids' => [$this->staff->id], 'due_at' => '2026-11-21 10:00'], $this->admin);

        $this->actingAs($this->staff, 'employee')->get(route('portal.home'))->assertOk()->assertSee('Due soon task');
    }
}
