<?php

namespace App\Http\Controllers\Admin\Projects;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Exceptions\InvalidStateException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\ProjectRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Task;
use App\Services\Projects\ProjectAccess;
use App\Services\Projects\ProjectService;
use App\Support\BranchScopeResolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * المشاريع في لوحة الإدارة.
 *
 * القائمة كروت (مش جدول) لأن أهم معلومة هي نسبة الإنجاز والفريق؛
 * العدادات مخزّنة على المشروع فالكروت مابتعملش استعلامات إضافية.
 */
class ProjectController extends Controller
{
    private const PER_PAGE = 12;

    public function __construct(
        private ProjectService $projects,
        private ProjectAccess $access,
    ) {}

    public function index(Request $request)
    {
        $status = $request->query('status', 'ongoing');

        $projects = Project::query()
            ->with(['manager:id,name', 'members:id,name'])
            ->when($request->filled('q'), fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('name', 'like', '%' . mb_substr($request->query('q'), 0, 100) . '%')
                ->orWhere('code', 'like', '%' . mb_substr($request->query('q'), 0, 100) . '%')))
            ->when($request->filled('manager_id'), fn (Builder $q) => $q->where('manager_id', $request->integer('manager_id')))
            ->when($status === 'ongoing', fn (Builder $q) => $q->whereIn('status', [ProjectStatus::Planning->value, ProjectStatus::Active->value, ProjectStatus::OnHold->value]))
            ->when(ProjectStatus::tryFrom((string) $status), fn (Builder $q) => $q->where('status', $status))
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'planning' THEN 1 WHEN 'on_hold' THEN 2 ELSE 3 END")
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.projects.index', [
            'projects'   => $projects,
            'status'     => $status,
            'managers'   => Employee::active()->whereIn('id', Project::select('manager_id')->whereNotNull('manager_id'))->orderBy('name')->get(['id', 'name']),
            'trashCount' => admin_can('project_trash') ? Project::onlyTrashed()->count() : 0,
        ]);
    }

    public function create()
    {
        return view('admin.projects.form', $this->formData(new Project([
            'status' => ProjectStatus::Active->value, 'priority' => 'medium', 'start_date' => today(),
        ])));
    }

    public function store(ProjectRequest $request)
    {
        $data = $request->forService();
        $data['branch_id'] = BranchScopeResolver::branchId() ?? ($data['branch_id'] ?? null);

        if (! $data['branch_id']) {
            return back()->withInput()->withErrors(['branch_id' => __('validation.required', ['attribute' => __('project.fields.branch')])]);
        }

        try {
            $project = $this->projects->create($data, admin());
        } catch (InvalidStateException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.projects.show', $project->id)->with('success', __('project.messages.created'));
    }

    public function show(Project $project)
    {
        $project->load(['manager:id,name', 'branch:id,name', 'members' => fn ($q) => $q->orderBy('name'), 'template:id,name']);

        return view('admin.projects.show', [
            'project'   => $project,
            'stats'     => $this->taskStats($project),
            'abilities' => $this->access->abilities($project, admin()),
        ]);
    }

    /** توزيع مهام المشروع على الحالات — استعلام تجميعي واحد. */
    private function taskStats(Project $project): array
    {
        $row = Task::query()->where('project_id', $project->id)->rootOnly()->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed', [TaskStatus::Completed->value])
            ->selectRaw('SUM(CASE WHEN status IN (?, ?, ?) THEN 1 ELSE 0 END) as open', TaskStatus::openValues())
            ->selectRaw('SUM(CASE WHEN status IN (?, ?, ?) AND due_at IS NOT NULL AND due_at < ? THEN 1 ELSE 0 END) as overdue', [...TaskStatus::openValues(), now()])
            ->first();

        return collect(['total', 'completed', 'open', 'overdue'])->mapWithKeys(fn ($k) => [$k => (int) ($row->{$k} ?? 0)])->all();
    }

    public function edit(Project $project)
    {
        $project->load('members:id');

        return view('admin.projects.form', $this->formData($project));
    }

    public function update(ProjectRequest $request, Project $project)
    {
        $data = collect($request->forService())->except(['branch_id', 'template_id'])->all();

        return $this->attempt(fn () => $this->projects->update($project, $data, admin()), 'project.messages.updated', route('admin.projects.show', $project->id));
    }

    public function destroy(Project $project)
    {
        return $this->attempt(fn () => $this->projects->delete($project, admin()), 'project.messages.deleted', route('admin.projects.index'));
    }

    public function saveTemplate(Request $request, Project $project)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:191']]);

        // مشرف الكل ← قالب عام، مشرف الفرع ← قالب لفرعه
        $template = $this->projects->saveAsTemplate($project, $data['name'], admin(), BranchScopeResolver::branchId() === null);

        return redirect()->route('admin.project-templates.edit', $template->id)->with('success', __('project.messages.template_saved'));
    }

    // ========================================================
    //  سلة المحذوفات
    // ========================================================

    public function trash(Request $request)
    {
        if ($request->ajax()) {
            return datatables(Project::onlyTrashed()->select('projects.*')->with('manager:id,name'))
                ->addColumn('manager_name', fn (Project $p) => $p->manager?->name ?? '-')
                ->addColumn('status_badge', fn (Project $p) => ProjectStatus::badgeFor($p->status))
                ->editColumn('deleted_at', fn (Project $p) => $p->deleted_at?->format('Y-m-d H:i'))
                ->rawColumns(['status_badge'])
                ->make(true);
        }

        return view('admin.projects.trash');
    }

    public function restore(int $id)
    {
        $this->projects->restore($id);

        return back()->with('success', __('project.messages.restored'));
    }

    public function forceDelete(int $id)
    {
        Project::onlyTrashed()->findOrFail($id)->forceDelete();

        return back()->with('success', __('project.messages.force_deleted'));
    }

    // ========================================================
    //  مساعدات
    // ========================================================

    private function formData(Project $project): array
    {
        $branchId = BranchScopeResolver::branchId();

        return [
            'project'    => $project,
            'branches'   => $branchId ? collect() : Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            // الأعضاء ممكن يكونوا من أي فرع — من غير عزل
            'employees'  => Employee::withoutGlobalScopes()->whereNull('deleted_at')->where('status', '!=', 'terminated')
                ->with('branch:id,name')->orderBy('name')->get(['id', 'name', 'employee_code', 'branch_id']),
            'templates'  => $project->exists ? collect() : ProjectTemplate::availableFor($branchId)->orderBy('name')->get(['id', 'name', 'structure']),
            'formAction' => $project->exists ? route('admin.projects.update', $project->id) : route('admin.projects.store'),
            'cancelUrl'  => $project->exists ? route('admin.projects.show', $project->id) : route('admin.projects.index'),
            'canManager' => true,
        ];
    }

    private function attempt(Closure $action, string $successKey, ?string $redirect = null): RedirectResponse
    {
        try {
            $action();
        } catch (InvalidStateException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return ($redirect ? redirect($redirect) : back())->with('success', __($successKey));
    }
}
