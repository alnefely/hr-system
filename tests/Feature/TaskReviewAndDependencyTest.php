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
use App\Models\TaskReview;
use App\Services\Portal\PortalAccessService;
use App\Services\Tasks\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * سجل المراجعات (تسليم ← اعتماد/إرجاع بسبب) وقوائم "بانتظار مراجعتي"،
 * وارتباط المهام (منع الحلقات، إشعار لما المهمة تتفك).
 */
class TaskReviewAndDependencyTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $service;

    private Branch $branch;

    private Admin $admin;

    private Employee $manager;

    private Employee $staff;

    private Employee $colleague;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TaskService::class);
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
        $this->colleague = $make('Karim Colleague', '01000000003');

        $role = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $this->admin = Admin::create(['name' => 'Root', 'email' => 'root@test.com', 'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id]);
    }

    private function task(array $data = [], ?object $creator = null): Task
    {
        return $this->service->create($data + [
            'title' => 'Draft contract', 'branch_id' => $this->branch->id, 'assignee_ids' => [$this->staff->id],
        ], $creator ?? $this->colleague);
    }

    // ========================================================
    //  المراجعات
    // ========================================================

    public function test_submit_creates_pending_review_for_resolved_reviewer(): void
    {
        $task = $this->task();

        $this->service->changeStatus($task, TaskStatus::InReview, $this->staff, 'ready, see v2');

        $review = TaskReview::sole();
        $this->assertSame('pending', $review->status);
        $this->assertTrue($review->submitter->is($this->staff));
        $this->assertTrue($review->reviewer->is($this->colleague));
        $this->assertSame('ready, see v2', $review->submission_note);
        $this->assertSame('review_submitted', $task->activities()->first()->event);
    }

    public function test_approval_closes_review_with_actual_decider(): void
    {
        $task = $this->task();
        $this->service->changeStatus($task, TaskStatus::InReview, $this->staff);

        // المدير المباشر للمكلّف بيعتمد بدل اللي أنشأها
        $this->service->changeStatus($task->fresh(), TaskStatus::Completed, $this->manager, 'great');

        $review = TaskReview::sole();
        $this->assertSame('approved', $review->status);
        $this->assertTrue($review->reviewer->is($this->manager));
        $this->assertSame('great', $review->decision_note);
        $this->assertNotNull($review->decided_at);
    }

    public function test_return_requires_reason_and_approver(): void
    {
        $task = $this->task();
        $this->service->changeStatus($task, TaskStatus::InReview, $this->staff);

        // المكلّف مايسحبش المهمة من المراجعة بنفسه
        try {
            $this->service->changeStatus($task->fresh(), TaskStatus::InProgress, $this->staff, 'oops');
            $this->fail('assignee pulled the task back from review');
        } catch (InvalidStateException) {
            $this->assertSame('in_review', $task->fresh()->status);
        }

        try {
            $this->service->changeStatus($task->fresh(), TaskStatus::InProgress, $this->colleague, '  ');
            $this->fail('returned without a reason');
        } catch (InvalidStateException $e) {
            $this->assertSame(__('task.errors.return_reason_required'), $e->getMessage());
        }

        $this->service->changeStatus($task->fresh(), TaskStatus::InProgress, $this->colleague, 'fix section 3');

        $review = TaskReview::sole();
        $this->assertSame('rejected', $review->status);
        $this->assertSame('fix section 3', $review->decision_note);
        $this->assertTrue(EmployeeNotification::where('employee_id', $this->staff->id)->where('type', 'task_returned')->exists());

        // تسليم تاني = مراجعة جديدة
        $this->service->changeStatus($task->fresh(), TaskStatus::InReview, $this->staff);
        $this->assertSame(2, TaskReview::count());
        $this->assertSame(1, TaskReview::where('status', 'pending')->count());
    }

    public function test_submit_without_any_reviewer_is_refused(): void
    {
        $task = $this->task(['assignee_ids' => [$this->colleague->id]]);

        $this->expectException(InvalidStateException::class);
        $this->service->changeStatus($task, TaskStatus::InReview, $this->colleague);
    }

    public function test_awaiting_review_lists(): void
    {
        $mine = $this->task(['title' => 'For Karim']);
        $this->service->changeStatus($mine, TaskStatus::InReview, $this->staff);
        $other = $this->task(['title' => 'Not mine'], $this->manager);

        $this->assertSame([$mine->id], Task::awaitingReviewBy($this->colleague)->pluck('id')->all());

        $this->actingAs($this->colleague, 'employee')
            ->get(route('portal.tasks.index', ['tab' => 'review']))
            ->assertOk()->assertSee('For Karim')->assertDontSee('Not mine')->assertSee(__('task.tabs.review'));

        // المشرف
        $this->actingAs($this->admin, 'admin');
        $adminTask = $this->task(['title' => 'Admin owned'], $this->admin);
        $this->service->changeStatus($adminTask, TaskStatus::InReview, $this->staff);

        $this->get(route('admin.tasks.index'))->assertOk()->assertSee(__('task.review.awaiting_me'));
        $json = $this->getJson(route('admin.tasks.index', ['review' => 'mine']), ['X-Requested-With' => 'XMLHttpRequest'])->json();
        $this->assertSame(1, $json['recordsFiltered']);
        $this->assertStringContainsString('Admin owned', $json['data'][0]['title_html']);
    }

    public function test_return_from_portal_through_modal_fields(): void
    {
        $task = $this->task();
        $this->service->changeStatus($task, TaskStatus::InReview, $this->staff, 'done');

        $this->actingAs($this->colleague, 'employee')->get(route('portal.tasks.show', $task->id))
            ->assertOk()->assertSee('statusNoteModal', false)->assertSee(__('task.actions.return'))->assertSee('done');

        $this->actingAs($this->colleague, 'employee')
            ->post(route('portal.tasks.status', $task->id), ['status' => 'in_progress'])
            ->assertSessionHas('error', __('task.errors.return_reason_required'));

        $this->actingAs($this->colleague, 'employee')
            ->post(route('portal.tasks.status', $task->id), ['status' => 'in_progress', 'note' => 'add totals'])
            ->assertSessionHas('success');

        $this->actingAs($this->staff, 'employee')->get(route('portal.tasks.show', $task->id))->assertOk()->assertSee('add totals');
    }

    // ========================================================
    //  ارتباط المهام
    // ========================================================

    public function test_dependency_add_remove_and_blocking(): void
    {
        $design = $this->task(['title' => 'Design']);
        $build = $this->task(['title' => 'Build']);

        $this->actingAs($this->colleague, 'employee')
            ->post(route('portal.tasks.dependencies.store', $build->id), ['depends_on_id' => $design->id])
            ->assertSessionHas('success');

        $this->assertTrue($build->fresh()->isBlocked());

        $this->actingAs($this->staff, 'employee')
            ->post(route('portal.tasks.status', $build->id), ['status' => 'in_progress'])
            ->assertSessionHas('error', __('task.errors.blocked'));

        $this->actingAs($this->colleague, 'employee')->get(route('portal.tasks.show', $build->id))->assertOk()->assertSee('Design');
        $this->actingAs($this->colleague, 'employee')->get(route('portal.tasks.show', $design->id))->assertOk()->assertSee(__('task.dependencies.blocks'));

        $this->actingAs($this->colleague, 'employee')
            ->delete(route('portal.tasks.dependencies.destroy', [$build->id, $design->id]))
            ->assertSessionHas('success');
        $this->assertFalse($build->fresh()->isBlocked());
    }

    public function test_assignee_cannot_link_tasks(): void
    {
        $a = $this->task();
        $b = $this->task();

        $this->actingAs($this->staff, 'employee')
            ->post(route('portal.tasks.dependencies.store', $b->id), ['depends_on_id' => $a->id])
            ->assertSessionHas('error');
        $this->assertFalse($b->fresh()->isBlocked());
    }

    public function test_cycles_and_self_links_are_refused(): void
    {
        $a = $this->task(['title' => 'A']);
        $b = $this->task(['title' => 'B']);
        $c = $this->task(['title' => 'C']);

        $this->service->addDependency($b, $a->id, $this->colleague);   // B مستنية A
        $this->service->addDependency($c, $b->id, $this->colleague);   // C مستنية B

        foreach ([[$a, $c->id], [$a, $b->id], [$a, $a->id]] as [$task, $dep]) {
            try {
                $this->service->addDependency($task, $dep, $this->colleague);
                $this->fail('cycle or self link accepted');
            } catch (InvalidStateException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_dependency_must_share_project_or_branch(): void
    {
        $project = Project::create(['branch_id' => $this->branch->id, 'name' => 'P', 'manager_id' => $this->colleague->id]);
        $inProject = $this->task(['project_id' => $project->id]);
        $loose = $this->task();

        $this->expectException(InvalidStateException::class);
        $this->service->addDependency($inProject, $loose->id, $this->colleague);
    }

    public function test_completion_notifies_unblocked_assignees(): void
    {
        $first = $this->task(['title' => 'First', 'requires_review' => false]);
        $second = $this->task(['title' => 'Second', 'assignee_ids' => [$this->manager->id]]);
        $third = $this->task(['title' => 'Third', 'assignee_ids' => [$this->manager->id]]);
        $this->service->addDependency($second, $first->id, $this->colleague);
        $this->service->addDependency($third, $first->id, $this->colleague);
        $this->service->addDependency($third, $second->id, $this->colleague);

        $this->service->changeStatus($first, TaskStatus::Completed, $this->staff);

        // Second بقت جاهزة، Third لسه مستنية Second
        $unblocked = EmployeeNotification::where('employee_id', $this->manager->id)->where('type', 'task_unblocked')->get();
        $this->assertCount(1, $unblocked);
        $this->assertSame(route('portal.tasks.show', $second->id, false), $unblocked->first()->url);
    }
}
