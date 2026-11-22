<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ProjectStatus;
use App\Exceptions\InvalidStateException;
use App\Http\Requests\Projects\ProjectRequest;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Services\Projects\ProjectAccess;
use App\Services\Projects\ProjectService;
use App\Services\Tasks\TaskFilters;
use Illuminate\Http\Request;

/**
 * مشاريعي في البوابة: المشاريع اللي الموظف مديرها أو عضو أو مشاهد فيها.
 *
 * مدير المشروع بيعدّل بياناته وحالته وأعضاءه من هنا (من غير ما يغيّر
 * المدير نفسه — ده من لوحة الإدارة).
 */
class ProjectController extends PortalController
{
    private const PER_PAGE = 12;

    public function __construct(
        private ProjectService $projects,
        private ProjectAccess $access,
        private TaskFilters $filters,
    ) {}

    public function index(Request $request)
    {
        $me = $this->employee();
        $ongoing = $request->query('status', 'ongoing') === 'ongoing';

        $projects = $this->mine()
            ->with(['manager:id,name', 'members:id,name'])
            ->when($ongoing, fn ($q) => $q->whereIn('status', [ProjectStatus::Planning->value, ProjectStatus::Active->value, ProjectStatus::OnHold->value]))
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'planning' THEN 1 WHEN 'on_hold' THEN 2 ELSE 3 END")
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('portal.projects.index', ['projects' => $projects, 'ongoing' => $ongoing]);
    }

    public function show(Request $request, int $project)
    {
        $project = $this->visible($project);
        $project->load(['manager:id,name', 'members' => fn ($q) => $q->orderBy('name')]);
        $me = $this->employee();

        $filters = TaskFilters::fromRequest($request) + ['status' => TaskFilters::STATUS_OPEN];
        $filters['project_id'] = $project->id;
        unset($filters['employee_id']);

        // أعضاء المشروع بيشوفوا كل مهامه (Task::visibleToEmployee بيشمل المشروع)
        $tasks = $this->filters->defaultOrder(
            $this->filters->apply(Task::visibleToEmployee($me)->select('tasks.*'), $filters)
                ->with(['assignees:id,name,avatar', 'labels:id,name,color'])
        )->paginate(self::PER_PAGE)->withQueryString();

        return view('portal.projects.show', [
            'project'   => $project,
            'tasks'     => $tasks,
            'filters'   => $filters,
            'role'      => $this->access->roleOf($project, $me),
            'abilities' => $this->access->abilities($project, $me),
        ]);
    }

    public function edit(int $project)
    {
        $project = $this->visible($project);
        abort_unless($this->access->canUpdate($project, $this->employee()), 403);
        $project->load('members:id');

        return view('portal.projects.form', [
            'project'    => $project,
            'branches'   => collect(),
            'templates'  => collect(),
            'employees'  => Employee::withoutGlobalScopes()->whereNull('deleted_at')->where('status', '!=', 'terminated')
                ->with('branch:id,name')->orderBy('name')->get(['id', 'name', 'employee_code', 'branch_id']),
            'formAction' => route('portal.projects.update', $project->id),
            'cancelUrl'  => route('portal.projects.show', $project->id),
            'canManager' => false,
        ]);
    }

    public function update(ProjectRequest $request, int $project)
    {
        $project = $this->visible($project);
        // مدير المشروع مايغيّرش المدير ولا الفرع
        $data = collect($request->forService())->except(['manager_id', 'branch_id', 'template_id'])->all();

        try {
            $this->projects->update($project, $data, $this->employee());
        } catch (InvalidStateException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('portal.projects.show', $project->id)->with('success', __('project.messages.updated'));
    }

    /** مشاريع الموظف — من غير عزل الفروع (العضو ممكن يكون من فرع تاني). */
    private function mine()
    {
        return Project::withoutGlobalScopes()->whereNull('deleted_at')->forEmployee($this->employee()->id);
    }

    private function visible(int $id): Project
    {
        return $this->mine()->whereKey($id)->firstOrFail();
    }
}
