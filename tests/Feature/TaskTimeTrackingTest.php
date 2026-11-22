<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Exceptions\InvalidStateException;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskTimeEntry;
use App\Services\Portal\PortalAccessService;
use App\Services\Tasks\TaskService;
use App\Services\Tasks\TaskTimeService;
use App\Support\Duration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * تتبع الوقت: العدّاد، الإدخال اليدوي، الحد الأقصى، الإيقاف التلقائي، وسجل وقتي.
 */
class TaskTimeTrackingTest extends TestCase
{
    use RefreshDatabase;

    private TaskTimeService $time;

    private Admin $admin;

    private Employee $staff;

    private Employee $stranger;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-18 10:00:00');

        $this->time = app(TaskTimeService::class);
        $branch = Branch::create(['name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $title = JobTitle::create(['name' => 'Staff', 'is_active' => true]);
        foreach (['staff' => '01000000001', 'stranger' => '01000000002'] as $prop => $phone) {
            $e = Employee::create([
                'branch_id' => $branch->id, 'job_title_id' => $title->id, 'name' => ucfirst($prop), 'employee_code' => 'E-' . $phone,
                'phone' => $phone, 'status' => 'active', 'basic_salary' => 3000, 'hire_date' => '2024-01-01',
            ]);
            app(PortalAccessService::class)->enable($e, 'Secret123');
            $e->forceFill(['must_change_password' => false])->save();
            $this->{$prop} = $e->fresh();
        }

        $role = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $this->admin = Admin::create(['name' => 'Root', 'email' => 'root@test.com', 'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id]);
        $this->actingAs($this->admin, 'admin');

        $this->task = app(TaskService::class)->create([
            'title' => 'Write report', 'branch_id' => $branch->id, 'assignee_ids' => [$this->staff->id], 'estimated_minutes' => 120,
        ], $this->admin);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_timer_start_stop_records_minutes(): void
    {
        $entry = $this->time->start($this->task, $this->staff);
        $this->assertTrue($entry->isRunning());

        // ضغطة تانية على نفس المهمة ← نفس العدّاد
        $this->assertSame($entry->id, $this->time->start($this->task, $this->staff)->id);

        Carbon::setTestNow(now()->addMinutes(45)->addSeconds(10));
        $this->time->stop($this->staff, 'drafted intro');

        $entry->refresh();
        $this->assertSame(46, $entry->minutes);
        $this->assertSame('drafted intro', $entry->note);
        $this->assertSame(46, $this->task->fresh()->spent_minutes);
    }

    public function test_starting_another_task_stops_the_current_timer(): void
    {
        $other = app(TaskService::class)->create(['title' => 'Other', 'branch_id' => $this->task->branch_id, 'assignee_ids' => [$this->staff->id]], $this->admin);

        $this->time->start($this->task, $this->staff);
        Carbon::setTestNow(now()->addMinutes(20));
        $this->time->start($other, $this->staff);

        $this->assertSame(1, TaskTimeEntry::running()->where('employee_id', $this->staff->id)->count());
        $this->assertSame(20, $this->task->fresh()->spent_minutes);
    }

    public function test_forgotten_timer_is_capped(): void
    {
        $entry = $this->time->start($this->task, $this->staff);
        Carbon::setTestNow(now()->addDays(3));
        $this->time->stop($this->staff);

        $this->assertSame(TaskTimeService::maxTimerMinutes(), $entry->fresh()->minutes);
    }

    public function test_completing_task_stops_running_timers(): void
    {
        $entry = $this->time->start($this->task, $this->staff);
        Carbon::setTestNow(now()->addMinutes(30));

        app(TaskService::class)->changeStatus($this->task, TaskStatus::Completed, $this->admin);

        $this->assertFalse($entry->fresh()->isRunning());
        $this->assertSame(30, $this->task->fresh()->spent_minutes);
    }

    public function test_stranger_cannot_track_time(): void
    {
        $this->expectException(InvalidStateException::class);
        $this->time->start($this->task, $this->stranger);
    }

    public function test_manual_entries_and_rules(): void
    {
        $this->time->addManual($this->task, $this->staff, 90, Carbon::parse('2026-11-17'), 'meeting');
        $this->assertSame(90, $this->task->fresh()->spent_minutes);

        foreach ([[0, '2026-11-17'], [25 * 60, '2026-11-17'], [30, '2026-11-25']] as [$minutes, $date]) {
            try {
                $this->time->addManual($this->task, $this->staff, $minutes, Carbon::parse($date));
                $this->fail('invalid manual entry accepted');
            } catch (InvalidStateException) {
                $this->addToAssertionCount(1);
            }
        }

        // المشرف يسجّل لموظف مكلّف بس
        $this->time->addManual($this->task, $this->admin, 30, today(), null, $this->staff->id);
        $this->assertSame(120, $this->task->fresh()->spent_minutes);

        $this->expectException(InvalidStateException::class);
        $this->time->addManual($this->task, $this->admin, 30, today(), null, $this->stranger->id);
    }

    public function test_portal_endpoints_and_delete_rules(): void
    {
        $this->actingAs($this->staff, 'employee');

        $this->post(route('portal.tasks.timer.start', $this->task->id))->assertSessionHas('success');
        $this->get(route('portal.tasks.show', $this->task->id))->assertOk()->assertSee(__('task.time.running'))->assertSee('data-elapsed-since', false);

        // الشريط العلوي في أي صفحة فيه العدّاد وزرار الإيقاف
        $this->get(route('portal.home'))->assertOk()->assertSee(route('portal.time.stop'), false);

        Carbon::setTestNow(now()->addMinutes(15));
        $this->post(route('portal.time.stop'))->assertSessionHas('success');
        $this->assertSame(15, $this->task->fresh()->spent_minutes);

        $this->post(route('portal.tasks.time.store', $this->task->id), ['hours' => 1, 'minutes' => 30, 'date' => '2026-11-17', 'note' => 'calls'])
            ->assertSessionHas('success');
        $this->assertSame(105, $this->task->fresh()->spent_minutes);

        $entry = TaskTimeEntry::where('is_manual', true)->sole();

        // موظف تاني مايحذفش سجل غيره (ومش شايف المهمة أصلاً)
        $this->actingAs($this->stranger, 'employee');
        $this->delete(route('portal.tasks.time.destroy', [$this->task->id, $entry->id]))->assertNotFound();

        $this->actingAs($this->staff, 'employee');
        $this->delete(route('portal.tasks.time.destroy', [$this->task->id, $entry->id]))->assertSessionHas('success');
        $this->assertSame(15, $this->task->fresh()->spent_minutes);

        $this->get(route('portal.time.index'))->assertOk()->assertSee('Write report')->assertSee(Duration::format(15));
    }

    public function test_admin_task_page_shows_time_and_manual_form(): void
    {
        $this->time->addManual($this->task, $this->staff, 150, today());

        $this->get(route('admin.tasks.show', $this->task->id))
            ->assertOk()
            ->assertSee(__('task.time.over', ['time' => Duration::format(30)]))
            ->assertSee('name="employee_id"', false);

        $this->post(route('admin.tasks.time.store', $this->task->id), ['hours' => 0, 'minutes' => 20, 'date' => today()->toDateString(), 'employee_id' => $this->staff->id])
            ->assertSessionHas('success');
        $this->assertSame(170, $this->task->fresh()->spent_minutes);
    }

    public function test_duration_format(): void
    {
        $this->assertSame(__('task.time.m', ['m' => 45]), Duration::format(45));
        $this->assertSame(__('task.time.h', ['h' => 2]), Duration::format(120));
        $this->assertSame(__('task.time.hm', ['h' => 1, 'm' => 5]), Duration::format(65));
        $this->assertSame(__('task.time.dh', ['d' => 2, 'h' => 3]), Duration::format(2 * 1440 + 180));
    }
}
