<?php

namespace App\Services\Tasks;

use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;

/**
 * بيانات شاشة تفاصيل المهمة — لوحة الإدارة والبوابة بيعرضوا نفس الشاشة.
 *
 * كل العلاقات بتتحمّل هنا مرة واحدة (eager loading) فعدد الاستعلامات
 * ثابت مهما كان عدد الخطوات أو المهام الفرعية أو الأحداث.
 */
class TaskViewData
{
    /** آخر كام حدث بيظهر في الخط الزمني. */
    private const TIMELINE_LIMIT = 50;

    private const PROGRESS_LIMIT = 20;

    public function __construct(
        private TaskAccess $access,
    ) {}

    public function build(Task $task, Model $actor): array
    {
        $task->load([
            'branch:id,name',
            'project:id,code,name,manager_id,branch_id',
            'parent:id,code,title,status',
            'creator',
            'assignments' => fn ($q) => $q->orderBy('id'),
            'assignments.employee:id,name,avatar,manager_id,branch_id',
            'checklistItems',
            'subtasks' => fn ($q) => $q->select(['id', 'code', 'title', 'status', 'priority', 'progress', 'due_at', 'parent_id', 'position', 'branch_id']),
            'subtasks.assignees:id,name,avatar',
            'labels:id,name,color',
            'dependencies:id,code,title,status',
            'progressUpdates' => fn ($q) => $q->limit(self::PROGRESS_LIMIT),
            'progressUpdates.author',
            'activities' => fn ($q) => $q->limit(self::TIMELINE_LIMIT),
            'activities.actor',
        ]);

        $abilities = $this->access->abilities($task, $actor);

        return [
            'task'         => $task,
            'abilities'    => $abilities,
            'transitions'  => $this->transitions($task, $abilities, $actor),
            'names'        => $this->timelineNames($task),
            'expected'     => $task->expectedProgress(),
            'health'       => $task->progressHealth(),
            'blocked'      => $task->isOpen() && $task->dependencies->contains(fn ($d) => $d->status !== TaskStatus::Completed->value),
            'actor'        => $actor,
        ];
    }

    /**
     * أزرار تغيير الحالة المتاحة للمستخدم ده بالترتيب المنطقي.
     *
     * @return array<int, array{status: string, label: string, class: string, icon: string}>
     */
    private function transitions(Task $task, array $abilities, Model $actor): array
    {
        if (! $abilities['status']) {
            return [];
        }

        $from = $task->statusEnum();
        $canDecide = $abilities['update'] || $abilities['approve'];

        // المكلّف يقدر يقفل لو المهمة مش محتاجة مراجعة، أو مفيش حد يراجعها أصلاً
        $canComplete = $abilities['approve'] || ! $task->requires_review || $task->resolveReviewer() === null;

        $buttons = [];

        foreach ($from->allowedTransitions() as $to) {
            $button = match (true) {
                $to === TaskStatus::InProgress && $from->isClosed() => $canDecide ? ['reopen', 'btn-label-secondary', 'fa-rotate-left'] : null,
                $to === TaskStatus::InProgress && $from === TaskStatus::InReview => $abilities['approve'] ? ['return', 'btn-label-warning', 'fa-arrow-rotate-left'] : null,
                $to === TaskStatus::InProgress => ['start', 'btn-primary', 'fa-play'],
                $to === TaskStatus::InReview => $task->requires_review && ! ($canComplete && $abilities['approve']) ? ['send_to_review', 'btn-warning', 'fa-paper-plane'] : null,
                $to === TaskStatus::Completed => $canComplete ? ['complete', 'btn-success', 'fa-check'] : null,
                $to === TaskStatus::Cancelled => $canDecide ? ['cancel', 'btn-label-danger', 'fa-ban'] : null,
                $to === TaskStatus::New && $from->isClosed() => $canDecide ? ['reopen', 'btn-label-secondary', 'fa-rotate-left'] : null,
                default => null,
            };

            if ($button === null) {
                continue;
            }

            // زرار إعادة فتح واحد بس
            if ($button[0] === 'reopen' && collect($buttons)->contains('action', 'reopen')) {
                continue;
            }

            $buttons[] = [
                'status' => $to->value,
                'action' => $button[0],
                'label'  => __('task.actions.' . $button[0]),
                'class'  => $button[1],
                'icon'   => $button[2],
            ];
        }

        return $buttons;
    }

    /**
     * أسماء الموظفين المذكورين في أحداث التكليف — استعلام واحد لكل الأحداث.
     *
     * @return array<int, string>
     */
    private function timelineNames(Task $task): array
    {
        $ids = $task->activities
            ->flatMap(fn ($a) => $a->properties['employee_ids'] ?? [])
            ->merge($task->assignments->pluck('employee_id'))
            ->unique()
            ->values();

        $known = $task->assignments->pluck('employee.name', 'employee_id');
        $missing = $ids->diff($known->keys());

        if ($missing->isNotEmpty()) {
            $known = $known->union(
                Employee::withoutGlobalScopes()->whereIn('id', $missing)->pluck('name', 'id')
            );
        }

        return $known->all();
    }
}
