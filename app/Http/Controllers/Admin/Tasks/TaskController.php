<?php

namespace App\Http\Controllers\Admin\Tasks;

use App\Enums\TaskStatus;
use App\Exceptions\InvalidStateException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\ChecklistItemRequest;
use App\Http\Requests\Tasks\TaskAssigneesRequest;
use App\Http\Requests\Tasks\TaskProgressRequest;
use App\Http\Requests\Tasks\TaskRequest;
use App\Http\Requests\Tasks\TaskStatusRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskChecklistItem;
use App\Models\TaskLabel;
use App\Services\Tasks\TaskFilters;
use App\Services\Tasks\TaskService;
use App\Services\Tasks\TaskViewData;
use App\Support\BranchScopeResolver;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * المهام في لوحة الإدارة.
 *
 * عزل الفروع تلقائي (Global Scope على Task): مشرف الفرع مابيشوفش ولا
 * يفتح مهام فرع تاني حتى بتغيير الرقم في الرابط. القواعد كلها في TaskService.
 */
class TaskController extends Controller
{
    public function __construct(
        private TaskService $tasks,
        private TaskFilters $filters,
    ) {}

    public function index(Request $request)
    {
        $filters = TaskFilters::fromRequest($request);

        if ($request->ajax()) {
            // select صريح: yajra بيعمل join للترتيب بأعمدة العلاقات
            $query = $this->filters->apply(Task::select('tasks.*'), $filters)
                ->with(['project:id,name,code', 'assignees:id,name,avatar', 'labels:id,name,color']);

            if (! $request->has('order')) {
                $this->filters->defaultOrder($query);
            }

            return datatables($query)
                ->addColumn('title_html', fn (Task $t) => view('tasks.partials.title-cell', ['task' => $t, 'url' => route('admin.tasks.show', $t->id)])->render())
                ->addColumn('assignees_html', fn (Task $t) => view('tasks.partials.avatars', ['employees' => $t->assignees])->render())
                ->addColumn('priority_badge', fn (Task $t) => \App\Enums\TaskPriority::badgeFor($t->priority))
                ->addColumn('status_badge', fn (Task $t) => TaskStatus::badgeFor($t->status))
                ->addColumn('progress_html', fn (Task $t) => view('tasks.partials.progress-bar', ['task' => $t, 'compact' => true])->render())
                ->addColumn('due_html', fn (Task $t) => view('tasks.partials.due', ['task' => $t])->render())
                ->addColumn('actions', fn (Task $t) => view('admin.partials.table-actions.crud', [
                    'row' => $t, 'routes' => 'admin.tasks', 'can' => 'task', 'show' => true,
                    'deleteText' => __('global.confirm_delete'),
                ])->render())
                ->rawColumns(['title_html', 'assignees_html', 'priority_badge', 'status_badge', 'progress_html', 'due_html', 'actions'])
                ->make(true);
        }

        return view('admin.tasks.index', [
            'filters'    => $filters,
            'stats'      => $this->stats($filters),
            'projects'   => Project::orderBy('name')->get(['id', 'name', 'code']),
            'employees'  => Employee::active()->orderBy('name')->get(['id', 'name', 'employee_code']),
            'trashCount' => admin_can('task_trash') ? Task::onlyTrashed()->count() : 0,
        ]);
    }

    /**
     * كروت الملخص فوق الجدول — استعلام تجميعي واحد، بنفس الفلاتر
     * (من غير فلتر الحالة عشان الكروت نفسها هي تقسيم بالحالة).
     */
    private function stats(array $filters): array
    {
        unset($filters['status']);

        $open = TaskStatus::openValues();
        $placeholders = implode(',', array_fill(0, count($open), '?'));

        $row = $this->filters->apply(Task::query(), $filters)
            ->toBase()
            ->selectRaw("SUM(CASE WHEN status IN ({$placeholders}) THEN 1 ELSE 0 END) as open", $open)
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress', [TaskStatus::InProgress->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_review', [TaskStatus::InReview->value])
            ->selectRaw("SUM(CASE WHEN status IN ({$placeholders}) AND due_at IS NOT NULL AND due_at < ? THEN 1 ELSE 0 END) as overdue", [...$open, now()])
            ->first();

        return [
            'open'        => (int) ($row->open ?? 0),
            'in_progress' => (int) ($row->in_progress ?? 0),
            'in_review'   => (int) ($row->in_review ?? 0),
            'overdue'     => (int) ($row->overdue ?? 0),
        ];
    }

    public function create(Request $request)
    {
        return view('admin.tasks.form', $this->formData(new Task([
            'project_id' => $request->integer('project_id') ?: null,
            'parent_id'  => $request->integer('parent_id') ?: null,
        ])));
    }

    public function store(TaskRequest $request)
    {
        $data = $request->forService();

        try {
            $this->ensureProjectAccessible($data['project_id'] ?? null);
            // مشرف الفرع: المهمة في فرعه دايماً مهما اتبعت في الفورم
            $data['branch_id'] = BranchScopeResolver::branchId() ?? ($data['branch_id'] ?? null);

            $task = $this->tasks->create($data, admin());
        } catch (InvalidStateException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.tasks.show', $task->id)->with('success', __('task.messages.created'));
    }

    public function show(Task $task, TaskViewData $viewData)
    {
        return view('admin.tasks.show', $viewData->build($task, admin()) + [
            'assignable' => $this->assignable($task->branch_id, $task->project_id),
        ]);
    }

    public function edit(Task $task)
    {
        $task->load('labels:id');

        return view('admin.tasks.form', $this->formData($task));
    }

    public function update(TaskRequest $request, Task $task)
    {
        // المشروع والفرع والمكلّفين مابيتغيروش من فورم التعديل
        $data = collect($request->forService())->except(['project_id', 'branch_id', 'parent_id', 'assignee_ids', 'checklist'])->all();

        return $this->attempt(fn () => $this->tasks->update($task, $data, admin()), 'task.messages.updated', route('admin.tasks.show', $task->id));
    }

    public function destroy(Task $task)
    {
        return $this->attempt(fn () => $this->tasks->delete($task, admin()), 'task.messages.deleted', route('admin.tasks.index'));
    }

    // ========================================================
    //  عمليات من شاشة التفاصيل
    // ========================================================

    public function status(TaskStatusRequest $request, Task $task)
    {
        return $this->attempt(fn () => $this->tasks->changeStatus($task, $request->status(), admin(), $request->validated('note')), 'task.messages.status_changed');
    }

    public function progress(TaskProgressRequest $request, Task $task)
    {
        return $this->attempt(fn () => $this->tasks->updateProgress($task, (int) $request->validated('progress'), admin(), $request->validated('note')), 'task.messages.progress_updated');
    }

    public function assignees(TaskAssigneesRequest $request, Task $task)
    {
        return $this->attempt(fn () => $this->tasks->syncAssignees($task, $request->validated('assignee_ids') ?? [], admin()), 'task.messages.assignees_updated');
    }

    public function storeChecklist(ChecklistItemRequest $request, Task $task)
    {
        return $this->attempt(fn () => $this->tasks->addChecklistItem($task, $request->validated('title'), admin()), 'task.messages.checklist_saved');
    }

    public function toggleChecklist(Task $task, TaskChecklistItem $item)
    {
        return $this->attempt(fn () => $this->tasks->toggleChecklistItem($task, $item, admin()), 'task.messages.checklist_saved');
    }

    public function destroyChecklist(Task $task, TaskChecklistItem $item)
    {
        return $this->attempt(fn () => $this->tasks->deleteChecklistItem($task, $item, admin()), 'task.messages.checklist_removed');
    }

    // ========================================================
    //  سلة المحذوفات
    // ========================================================

    public function trash(Request $request)
    {
        if ($request->ajax()) {
            return datatables(Task::onlyTrashed()->select('tasks.*')->with('project:id,name'))
                ->addColumn('project_name', fn (Task $t) => $t->project?->name ?? '-')
                ->addColumn('status_badge', fn (Task $t) => TaskStatus::badgeFor($t->status))
                ->editColumn('deleted_at', fn (Task $t) => $t->deleted_at?->format('Y-m-d H:i'))
                ->rawColumns(['status_badge'])
                ->make(true);
        }

        return view('admin.tasks.trash');
    }

    public function restore(int $id)
    {
        return $this->attempt(fn () => $this->tasks->restore($id, admin()), 'task.messages.restored');
    }

    public function forceDelete(int $id)
    {
        Task::onlyTrashed()->findOrFail($id)->forceDelete();

        return back()->with('success', __('task.messages.force_deleted'));
    }

    // ========================================================
    //  مساعدات
    // ========================================================

    private function formData(Task $task): array
    {
        $branchId = BranchScopeResolver::branchId();

        return [
            'task'       => $task,
            'projects'   => Project::whereIn('status', ['planning', 'active', 'on_hold'])->orderBy('name')->get(['id', 'name', 'code', 'branch_id']),
            'branches'   => $branchId ? collect() : Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employees'  => $task->exists
                ? collect()
                : Employee::active()->orderBy('name')->get(['id', 'name', 'employee_code', 'branch_id']),
            'labels'     => TaskLabel::availableFor($task->branch_id ?? $branchId)->orderBy('name')->get(['id', 'name', 'color']),
            'parent'     => $task->parent_id ? Task::find($task->parent_id, ['id', 'code', 'title']) : null,
            'formAction' => $task->exists ? route('admin.tasks.update', $task->id) : route('admin.tasks.store'),
            'cancelUrl'  => $task->exists ? route('admin.tasks.show', $task->id) : route('admin.tasks.index'),
        ];
    }

    /** الموظفين المتاحين لنافذة تعديل المكلّفين. */
    private function assignable(int $branchId, ?int $projectId)
    {
        return app(\App\Services\Tasks\TaskAccess::class)
            ->assignableEmployeesQuery($branchId, $projectId)
            ->orderBy('name')
            ->get(['id', 'name', 'employee_code']);
    }

    /** مشرف الفرع مايقدرش يربط مهمة بمشروع فرع تاني. */
    private function ensureProjectAccessible(?int $projectId): void
    {
        if ($projectId && ! Project::whereKey($projectId)->exists()) {
            throw new InvalidStateException(__('task.errors.forbidden'));
        }
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
