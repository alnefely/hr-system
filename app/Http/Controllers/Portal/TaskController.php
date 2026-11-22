<?php

namespace App\Http\Controllers\Portal;

use App\Enums\TaskAssigneeStatus;
use App\Enums\TaskStatus;
use App\Exceptions\InvalidStateException;
use App\Http\Requests\Tasks\ChecklistItemRequest;
use App\Http\Requests\Tasks\TaskAssigneesRequest;
use App\Http\Requests\Tasks\TaskProgressRequest;
use App\Http\Requests\Tasks\TaskRequest;
use App\Http\Requests\Tasks\TaskStatusRequest;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskChecklistItem;
use App\Models\TaskLabel;
use App\Services\Tasks\TaskAccess;
use App\Services\Tasks\TaskFilters;
use App\Services\Tasks\TaskService;
use App\Services\Tasks\TaskViewData;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * مهامي في بوابة الموظف.
 *
 * كل استعلام بيبدأ من Task::visibleToEmployee() — الموظف مايقدرش يفتح
 * مهمة مالوش علاقة بيها حتى لو غيّر الرقم في الرابط (404).
 * القواعد (مين يعدّل، مين يعتمد، مين يتكلّف) في TaskService/TaskAccess.
 */
class TaskController extends PortalController
{
    private const TABS = ['assigned', 'created', 'team', 'watching'];

    private const PER_PAGE = 12;

    public function __construct(
        private TaskService $tasks,
        private TaskFilters $filters,
        private TaskAccess $access,
    ) {}

    public function index(Request $request)
    {
        $me = $this->employee();
        $hasTeam = $me->subordinates()->exists();
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'assigned';

        if ($tab === 'team' && ! $hasTeam) {
            $tab = 'assigned';
        }

        $filters = TaskFilters::fromRequest($request);
        // افتراضياً المفتوح بس — المقفول بفلتر الحالة
        $filters['status'] ??= TaskFilters::STATUS_OPEN;
        unset($filters['employee_id']);

        $query = $this->filters->apply($this->tabQuery($tab, $me), $filters)
            ->with(['project:id,name,code', 'assignees:id,name,avatar', 'labels:id,name,color']);

        $tasks = $this->filters->defaultOrder($query)->paginate(self::PER_PAGE)->withQueryString();

        return view('portal.tasks.index', [
            'tasks'    => $tasks,
            'tab'      => $tab,
            'hasTeam'  => $hasTeam,
            'filters'  => $filters,
            'stats'    => $this->stats($me),
            'projects' => Project::withoutGlobalScopes()->forEmployee($me->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** مصدر كل تبويب — استعلامات فرعية على جداول وسيطة مفهرسة. */
    private function tabQuery(string $tab, Employee $me): Builder
    {
        $query = Task::query()->select('tasks.*');

        return match ($tab) {
            'created'  => $query->where('creator_type', Employee::class)->where('creator_id', $me->id),
            'team'     => $query->whereIn('tasks.id', fn ($s) => $s->select('task_id')->from('task_assignees')
                ->where('status', TaskAssigneeStatus::Active->value)
                ->whereIn('employee_id', fn ($e) => $e->select('id')->from('employees')->where('manager_id', $me->id))),
            'watching' => $query->whereIn('tasks.id', fn ($s) => $s->select('task_id')->from('task_watchers')
                ->where('watcher_type', Employee::class)->where('watcher_id', $me->id)),
            default    => $query->assignedTo($me->id),
        };
    }

    /** ملخص مهامي المكلّف بيها — استعلام تجميعي واحد. */
    private function stats(Employee $me): array
    {
        $row = Task::query()->assignedTo($me->id)->rootOnly()->toBase()
            ->selectRaw('SUM(CASE WHEN status IN (?, ?, ?) THEN 1 ELSE 0 END) as open', TaskStatus::openValues())
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress', [TaskStatus::InProgress->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_review', [TaskStatus::InReview->value])
            ->selectRaw('SUM(CASE WHEN status IN (?, ?, ?) AND due_at IS NOT NULL AND due_at < ? THEN 1 ELSE 0 END) as overdue', [...TaskStatus::openValues(), now()])
            ->first();

        return collect(['open', 'in_progress', 'in_review', 'overdue'])
            ->mapWithKeys(fn ($k) => [$k => (int) ($row->{$k} ?? 0)])
            ->all();
    }

    public function create(Request $request)
    {
        $parent = null;

        if ($request->filled('parent_id')) {
            $parent = $this->visibleTask($request->integer('parent_id'));
            abort_unless($this->access->canWork($parent, $this->employee()), 403);
        }

        return view('portal.tasks.form', $this->formData(new Task([
            'project_id' => $request->integer('project_id') ?: null,
            'parent_id'  => $parent?->id,
        ]), $parent));
    }

    public function store(TaskRequest $request)
    {
        $me = $this->employee();
        // فرع المهمة = فرع الموظف (أو فرع المشروع — الخدمة بتحدده)
        $data = ['branch_id' => $me->branch_id] + $request->forService();

        try {
            $task = $this->tasks->create($data, $me);
        } catch (InvalidStateException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('portal.tasks.show', $task->id)->with('success', __('task.messages.created'));
    }

    public function show(int $task, TaskViewData $viewData)
    {
        $task = $this->visibleTask($task);

        return view('portal.tasks.show', $viewData->build($task, $this->employee()) + [
            'assignable' => $this->access->assignableEmployeesQuery($task->branch_id, $task->project_id)
                ->orderBy('name')->get(['id', 'name', 'employee_code']),
        ]);
    }

    public function edit(int $task)
    {
        $task = $this->visibleTask($task);
        abort_unless($this->access->canUpdate($task, $this->employee()), 403);
        $task->load('labels:id');

        return view('portal.tasks.form', $this->formData($task));
    }

    public function update(TaskRequest $request, int $task)
    {
        $task = $this->visibleTask($task);
        $data = collect($request->forService())->except(['project_id', 'branch_id', 'parent_id', 'assignee_ids', 'checklist'])->all();

        return $this->attempt(fn () => $this->tasks->update($task, $data, $this->employee()), 'task.messages.updated', route('portal.tasks.show', $task->id));
    }

    public function destroy(int $task)
    {
        $task = $this->visibleTask($task);

        return $this->attempt(fn () => $this->tasks->delete($task, $this->employee()), 'task.messages.deleted', route('portal.tasks.index'));
    }

    public function status(TaskStatusRequest $request, int $task)
    {
        $task = $this->visibleTask($task);

        return $this->attempt(fn () => $this->tasks->changeStatus($task, $request->status(), $this->employee(), $request->validated('note')), 'task.messages.status_changed');
    }

    public function progress(TaskProgressRequest $request, int $task)
    {
        $task = $this->visibleTask($task);

        return $this->attempt(fn () => $this->tasks->updateProgress($task, (int) $request->validated('progress'), $this->employee(), $request->validated('note')), 'task.messages.progress_updated');
    }

    public function assignees(TaskAssigneesRequest $request, int $task)
    {
        $task = $this->visibleTask($task);

        return $this->attempt(fn () => $this->tasks->syncAssignees($task, $request->validated('assignee_ids') ?? [], $this->employee()), 'task.messages.assignees_updated');
    }

    public function decline(Request $request, int $task)
    {
        $task = $this->visibleTask($task);
        $reason = $request->validate(['reason' => ['required', 'string', 'max:1000']])['reason'];

        return $this->attempt(fn () => $this->tasks->decline($task, $this->employee(), $reason), 'task.messages.declined', route('portal.tasks.index'));
    }

    public function storeChecklist(ChecklistItemRequest $request, int $task)
    {
        $task = $this->visibleTask($task);

        return $this->attempt(fn () => $this->tasks->addChecklistItem($task, $request->validated('title'), $this->employee()), 'task.messages.checklist_saved');
    }

    public function toggleChecklist(int $task, int $item)
    {
        $task = $this->visibleTask($task);
        $item = $task->checklistItems()->findOrFail($item);

        return $this->attempt(fn () => $this->tasks->toggleChecklistItem($task, $item, $this->employee()), 'task.messages.checklist_saved');
    }

    public function destroyChecklist(int $task, int $item)
    {
        $task = $this->visibleTask($task);
        $item = $task->checklistItems()->findOrFail($item);

        return $this->attempt(fn () => $this->tasks->deleteChecklistItem($task, $item, $this->employee()), 'task.messages.checklist_removed');
    }

    // ========================================================
    //  مساعدات
    // ========================================================

    /** المهمة لو الموظف يحق له يشوفها، وإلا 404 (مانكشفش إنها موجودة). */
    private function visibleTask(int $id): Task
    {
        return Task::visibleToEmployee($this->employee())->whereKey($id)->firstOrFail();
    }

    private function formData(Task $task, ?Task $parent = null): array
    {
        $me = $this->employee();
        $parent ??= $task->parent_id ? Task::find($task->parent_id, ['id', 'code', 'title']) : null;

        return [
            'task'       => $task,
            'parent'     => $parent,
            'projects'   => Project::withoutGlobalScopes()->forEmployee($me->id)
                ->whereIn('status', ['planning', 'active', 'on_hold'])->orderBy('name')->get(['id', 'name']),
            'branches'   => collect(),
            'employees'  => $task->exists ? collect() : $this->access
                ->assignableEmployeesQuery($parent?->branch_id ?? $me->branch_id, $parent?->project_id)
                ->orderBy('name')->get(['id', 'name', 'employee_code']),
            'labels'     => TaskLabel::availableFor($task->branch_id ?? $me->branch_id)->orderBy('name')->get(['id', 'name', 'color']),
            'formAction' => $task->exists ? route('portal.tasks.update', $task->id) : route('portal.tasks.store'),
            'cancelUrl'  => $task->exists ? route('portal.tasks.show', $task->id) : route('portal.tasks.index'),
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
