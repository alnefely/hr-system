<?php

namespace App\Services\Tasks;

use App\Enums\TaskAssigneeStatus;
use App\Enums\TaskProgressSource;
use App\Enums\TaskReviewStatus;
use App\Enums\TaskStatus;
use App\Events\Tasks\TaskAssigned;
use App\Events\Tasks\TaskAssignmentDeclined;
use App\Events\Tasks\TaskCreated;
use App\Events\Tasks\TaskProgressUpdated;
use App\Events\Tasks\TaskStatusChanged;
use App\Events\Tasks\TaskUpdated;
use App\Exceptions\InvalidStateException;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAssignee;
use App\Models\TaskChecklistItem;
use App\Models\TaskProgressUpdate;
use App\Models\TaskReview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * كل العمليات على المهام — لوحة الإدارة والبوابة بيستخدموا نفس الخدمة.
 *
 * - الصلاحية بتتفحص هنا (TaskAccess) مش في الكنترولر بس، فمفيش طريق
 *   يعدّي القاعدة.
 * - أي عملية مش مسموحة بترمي InvalidStateException برسالة مترجمة.
 * - كل تغيير بيتسجّل في الخط الزمني (task_activities)، والعدادات
 *   المخزّنة بتتحدّث (المهمة الأم + المشروع).
 * - الأحداث (events) بتتبعت بعد المعاملة — للإشعارات والبث اللحظي.
 *
 * $actor = المشرف أو الموظف اللي بيعمل العملية.
 */
class TaskService
{
    public function __construct(
        private TaskAccess $access,
    ) {}

    // ========================================================
    //  إنشاء وتعديل
    // ========================================================

    /**
     * @param  array{title: string, description?: ?string, branch_id?: ?int, project_id?: ?int, parent_id?: ?int,
     *               priority?: string, starts_at?: ?string, due_at?: ?string, estimated_minutes?: ?int,
     *               requires_review?: bool, progress_source?: string, progress_update_interval?: string,
     *               progress_update_every_days?: ?int, assignee_ids?: array<int>, checklist?: array<string>,
     *               label_ids?: array<int>}  $data
     */
    public function create(array $data, Model $actor): Task
    {
        $parent = null;

        if (! empty($data['parent_id'])) {
            $parent = Task::withoutGlobalScopes()->findOrFail($data['parent_id']);

            if (! $this->access->canWork($parent, $actor)) {
                throw new InvalidStateException(__('task.errors.forbidden'));
            }

            $data['branch_id'] = $parent->branch_id;
            $data['project_id'] = $parent->project_id;
        }

        $project = ! empty($data['project_id'])
            ? Project::withoutGlobalScopes()->findOrFail($data['project_id'])
            : null;

        // الموظف يضيف مهام في مشروع هو مديره أو عضو فيه (مش مشاهد)
        if ($project && $actor instanceof \App\Models\Employee && ! $parent
            && ! app(\App\Services\Projects\ProjectAccess::class)->canAddTasks($project, $actor)) {
            throw new InvalidStateException(__('task.errors.forbidden'));
        }

        $data['branch_id'] = $project?->branch_id ?? ($data['branch_id'] ?? null);

        if (! $data['branch_id']) {
            throw new InvalidStateException(__('task.errors.branch_required'));
        }

        $assigneeIds = $this->validAssignees($data['assignee_ids'] ?? [], (int) $data['branch_id'], $project?->id);

        $task = DB::transaction(function () use ($data, $actor, $assigneeIds, $parent) {
            $task = Task::create($this->taskAttributes($data) + [
                'branch_id'    => $data['branch_id'],
                'project_id'   => $data['project_id'] ?? null,
                'parent_id'    => $parent?->id,
                'creator_type' => $actor::class,
                'creator_id'   => $actor->getKey(),
            ]);

            $task->next_progress_update_at = $task->nextProgressUpdateDate($task->starts_at?->isFuture() ? $task->starts_at : now());

            foreach (array_values(array_filter($data['checklist'] ?? [], fn ($t) => trim((string) $t) !== '')) as $i => $title) {
                $task->checklistItems()->create(['title' => trim($title), 'position' => $i]);
            }

            if (! empty($data['label_ids'])) {
                $task->labels()->sync($data['label_ids']);
            }

            $this->record($task, $actor, 'created');
            $this->attachAssignees($task, $assigneeIds, $actor);

            $task->refreshCounters();
            $this->refreshParents($task);

            return $task;
        });

        TaskCreated::dispatch($task, $actor);

        if ($assigneeIds !== []) {
            TaskAssigned::dispatch($task, $assigneeIds, $actor);
        }

        return $task;
    }

    public function update(Task $task, array $data, Model $actor): Task
    {
        $this->authorize($this->access->canUpdate($task, $actor));

        $attributes = $this->taskAttributes($data);

        // تغيير طريقة الحساب أو الفترة بيعيد جدولة التذكير الجاي
        $intervalChanged = isset($attributes['progress_update_interval'])
            && ($attributes['progress_update_interval'] !== $task->progress_update_interval
                || ($attributes['progress_update_every_days'] ?? null) !== $task->progress_update_every_days);

        DB::transaction(function () use ($task, $attributes, $data, $actor, $intervalChanged) {
            $task->fill($attributes);
            $changes = array_keys($task->getDirty());

            if ($intervalChanged) {
                $task->next_progress_update_at = $task->nextProgressUpdateDate($task->last_progress_update_at ?? now());
            }

            $task->last_activity_at = now();
            $task->save();

            if (array_key_exists('label_ids', $data)) {
                $task->labels()->sync($data['label_ids'] ?? []);
            }

            if ($changes !== []) {
                $this->record($task, $actor, 'updated', ['fields' => $changes]);
            }

            // تغيير مصدر النسبة لتلقائي بيعيد حسابها فوراً
            $task->refreshCounters();
            $this->refreshParents($task);
        });

        TaskUpdated::dispatch($task, $actor);

        return $task;
    }

    // ========================================================
    //  الحالة
    // ========================================================

    public function changeStatus(Task $task, TaskStatus $to, Model $actor, ?string $note = null): Task
    {
        $this->authorize($this->access->canChangeStatus($task, $actor));

        $from = $task->statusEnum();

        if ($from === $to) {
            return $task;
        }

        if (! $from->canTransitionTo($to)) {
            throw new InvalidStateException(__('task.errors.invalid_transition', [
                'from' => $from->label(), 'to' => $to->label(),
            ]));
        }

        // الإلغاء وإعادة الفتح لأصحاب القرار بس
        if (in_array($to, [TaskStatus::Cancelled], true) || $from->isClosed()) {
            $this->authorize($this->access->canUpdate($task, $actor) || $this->access->canApprove($task, $actor));
        }

        if ($to === TaskStatus::Completed) {
            $this->ensureCanComplete($task, $actor);
        }

        $note = $note !== null && trim($note) !== '' ? trim($note) : null;
        $reviewer = null;

        if ($to === TaskStatus::InReview) {
            $task->loadMissing(['creator', 'assignees']);
            $reviewer = $task->resolveReviewer();

            // مفيش حد يراجع (موظف عمل مهمة لنفسه ومالوش مدير) ← يقفلها مباشرة
            if ($reviewer === null) {
                throw new InvalidStateException(__('task.errors.no_reviewer'));
            }
        }

        // إرجاع مهمة من المراجعة: لصاحب القرار بس، وبسبب واضح للموظف
        if ($from === TaskStatus::InReview && $to === TaskStatus::InProgress) {
            $this->authorize($this->access->canApprove($task, $actor));

            if ($note === null) {
                throw new InvalidStateException(__('task.errors.return_reason_required'));
            }
        }

        if (in_array($to, [TaskStatus::InProgress, TaskStatus::InReview, TaskStatus::Completed], true) && $task->isBlocked()) {
            throw new InvalidStateException(__('task.errors.blocked'));
        }

        DB::transaction(function () use ($task, $from, $to, $actor, $note, $reviewer) {
            $task->status = $to->value;
            $task->last_activity_at = now();

            match ($to) {
                TaskStatus::InProgress => $task->started_at ??= now(),
                TaskStatus::Completed  => $task->forceFill(['completed_at' => now(), 'cancelled_at' => null, 'progress' => 100]),
                TaskStatus::Cancelled  => $task->forceFill(['cancelled_at' => now(), 'completed_at' => null]),
                default                => null,
            };

            if ($to->isOpen()) {
                $task->completed_at = null;
                $task->cancelled_at = null;
            }

            $task->save();

            $event = $this->recordReview($task, $from, $to, $actor, $note, $reviewer);

            $this->record($task, $actor, $event ?? 'status_changed', array_filter([
                'from' => $from->value, 'to' => $to->value, 'note' => $note,
            ]));

            $this->refreshParents($task);
        });

        TaskStatusChanged::dispatch($task, $from->value, $to->value, $actor);

        return $task;
    }

    /**
     * سجل المراجعات: التسليم بيفتح مراجعة معلّقة للمراجع، والقرار
     * (اعتماد / إرجاع / إلغاء) بيقفلها باسم اللي قرر فعلاً.
     *
     * @return string|null اسم الحدث في الخط الزمني (null = تغيير حالة عادي)
     */
    private function recordReview(Task $task, TaskStatus $from, TaskStatus $to, Model $actor, ?string $note, ?Model $reviewer): ?string
    {
        if ($to === TaskStatus::InReview) {
            // أي مراجعة قديمة معلّقة (المفروض مفيش) بتتقفل الأول
            $task->reviews()->where('status', TaskReviewStatus::Pending->value)
                ->update(['status' => TaskReviewStatus::Rejected->value, 'decided_at' => now()]);

            TaskReview::create([
                'task_id'         => $task->id,
                'submitter_type'  => $actor::class,
                'submitter_id'    => $actor->getKey(),
                'submission_note' => $note,
                'reviewer_type'   => $reviewer::class,
                'reviewer_id'     => $reviewer->getKey(),
                'status'          => TaskReviewStatus::Pending->value,
            ]);

            return 'review_submitted';
        }

        if ($from !== TaskStatus::InReview) {
            return null;
        }

        $decision = $to === TaskStatus::Completed ? TaskReviewStatus::Approved : TaskReviewStatus::Rejected;

        $task->reviews()->where('status', TaskReviewStatus::Pending->value)->update([
            'status'        => $decision->value,
            'decision_note' => $note,
            'decided_at'    => now(),
            'reviewer_type' => $actor::class,
            'reviewer_id'   => $actor->getKey(),
        ]);

        return match ($to) {
            TaskStatus::Completed  => 'review_approved',
            TaskStatus::InProgress => 'review_returned',
            default                => null,
        };
    }

    /**
     * المكلّف مايقدرش يقفل مهمة محتاجة مراجعة بنفسه — يبعتها للمراجعة.
     * اللي أنشأها/مدير المشروع/المدير المباشر/المشرف المخوّل يقفلوها عادي.
     * لو مفيش مراجع أصلاً (موظف عمل مهمة لنفسه ومالوش مدير) ← تتقفل عادي.
     */
    private function ensureCanComplete(Task $task, Model $actor): void
    {
        if (! $task->requires_review || $this->access->canApprove($task, $actor)) {
            return;
        }

        $task->loadMissing(['creator', 'assignees']);

        if ($task->resolveReviewer() !== null) {
            throw new InvalidStateException(__('task.errors.needs_review'));
        }
    }

    // ========================================================
    //  نسبة الإنجاز
    // ========================================================

    public function updateProgress(Task $task, int $progress, Model $actor, ?string $note = null): TaskProgressUpdate
    {
        $this->authorize($this->access->canWork($task, $actor));

        if (! $task->isOpen()) {
            throw new InvalidStateException(__('task.errors.closed'));
        }

        if ($task->progress_source !== TaskProgressSource::Manual->value) {
            throw new InvalidStateException(__('task.errors.progress_automatic'));
        }

        $progress = max(0, min(100, $progress));
        $previous = $task->progress;

        $update = DB::transaction(function () use ($task, $progress, $previous, $actor, $note) {
            $update = TaskProgressUpdate::create([
                'task_id'           => $task->id,
                'author_type'       => $actor::class,
                'author_id'         => $actor->getKey(),
                'previous_progress' => $previous,
                'progress'          => $progress,
                'expected_progress' => $task->expectedProgress(),
                'note'              => $note,
            ]);

            $task->forceFill([
                'progress'                => $progress,
                'last_progress_update_at' => now(),
                'next_progress_update_at' => $task->nextProgressUpdateDate(now()),
                'last_activity_at'        => now(),
            ]);

            // أول تحديث نسبة = الشغل بدأ
            if ($task->status === TaskStatus::New->value && $progress > 0 && ! $task->isBlocked()) {
                $task->status = TaskStatus::InProgress->value;
                $task->started_at ??= now();
            }

            $task->save();

            $this->record($task, $actor, 'progress_updated', array_filter([
                'from' => $previous, 'to' => $progress, 'note' => $note,
            ], fn ($v) => $v !== null));

            $this->refreshParents($task);

            return $update;
        });

        TaskProgressUpdated::dispatch($task, $previous, $progress, $actor);

        return $update;
    }

    // ========================================================
    //  التكليف
    // ========================================================

    /** المكلّفين = القائمة دي بالظبط (يضيف الجديد ويشيل اللي مش فيها). */
    public function syncAssignees(Task $task, array $employeeIds, Model $actor): void
    {
        $this->authorize($this->access->canAssign($task, $actor));

        $ids = $this->validAssignees($employeeIds, $task->branch_id, $task->project_id);

        $added = DB::transaction(function () use ($task, $ids, $actor) {
            $current = $task->assignments()->get(['id', 'employee_id', 'status']);
            $activeIds = $current->where('status', TaskAssigneeStatus::Active->value)->pluck('employee_id')->map(fn ($i) => (int) $i)->all();

            $removed = array_values(array_diff($activeIds, $ids));
            $added = array_values(array_diff($ids, $activeIds));

            if ($removed !== []) {
                $task->assignments()->whereIn('employee_id', $removed)->delete();
                $this->record($task, $actor, 'unassigned', ['employee_ids' => $removed]);
            }

            $this->attachAssignees($task, $added, $actor);

            $task->forceFill(['last_activity_at' => now()])->saveQuietly();

            return $added;
        });

        if ($added !== []) {
            TaskAssigned::dispatch($task, $added, $actor);
        }
    }

    public function decline(Task $task, Model $actor, ?string $reason = null): void
    {
        $this->authorize($this->access->canDecline($task, $actor));

        DB::transaction(function () use ($task, $actor, $reason) {
            $task->assignments()
                ->where('employee_id', $actor->getKey())
                ->update([
                    'status'         => TaskAssigneeStatus::Declined->value,
                    'decline_reason' => $reason,
                    'responded_at'   => now(),
                ]);

            $this->record($task, $actor, 'declined', array_filter(['reason' => $reason]));
            $task->forceFill(['last_activity_at' => now()])->saveQuietly();
        });

        TaskAssignmentDeclined::dispatch($task, (int) $actor->getKey(), $reason, $actor);
    }

    /**
     * يفلتر القائمة للموظفين المسموح تكليفهم (نفس الفرع أو أعضاء المشروع).
     * أي رقم برّه القائمة = خطأ واضح بدل ما يتجاهل بصمت.
     *
     * @return array<int, int>
     */
    private function validAssignees(array $ids, int $branchId, ?int $projectId): array
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids))));

        if ($ids === []) {
            return [];
        }

        $valid = $this->access->assignableEmployeesQuery($branchId, $projectId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($i) => (int) $i)
            ->all();

        if (count($valid) !== count($ids)) {
            throw new InvalidStateException(__('task.errors.invalid_assignee'));
        }

        return $valid;
    }

    /** إضافة مكلّفين (ولو كان اعتذر قبل كده، التكليف بيرجع فعّال). */
    private function attachAssignees(Task $task, array $employeeIds, Model $actor): void
    {
        if ($employeeIds === []) {
            return;
        }

        foreach ($employeeIds as $employeeId) {
            TaskAssignee::updateOrCreate(
                ['task_id' => $task->id, 'employee_id' => $employeeId],
                [
                    'status'           => TaskAssigneeStatus::Active->value,
                    'decline_reason'   => null,
                    'responded_at'     => null,
                    'assigned_by_type' => $actor::class,
                    'assigned_by_id'   => $actor->getKey(),
                ],
            );
        }

        $this->record($task, $actor, 'assigned', ['employee_ids' => array_values($employeeIds)]);
    }

    // ========================================================
    //  الخطوات
    // ========================================================

    public function addChecklistItem(Task $task, string $title, Model $actor): TaskChecklistItem
    {
        $this->authorize($this->access->canWork($task, $actor));

        return DB::transaction(function () use ($task, $title, $actor) {
            $item = $task->checklistItems()->create([
                'title'    => trim($title),
                'position' => (int) $task->checklistItems()->max('position') + 1,
            ]);

            $this->record($task, $actor, 'checklist_added', ['title' => $item->title]);
            $this->afterChecklistChange($task);

            return $item;
        });
    }

    public function toggleChecklistItem(Task $task, TaskChecklistItem $item, Model $actor): TaskChecklistItem
    {
        $this->ensureItemBelongs($task, $item);
        $this->authorize($this->access->canWork($task, $actor));

        DB::transaction(function () use ($item, $task, $actor) {
            $done = ! $item->is_done;

            $item->update([
                'is_done'      => $done,
                'done_at'      => $done ? now() : null,
                'done_by_type' => $done ? $actor::class : null,
                'done_by_id'   => $done ? $actor->getKey() : null,
            ]);

            $this->record($task, $actor, $done ? 'checklist_done' : 'checklist_undone', ['title' => $item->title]);
            $this->afterChecklistChange($task);
        });

        return $item;
    }

    public function deleteChecklistItem(Task $task, TaskChecklistItem $item, Model $actor): void
    {
        $this->ensureItemBelongs($task, $item);
        $this->authorize($this->access->canWork($task, $actor));

        DB::transaction(function () use ($item, $task, $actor) {
            $title = $item->title;
            $item->delete();

            $this->record($task, $actor, 'checklist_removed', ['title' => $title]);
            $this->afterChecklistChange($task);
        });
    }

    private function ensureItemBelongs(Task $task, TaskChecklistItem $item): void
    {
        if ((int) $item->task_id !== (int) $task->id) {
            throw new InvalidStateException(__('task.errors.forbidden'));
        }
    }

    private function afterChecklistChange(Task $task): void
    {
        $task->last_activity_at = now();
        $task->refreshCounters();
        $this->refreshParents($task);
    }

    // ========================================================
    //  ارتباط المهام
    // ========================================================

    /**
     * $task ماتبدأش إلا لما $dependsOn تخلص.
     *
     * - نفس الفرع (أو نفس المشروع)، ومش المهمة نفسها.
     * - مفيش حلقة: لو $dependsOn مستنية $task (مباشرة أو بطريق غير مباشر)
     *   الربط بيترفض — وإلا الاتنين هيفضلوا مستنيين بعض للأبد.
     */
    public function addDependency(Task $task, int $dependsOnId, Model $actor): void
    {
        $this->authorize($this->access->canUpdate($task, $actor));

        $dependsOn = Task::withoutGlobalScopes()->whereNull('deleted_at')->find($dependsOnId);

        if (! $dependsOn || $dependsOn->id === $task->id) {
            throw new InvalidStateException(__('task.errors.invalid_dependency'));
        }

        $sameScope = $task->project_id ? $dependsOn->project_id === $task->project_id : $dependsOn->branch_id === $task->branch_id;

        if (! $sameScope) {
            throw new InvalidStateException(__('task.errors.invalid_dependency'));
        }

        if ($this->reaches($dependsOn->id, $task->id)) {
            throw new InvalidStateException(__('task.errors.dependency_cycle'));
        }

        DB::transaction(function () use ($task, $dependsOn, $actor) {
            $task->dependencies()->syncWithoutDetaching([$dependsOn->id]);
            $this->record($task, $actor, 'dependency_added', ['title' => $dependsOn->title]);
            $task->forceFill(['last_activity_at' => now()])->saveQuietly();
        });
    }

    public function removeDependency(Task $task, int $dependsOnId, Model $actor): void
    {
        $this->authorize($this->access->canUpdate($task, $actor));

        DB::transaction(function () use ($task, $dependsOnId, $actor) {
            $title = Task::withoutGlobalScopes()->whereKey($dependsOnId)->value('title');

            if ($task->dependencies()->detach($dependsOnId)) {
                $this->record($task, $actor, 'dependency_removed', ['title' => $title]);
            }
        });
    }

    /**
     * هل $from مستنية $target (مباشرة أو عبر سلسلة)؟
     * بحث بالعرض على جدول الارتباطات — استعلام واحد لكل مستوى.
     */
    private function reaches(int $from, int $target): bool
    {
        $frontier = [$from];
        $seen = [];

        while ($frontier !== []) {
            if (in_array($target, $frontier, true)) {
                return true;
            }

            $seen = array_merge($seen, $frontier);
            $frontier = DB::table('task_dependencies')
                ->whereIn('task_id', $frontier)
                ->pluck('depends_on_id')
                ->map(fn ($id) => (int) $id)
                ->diff($seen)
                ->unique()
                ->values()
                ->all();
        }

        return false;
    }

    // ========================================================
    //  الحذف والاسترجاع
    // ========================================================

    public function delete(Task $task, Model $actor): void
    {
        $this->authorize($this->access->canDelete($task, $actor));

        DB::transaction(function () use ($task, $actor) {
            $this->record($task, $actor, 'deleted');
            $task->delete();
            $this->refreshParents($task);
        });
        TaskUpdated::dispatch($task, $actor);
    }

    public function restore(int $id, Model $actor): Task
    {
        $task = Task::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($task, $actor) {
            $task->restore();
            $this->record($task, $actor, 'restored');
            $this->refreshParents($task);
        });

        TaskUpdated::dispatch($task, $actor);

        return $task;
    }

    // ========================================================
    //  مساعدات
    // ========================================================

    /** الحقول المسموح تتملّى من الفورم بس (مش الحالة ولا العدادات). */
    private function taskAttributes(array $data): array
    {
        $attributes = array_intersect_key($data, array_flip([
            'title', 'description', 'priority', 'starts_at', 'due_at', 'estimated_minutes',
            'requires_review', 'progress_source', 'progress_update_interval', 'progress_update_every_days',
        ]));

        // تاريخ فاضي = مفيش تاريخ (مش "دلوقتي")
        foreach (['starts_at', 'due_at', 'description', 'estimated_minutes'] as $nullable) {
            if (array_key_exists($nullable, $attributes) && $attributes[$nullable] === '') {
                $attributes[$nullable] = null;
            }
        }

        if (($attributes['progress_update_interval'] ?? null) !== null && $attributes['progress_update_interval'] !== 'custom') {
            $attributes['progress_update_every_days'] = null;
        }

        return $attributes;
    }

    /** المهمة الأم والمشروع بيعيدوا حساب عداداتهم ونسبتهم. */
    private function refreshParents(Task $task): void
    {
        if ($task->parent_id) {
            Task::withoutGlobalScopes()->find($task->parent_id)?->refreshCounters();
        }

        if ($task->project_id) {
            Project::withoutGlobalScopes()->find($task->project_id)?->refreshStats();
        }
    }

    public function record(Task $task, ?Model $actor, string $event, array $properties = []): TaskActivity
    {
        return TaskActivity::create([
            'task_id'    => $task->id,
            'actor_type' => $actor ? $actor::class : null,
            'actor_id'   => $actor?->getKey(),
            'event'      => $event,
            'properties' => $properties ?: null,
            'created_at' => now(),
        ]);
    }

    private function authorize(bool $allowed): void
    {
        if (! $allowed) {
            throw new InvalidStateException(__('task.errors.forbidden'));
        }
    }
}
