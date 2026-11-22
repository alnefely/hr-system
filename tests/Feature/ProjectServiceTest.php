<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Exceptions\InvalidStateException;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Role;
use App\Models\Task;
use App\Services\Projects\ProjectService;
use App\Services\Tasks\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * قواعد المشاريع: الأعضاء ومدير المشروع، القوالب، الحالة، وصلاحيات الأدوار.
 */
class ProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProjectService $service;

    private Branch $branch;

    private Admin $admin;

    private Employee $lead;

    private Employee $dev;

    private Employee $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ProjectService::class);
        $this->branch = Branch::create(['name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $title = JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        foreach (['lead' => '01000000001', 'dev' => '01000000002', 'viewer' => '01000000003'] as $prop => $phone) {
            $this->{$prop} = Employee::create([
                'branch_id' => $this->branch->id, 'job_title_id' => $title->id, 'name' => ucfirst($prop),
                'employee_code' => 'E-' . $phone, 'phone' => $phone, 'status' => 'active', 'basic_salary' => 3000, 'hire_date' => '2024-01-01',
            ]);
        }

        $role = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $this->admin = Admin::create(['name' => 'Root', 'email' => 'root@test.com', 'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id]);
        $this->actingAs($this->admin, 'admin');
    }

    private function project(array $data = []): Project
    {
        return $this->service->create($data + [
            'name' => 'Website', 'branch_id' => $this->branch->id, 'manager_id' => $this->lead->id,
            'members' => [$this->dev->id => 'member', $this->viewer->id => 'viewer'],
        ], $this->admin);
    }

    public function test_manager_is_always_a_manager_member(): void
    {
        $project = $this->project();

        $roles = $project->members()->pluck('project_members.role', 'employees.id')->all();
        $this->assertSame(['manager', 'member', 'viewer'], [$roles[$this->lead->id], $roles[$this->dev->id], $roles[$this->viewer->id]]);

        // تغيير المدير: القديم بيفضل عضو، والجديد بقى مدير
        $this->service->update($project, ['manager_id' => $this->dev->id], $this->admin);
        $roles = $project->members()->pluck('project_members.role', 'employees.id')->all();
        $this->assertSame('manager', $roles[$this->dev->id]);
        $this->assertSame('member', $roles[$this->lead->id]);
    }

    public function test_employee_project_manager_can_update_but_member_cannot(): void
    {
        $project = $this->project();

        $this->service->update($project, ['name' => 'Website v2'], $this->lead);
        $this->assertSame('Website v2', $project->fresh()->name);

        $this->expectException(InvalidStateException::class);
        $this->service->update($project->fresh(), ['name' => 'X'], $this->dev);
    }

    public function test_viewer_cannot_add_tasks_but_member_can(): void
    {
        $project = $this->project();
        $tasks = app(TaskService::class);

        $tasks->create(['title' => 'by member', 'project_id' => $project->id], $this->dev);
        $this->assertSame(1, $project->fresh()->tasks_count);

        $this->expectException(InvalidStateException::class);
        $tasks->create(['title' => 'by viewer', 'project_id' => $project->id], $this->viewer);
    }

    public function test_status_completion_stamps_date(): void
    {
        $project = $this->project();

        $this->service->changeStatus($project, ProjectStatus::Completed, $this->lead);
        $this->assertNotNull($project->fresh()->completed_at);

        $this->service->changeStatus($project->fresh(), ProjectStatus::Active, $this->lead);
        $this->assertNull($project->fresh()->completed_at);
    }

    public function test_invalid_member_is_rejected(): void
    {
        $this->expectException(InvalidStateException::class);
        $this->project(['members' => [99999 => 'member']]);
    }

    public function test_create_from_template_with_offsets(): void
    {
        $template = ProjectTemplate::create(['name' => 'Launch', 'is_active' => true, 'structure' => [
            ['title' => 'Plan', 'priority' => 'high', 'start_offset_days' => 0, 'due_offset_days' => 3, 'checklist' => ['scope', 'budget'],
                'subtasks' => [['title' => 'Kickoff meeting', 'due_offset_days' => 1]]],
            ['title' => 'Build', 'start_offset_days' => 3, 'due_offset_days' => 10],
            ['title' => '  '],
        ]]);

        $project = $this->project(['template_id' => $template->id, 'start_date' => '2026-12-01']);

        $plan = Task::where('project_id', $project->id)->where('title', 'Plan')->firstOrFail();
        $this->assertSame('2026-12-04 17:00', $plan->due_at->format('Y-m-d H:i'));
        $this->assertSame('high', $plan->priority);
        $this->assertSame(2, $plan->checklist_count);
        $this->assertSame(1, $plan->subtasks_count);
        $this->assertSame('2026-12-02', $plan->subtasks()->first()->due_at->toDateString());
        $this->assertSame(2, $project->fresh()->tasks_count);
        $this->assertSame($template->id, $project->template_id);
    }

    public function test_save_project_as_template_round_trip(): void
    {
        $project = $this->project(['start_date' => '2026-12-01']);
        $tasks = app(TaskService::class);
        $root = $tasks->create(['title' => 'Design', 'project_id' => $project->id, 'starts_at' => '2026-12-02 09:00', 'due_at' => '2026-12-06 17:00', 'checklist' => ['wireframe']], $this->admin);
        $tasks->create(['title' => 'Logo', 'parent_id' => $root->id], $this->admin);

        $template = $this->service->saveAsTemplate($project, 'Web template', $this->admin);

        $this->assertSame($this->branch->id, $template->branch_id);
        $this->assertSame('Design', $template->structure[0]['title']);
        $this->assertSame(1, $template->structure[0]['start_offset_days']);
        $this->assertSame(5, $template->structure[0]['due_offset_days']);
        $this->assertSame(['wireframe'], $template->structure[0]['checklist']);
        $this->assertSame('Logo', $template->structure[0]['subtasks'][0]['title']);

        $copy = $this->project(['name' => 'Copy', 'template_id' => $template->id, 'start_date' => '2027-01-01']);
        $this->assertSame('2027-01-06', Task::where('project_id', $copy->id)->whereNull('parent_id')->firstOrFail()->due_at->toDateString());
    }

    public function test_admin_delete_requires_permission_and_employee_cannot_delete(): void
    {
        $project = $this->project();

        try {
            $this->service->delete($project, $this->lead);
            $this->fail('employee deleted a project');
        } catch (InvalidStateException) {
            $this->assertNotSoftDeleted($project);
        }

        $this->service->delete($project, $this->admin);
        $this->assertSoftDeleted($project);
        $this->service->restore($project->id);
        $this->assertNotSoftDeleted($project);
    }
}
