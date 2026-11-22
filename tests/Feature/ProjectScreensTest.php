<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Role;
use App\Models\Task;
use App\Services\Portal\PortalAccessService;
use App\Services\Projects\ProjectService;
use App\Services\Tasks\TaskService;
use App\Support\BranchScopeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * شاشات المشاريع والقوالب في لوحة الإدارة، ومشاريعي في البوابة.
 */
class ProjectScreensTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Admin $admin;

    private Employee $lead;

    private Employee $dev;

    private Employee $outsider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $title = JobTitle::create(['name' => 'Staff', 'is_active' => true]);

        foreach (['lead' => '01000000001', 'dev' => '01000000002', 'outsider' => '01000000003'] as $prop => $phone) {
            $this->{$prop} = Employee::create([
                'branch_id' => $this->branch->id, 'job_title_id' => $title->id, 'name' => ucfirst($prop) . ' Person',
                'employee_code' => 'E-' . $phone, 'phone' => $phone, 'status' => 'active', 'basic_salary' => 3000, 'hire_date' => '2024-01-01',
            ]);
            app(PortalAccessService::class)->enable($this->{$prop}, 'Secret123');
            $this->{$prop}->forceFill(['must_change_password' => false])->save();
        }

        $role = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $this->admin = Admin::create(['name' => 'Root', 'email' => 'root@test.com', 'password' => bcrypt('x'), 'main' => true, 'role_id' => $role->id]);
        $this->actingAs($this->admin, 'admin');
    }

    private function project(array $data = []): Project
    {
        return app(ProjectService::class)->create($data + [
            'name' => 'Website launch', 'branch_id' => $this->branch->id, 'manager_id' => $this->lead->id,
            'members' => [$this->dev->id => 'member'],
        ], $this->admin);
    }

    public function test_admin_index_create_show_edit(): void
    {
        $template = ProjectTemplate::create(['name' => 'Starter', 'is_active' => true, 'structure' => [['title' => 'Kickoff', 'due_offset_days' => 2]]]);

        $this->get(route('admin.projects.create'))->assertOk()->assertSee('Starter');

        $this->post(route('admin.projects.store'), [
            'name' => 'Mobile app', 'branch_id' => $this->branch->id, 'manager_id' => $this->lead->id,
            'status' => 'active', 'priority' => 'high', 'start_date' => '2026-12-01', 'due_date' => '2027-01-31',
            'members' => [$this->dev->id], 'viewers' => [$this->outsider->id], 'template_id' => $template->id, 'color' => '#28c76f',
        ])->assertSessionHas('success');

        $project = Project::where('name', 'Mobile app')->firstOrFail();
        $roles = $project->members()->pluck('project_members.role', 'employees.id')->all();
        $this->assertSame('viewer', $roles[$this->outsider->id]);
        $this->assertSame(1, $project->tasks_count);

        $this->get(route('admin.projects.index'))->assertOk()->assertSee('Mobile app');
        $this->get(route('admin.projects.show', $project->id))->assertOk()->assertSee('Mobile app')->assertSee('Lead Person')->assertSee(__('project.actions.save_as_template'));
        $this->get(route('admin.projects.edit', $project->id))->assertOk();

        $this->put(route('admin.projects.update', $project->id), [
            'name' => 'Mobile app v2', 'manager_id' => $this->dev->id, 'status' => 'completed', 'priority' => 'low',
            'members' => [$this->lead->id],
        ])->assertRedirect(route('admin.projects.show', $project->id));

        $project->refresh();
        $this->assertSame('Mobile app v2', $project->name);
        $this->assertNotNull($project->completed_at);
        $this->assertSame(2, $project->members()->count());
    }

    public function test_color_must_be_hex(): void
    {
        $this->post(route('admin.projects.store'), [
            'name' => 'X', 'branch_id' => $this->branch->id, 'status' => 'active', 'priority' => 'low', 'color' => 'red;background:url(x)',
        ])->assertSessionHasErrors('color');
    }

    public function test_project_tasks_table_and_filters(): void
    {
        $project = $this->project();
        app(TaskService::class)->create(['title' => 'Inside', 'project_id' => $project->id], $this->admin);
        app(TaskService::class)->create(['title' => 'Outside', 'branch_id' => $this->branch->id], $this->admin);

        $json = $this->getJson(route('admin.tasks.index', ['project_id' => $project->id]), ['X-Requested-With' => 'XMLHttpRequest'])->json();
        $this->assertSame(1, $json['recordsFiltered']);
        $this->assertStringContainsString('Inside', $json['data'][0]['title_html']);
    }

    public function test_save_as_template_and_template_crud(): void
    {
        $project = $this->project();
        app(TaskService::class)->create(['title' => 'Design', 'project_id' => $project->id, 'checklist' => ['wire']], $this->admin);

        $this->post(route('admin.projects.save-template', $project->id), ['name' => 'Web flow']);
        $template = ProjectTemplate::where('name', 'Web flow')->firstOrFail();
        $this->assertNull($template->branch_id);

        $this->get(route('admin.project-templates.index'))->assertOk()->assertSee('Web flow');
        $this->get(route('admin.project-templates.edit', $template->id))->assertOk()->assertSee('Design');

        $this->put(route('admin.project-templates.update', $template->id), [
            'name' => 'Web flow 2', 'is_active' => '1',
            'tasks' => [
                ['title' => 'Design', 'priority' => 'high', 'start_offset_days' => 0, 'due_offset_days' => 4, 'checklist' => "wire\nmockup"],
                ['title' => '', 'priority' => 'low'],
            ],
        ])->assertSessionHas('success');

        $template->refresh();
        $this->assertSame('Web flow 2', $template->name);
        $this->assertCount(1, $template->structure);
        $this->assertSame(['wire', 'mockup'], $template->structure[0]['checklist']);

        $this->post(route('admin.project-templates.store'), ['name' => 'Fresh', 'is_active' => '1', 'tasks' => [['title' => 'One']]])->assertSessionHas('success');
        $this->delete(route('admin.project-templates.destroy', $template->id))->assertRedirect(route('admin.project-templates.index'));
        $this->assertSoftDeleted($template);
    }

    public function test_trash_and_restore(): void
    {
        $project = $this->project();

        $this->delete(route('admin.projects.destroy', $project->id))->assertRedirect(route('admin.projects.index'));
        $this->getJson(route('admin.projects.trash'), ['X-Requested-With' => 'XMLHttpRequest'])->assertJsonPath('recordsTotal', 1);
        $this->post(route('admin.projects.restore', $project->id))->assertSessionHas('success');
        $this->assertNotSoftDeleted($project);
    }

    public function test_branch_admin_isolation(): void
    {
        $other = Branch::create(['name' => 'B2', 'code' => 'B2', 'country' => 'Egypt', 'currency' => 'EGP', 'is_active' => true]);
        $foreign = $this->project(['branch_id' => $other->id, 'name' => 'Foreign']);

        $role = Role::create(['name' => 'Branch', 'roles' => all_roles()]);
        $this->actingAs(Admin::create(['name' => 'B', 'email' => 'b@t.com', 'password' => bcrypt('x'), 'main' => false, 'role_id' => $role->id, 'employee_id' => $this->dev->id]), 'admin');
        BranchScopeResolver::forget();

        $this->get(route('admin.projects.show', $foreign->id))->assertNotFound();
        $this->get(route('admin.projects.index'))->assertOk()->assertDontSee('Foreign');

        BranchScopeResolver::forget();
    }

    public function test_portal_projects_by_role(): void
    {
        $project = $this->project();
        app(TaskService::class)->create(['title' => 'Team task', 'project_id' => $project->id], $this->admin);

        // العضو بيشوف المشروع ومهامه، ومايعدّلش
        $this->actingAs($this->dev->fresh(), 'employee');
        $this->get(route('portal.projects.index'))->assertOk()->assertSee('Website launch');
        $this->get(route('portal.projects.show', $project->id))->assertOk()->assertSee('Team task')->assertSee(__('project.actions.add_task'))->assertDontSee(__('project.actions.edit'));
        $this->get(route('portal.projects.edit', $project->id))->assertForbidden();

        // المدير بيعدّل
        $this->actingAs($this->lead->fresh(), 'employee');
        $this->get(route('portal.projects.edit', $project->id))->assertOk();
        $this->put(route('portal.projects.update', $project->id), [
            'name' => 'Website launch v2', 'status' => 'on_hold', 'priority' => 'medium',
            'members' => [$this->dev->id], 'viewers' => [$this->outsider->id], 'manager_id' => $this->outsider->id,
        ])->assertRedirect(route('portal.projects.show', $project->id));
        $project->refresh();
        $this->assertSame('Website launch v2', $project->name);
        $this->assertSame($this->lead->id, $project->manager_id);

        // المشاهد يشوف بس ومايضيفش مهام
        $this->actingAs($this->outsider->fresh(), 'employee');
        $this->get(route('portal.projects.show', $project->id))->assertOk()->assertDontSee(__('project.actions.add_task'));
        $this->post(route('portal.tasks.store'), [
            'title' => 'sneaky', 'project_id' => $project->id, 'priority' => 'low', 'progress_source' => 'manual', 'progress_update_interval' => 'none',
        ])->assertSessionHas('error');
        $this->assertFalse(Task::where('title', 'sneaky')->exists());
    }

    public function test_portal_outsider_cannot_open_project(): void
    {
        $project = $this->project();

        $this->actingAs($this->outsider->fresh(), 'employee');
        $this->get(route('portal.projects.show', $project->id))->assertNotFound();
    }
}
