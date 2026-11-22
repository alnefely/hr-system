<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Task;
use App\Services\Portal\PortalAccessService;
use App\Services\Tasks\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * مهامي في بوابة الموظف: التبويبات، الإنشاء لزميل، العمليات، والخصوصية.
 */
class PortalTaskScreensTest extends TestCase
{
    use RefreshDatabase;

    private Employee $manager;

    private Employee $staff;

    private Employee $colleague;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $title = JobTitle::create(['name' => 'Staff', 'is_active' => true]);
        $make = fn (string $name, string $phone, ?int $manager = null) => Employee::create([
            'branch_id' => $branch->id, 'job_title_id' => $title->id, 'name' => $name, 'employee_code' => 'E-' . $phone,
            'phone' => $phone, 'manager_id' => $manager, 'status' => 'active', 'basic_salary' => 3000, 'hire_date' => '2024-01-01',
        ]);

        $this->manager = $make('Mona Manager', '01000000001');
        $this->staff = $make('Sara Staff', '01000000002', $this->manager->id);
        $this->colleague = $make('Karim Colleague', '01000000003');

        foreach ([$this->manager, $this->staff, $this->colleague] as $employee) {
            app(PortalAccessService::class)->enable($employee, 'Secret123');
            $employee->forceFill(['must_change_password' => false])->save();
        }
    }

    private function as(Employee $employee): static
    {
        return $this->actingAs($employee->fresh(), 'employee');
    }

    private function taskFor(Employee $assignee, Employee $creator, array $data = []): Task
    {
        return app(TaskService::class)->create($data + [
            'title' => 'Design banner', 'branch_id' => $assignee->branch_id, 'assignee_ids' => [$assignee->id],
        ], $creator);
    }

    public function test_index_tabs(): void
    {
        $this->taskFor($this->staff, $this->colleague);
        $this->taskFor($this->colleague, $this->staff, ['title' => 'Review copy']);

        $this->as($this->staff)->get(route('portal.tasks.index'))
            ->assertOk()->assertSee('Design banner')->assertDontSee('Review copy');

        $this->as($this->staff)->get(route('portal.tasks.index', ['tab' => 'created']))
            ->assertOk()->assertSee('Review copy')->assertDontSee('Design banner');

        // تبويب الفريق للمدير بس
        $this->as($this->manager)->get(route('portal.tasks.index', ['tab' => 'team']))
            ->assertOk()->assertSee('Design banner')->assertSee(__('task.tabs.team'));

        $this->as($this->colleague)->get(route('portal.tasks.index'))
            ->assertOk()->assertDontSee(__('task.tabs.team'));
    }

    public function test_employee_creates_task_for_colleague(): void
    {
        $this->as($this->staff)->get(route('portal.tasks.create'))->assertOk()->assertSee('Karim Colleague');

        $this->as($this->staff)->post(route('portal.tasks.store'), [
            'title' => 'Send invoice', 'priority' => 'high', 'progress_source' => 'manual',
            'progress_update_interval' => 'daily', 'requires_review' => '1',
            'assignee_ids' => [$this->colleague->id], 'checklist' => ['print'],
        ])->assertSessionHas('success');

        $task = Task::where('title', 'Send invoice')->firstOrFail();
        $this->assertTrue($task->creator->is($this->staff));
        $this->assertSame($this->staff->branch_id, $task->branch_id);
        $this->assertSame([$this->colleague->id], $task->assignees()->pluck('employees.id')->all());
    }

    public function test_full_workflow_from_portal(): void
    {
        $task = $this->taskFor($this->staff, $this->colleague, ['checklist' => ['step one']]);

        $this->as($this->staff)->get(route('portal.tasks.show', $task->id))
            ->assertOk()->assertSee('step one')->assertSee(__('task.actions.start'))->assertSee(__('task.actions.decline'))
            ->assertDontSee(__('task.actions.complete'));

        $this->as($this->staff)->post(route('portal.tasks.status', $task->id), ['status' => 'in_progress'])->assertSessionHas('success');
        $this->as($this->staff)->post(route('portal.tasks.progress', $task->id), ['progress' => 60, 'note' => 'going'])->assertSessionHas('success');

        $item = $task->checklistItems()->first();
        $this->as($this->staff)->patch(route('portal.tasks.checklist.toggle', [$task->id, $item->id]))->assertSessionHas('success');
        $this->assertTrue($item->fresh()->is_done);

        // المكلّف مايقدرش يقفلها — يسلّمها للمراجعة
        $this->as($this->staff)->post(route('portal.tasks.status', $task->id), ['status' => 'completed'])->assertSessionHas('error');
        $this->as($this->staff)->post(route('portal.tasks.status', $task->id), ['status' => 'in_review'])->assertSessionHas('success');

        // اللي أنشأها بيشوف زرار الإكمال ويعتمد
        $this->as($this->colleague)->get(route('portal.tasks.show', $task->id))->assertOk()->assertSee(__('task.actions.complete'));
        $this->as($this->colleague)->post(route('portal.tasks.status', $task->id), ['status' => 'completed'])->assertSessionHas('success');

        $this->assertSame('completed', $task->fresh()->status);
    }

    public function test_decline(): void
    {
        $task = $this->taskFor($this->staff, $this->colleague);

        $this->as($this->staff)->post(route('portal.tasks.decline', $task->id), [])->assertSessionHasErrors('reason');
        $this->as($this->staff)->post(route('portal.tasks.decline', $task->id), ['reason' => 'on leave'])
            ->assertRedirect(route('portal.tasks.index'));

        $this->assertSame(0, $task->assignees()->count());
    }

    public function test_unrelated_employee_gets_404(): void
    {
        $task = $this->taskFor($this->staff, $this->manager);

        $this->as($this->colleague)->get(route('portal.tasks.show', $task->id))->assertNotFound();
        $this->as($this->colleague)->post(route('portal.tasks.status', $task->id), ['status' => 'in_progress'])->assertNotFound();
        $this->as($this->colleague)->get(route('portal.tasks.edit', $task->id))->assertNotFound();
    }

    public function test_assignee_cannot_edit_or_delete(): void
    {
        $task = $this->taskFor($this->staff, $this->colleague);

        $this->as($this->staff)->get(route('portal.tasks.edit', $task->id))->assertForbidden();
        $this->as($this->staff)->delete(route('portal.tasks.destroy', $task->id))->assertSessionHas('error');
        $this->assertNotSoftDeleted($task);
    }

    public function test_creator_edits_and_manages_assignees(): void
    {
        $task = $this->taskFor($this->staff, $this->colleague);

        $this->as($this->colleague)->get(route('portal.tasks.edit', $task->id))->assertOk();
        $this->as($this->colleague)->put(route('portal.tasks.update', $task->id), [
            'title' => 'Banner v2', 'priority' => 'low', 'progress_source' => 'manual', 'progress_update_interval' => 'none',
        ])->assertRedirect(route('portal.tasks.show', $task->id));
        $this->assertSame('Banner v2', $task->fresh()->title);

        $this->as($this->colleague)->put(route('portal.tasks.assignees', $task->id), ['assignee_ids' => [$this->manager->id]])
            ->assertSessionHas('success');
        $this->assertSame([$this->manager->id], $task->assignees()->pluck('employees.id')->all());
    }

    public function test_subtask_from_portal(): void
    {
        $task = $this->taskFor($this->staff, $this->colleague);

        $this->as($this->staff)->get(route('portal.tasks.create', ['parent_id' => $task->id]))->assertOk()->assertSee('Design banner');
        $this->as($this->staff)->post(route('portal.tasks.store'), [
            'title' => 'Pick colors', 'parent_id' => $task->id, 'priority' => 'medium',
            'progress_source' => 'manual', 'progress_update_interval' => 'none', 'assignee_ids' => [$this->staff->id],
        ])->assertSessionHas('success');

        $this->assertSame(1, $task->fresh()->subtasks_count);

        // المدير المباشر بيشوف المهمة لكن مش مكلّف ولا صاحبها ← مايضيفش فرعية
        $this->as($this->manager)->get(route('portal.tasks.create', ['parent_id' => $task->id]))->assertForbidden();
    }
}
