<?php

namespace App\Http\Controllers\Admin\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\TaskRecurrenceRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Project;
use App\Models\TaskRecurrence;
use App\Services\Tasks\TaskRecurrenceService;
use App\Support\BranchScopeResolver;

/**
 * المهام المتكررة في لوحة الإدارة — صلاحية task_recurrence_manage.
 * عزل الفروع تلقائي (ScopedToBranch على TaskRecurrence).
 */
class TaskRecurrenceController extends Controller
{
    public function __construct(
        private TaskRecurrenceService $recurrences,
    ) {}

    public function index()
    {
        $rules = TaskRecurrence::query()->with(['project:id,name', 'branch:id,name'])->orderByDesc('is_active')->orderBy('next_run_at')->get();
        $ids = $rules->flatMap(fn ($r) => $r->blueprint['assignee_ids'] ?? [])->unique();

        return view('admin.tasks.recurrences.index', [
            'rules' => $rules,
            'names' => Employee::withoutGlobalScopes()->whereIn('id', $ids)->pluck('name', 'id'),
        ]);
    }

    public function create()
    {
        return view('admin.tasks.recurrences.form', $this->formData(new TaskRecurrence([
            'frequency' => 'weekly', 'interval' => 1, 'run_time' => '08:00', 'duration_days' => 0,
            'starts_on' => today(), 'is_active' => true, 'blueprint' => ['priority' => 'medium', 'requires_review' => true],
        ])));
    }

    public function store(TaskRecurrenceRequest $request)
    {
        $data = $request->forService();
        $data['branch_id'] = BranchScopeResolver::branchId() ?? ($data['branch_id'] ?? null);

        if (! $data['branch_id']) {
            return back()->withInput()->withErrors(['branch_id' => __('task.errors.branch_required')]);
        }

        $this->recurrences->save($this->withProject($data), admin());

        return redirect()->route('admin.tasks.recurrences.index')->with('success', __('task.recurrence.messages.saved'));
    }

    public function edit(TaskRecurrence $recurrence)
    {
        return view('admin.tasks.recurrences.form', $this->formData($recurrence));
    }

    public function update(TaskRecurrenceRequest $request, TaskRecurrence $recurrence)
    {
        $data = $request->forService();
        $data['branch_id'] = $recurrence->branch_id;
        $this->recurrences->save($this->withProject($data), admin(), $recurrence);

        return redirect()->route('admin.tasks.recurrences.index')->with('success', __('task.recurrence.messages.saved'));
    }

    public function toggle(TaskRecurrence $recurrence)
    {
        $this->recurrences->toggle($recurrence);

        return back()->with('success', __('task.recurrence.messages.toggled'));
    }

    public function destroy(TaskRecurrence $recurrence)
    {
        $recurrence->delete();

        return redirect()->route('admin.tasks.recurrences.index')->with('success', __('task.recurrence.messages.deleted'));
    }

    /** المشروع لازم يكون ظاهر للمشرف (عزل الفرع)، وفرع القاعدة = فرع المشروع. */
    private function withProject(array $data): array
    {
        if (! empty($data['project_id'])) {
            $project = Project::findOrFail($data['project_id']);
            $data['branch_id'] = $project->branch_id;
        }

        return $data;
    }

    private function formData(TaskRecurrence $rule): array
    {
        $branchId = BranchScopeResolver::branchId();

        return [
            'rule'      => $rule,
            'projects'  => Project::whereIn('status', ['planning', 'active', 'on_hold'])->orderBy('name')->get(['id', 'name']),
            'branches'  => $branchId || $rule->exists ? collect() : Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employees' => Employee::active()->orderBy('name')->get(['id', 'name', 'employee_code']),
        ];
    }
}
