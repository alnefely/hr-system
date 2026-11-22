<?php

namespace App\Services\Tasks;

use App\Enums\NotificationType;
use App\Enums\TaskAssigneeStatus;
use App\Enums\TaskStatus;
use App\Events\Tasks\TaskAssigned;
use App\Events\Tasks\TaskAssignmentDeclined;
use App\Events\Tasks\TaskCommented;
use App\Events\Tasks\TaskStatusChanged;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\Task;
use App\Services\Notification\EmployeeNotificationService;
use App\Services\Notification\NotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * بيحوّل أحداث المهام لإشعارات للناس الصح.
 *
 *   اتكلّفت بمهمة             ← المكلّف الجديد
 *   اتسلّمت للمراجعة          ← المراجع (اللي أنشأها أو المدير المباشر)
 *   اتعتمدت / رجعت / اتلغت    ← المكلّفين (+ اللي أنشأها لو مش هو اللي عملها)
 *   حد اعتذر                  ← اللي أنشأها
 *   تعليق جديد                ← المكلّفين + اللي أنشأها + المتابعين
 *   إشارة @                   ← المشار إليه (إشعار مخصوص بدل العادي)
 *
 * عمر ما حد بيوصله إشعار بحاجة عملها بنفسه. الموظف بيوصله في البوابة،
 * والمشرف في جرس لوحة الإدارة — والاتنين لحظي عبر Pusher.
 */
class TaskNotifier
{
    public function __construct(
        private EmployeeNotificationService $employees,
        private NotificationService $admins,
    ) {}

    public static function subscribe(): array
    {
        return [
            TaskAssigned::class           => 'assigned',
            TaskStatusChanged::class      => 'statusChanged',
            TaskAssignmentDeclined::class => 'declined',
            TaskCommented::class          => 'commented',
        ];
    }

    public function assigned(TaskAssigned $event): void
    {
        $recipients = Employee::withoutGlobalScopes()->whereIn('id', $event->employeeIds)->get(['id', 'name']);

        $this->notify($recipients, $event->task, $event->actor, 'assigned', 'fa-solid fa-user-check', 'info');
    }

    public function statusChanged(TaskStatusChanged $event): void
    {
        $task = $event->task;
        $to = TaskStatus::from($event->to);

        if ($to === TaskStatus::InReview) {
            $task->loadMissing(['creator', 'assignees:id,name']);
            $reviewer = $task->resolveReviewer();

            $this->notify(collect([$reviewer])->filter(), $task, $event->actor, 'review_requested', 'fa-solid fa-clipboard-check', 'warning', NotificationType::TaskReviewRequested);

            return;
        }

        $key = match (true) {
            $to === TaskStatus::Completed                                     => 'completed',
            $to === TaskStatus::Cancelled                                     => 'cancelled',
            $to === TaskStatus::InProgress && $event->from === TaskStatus::InReview->value => 'returned',
            $to === TaskStatus::InProgress && in_array($event->from, [TaskStatus::Completed->value, TaskStatus::Cancelled->value], true) => 'reopened',
            default                                                           => null,
        };

        if ($key === null) {
            return;
        }

        $recipients = $this->assignees($task)->push($task->creator);

        // اكتملت أو اتلغت: الاتنين بيفكّوا المهام المستنياها
        if ($key === 'completed' || $key === 'cancelled') {
            $this->notifyUnblocked($task, $event->actor);
        }

        $this->notify($recipients, $task, $event->actor, $key,
            $key === 'completed' ? 'fa-solid fa-circle-check' : ($key === 'cancelled' ? 'fa-solid fa-ban' : 'fa-solid fa-arrow-rotate-left'),
            $key === 'completed' ? 'success' : ($key === 'cancelled' ? 'danger' : 'warning'));
    }

    /**
     * المهام اللي كانت مستنية المهمة دي وبقت جاهزة (كل اللي قبلها خلص)
     * ← المكلّفين بيها يعرفوا إنهم يقدروا يبدأوا.
     */
    private function notifyUnblocked(Task $task, ?Model $actor): void
    {
        $ready = Task::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereIn('status', TaskStatus::openValues())
            ->whereIn('id', fn ($q) => $q->select('task_id')->from('task_dependencies')->where('depends_on_id', $task->id))
            // مفيش ولا مهمة قبلها لسه مفتوحة
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('task_dependencies as d')
                ->join('tasks as blocker', 'blocker.id', '=', 'd.depends_on_id')
                ->whereColumn('d.task_id', 'tasks.id')
                ->whereNull('blocker.deleted_at')
                ->whereIn('blocker.status', TaskStatus::openValues()))
            ->get();

        foreach ($ready as $dependent) {
            $this->notify($this->assignees($dependent), $dependent, $actor, 'unblocked', 'fa-solid fa-lock-open', 'success', body: $task->title);
        }
    }

    public function declined(TaskAssignmentDeclined $event): void
    {
        $this->notify(collect([$event->task->creator])->filter(), $event->task, $event->actor, 'declined', 'fa-solid fa-hand', 'danger',
            body: $event->reason);
    }

    public function commented(TaskCommented $event): void
    {
        $task = $event->task;
        $excerpt = Str::limit((string) $event->comment->body, 140);

        $mentioned = Employee::withoutGlobalScopes()->whereIn('id', $event->mentionIds)->get(['id', 'name']);
        $this->notify($mentioned, $task, $event->actor, 'mentioned', 'fa-solid fa-at', 'info', body: $excerpt);

        $watchers = Employee::withoutGlobalScopes()->whereIn('id', $task->watchers()->where('watcher_type', Employee::class)->pluck('watcher_id'))->get(['id', 'name'])->toBase();

        $others = $this->assignees($task)
            ->push($task->creator)
            ->merge($watchers)
            // اللي اتشار له خد إشعار الإشارة — مانبعتلوش تاني
            ->reject(fn ($m) => $m instanceof Employee && in_array($m->id, $event->mentionIds, true));

        $this->notify($others, $task, $event->actor, 'commented', 'fa-solid fa-comment', 'info', body: $excerpt);
    }

    /** تذكير من النظام (مالوش صاحب) — بيستخدمه TaskReminderService. */
    public function remind(Task $task, string $key, Collection $recipients, string $icon, string $level): void
    {
        $this->notify($recipients, $task, null, $key, $icon, $level);
    }

    // ========================================================
    //  الإرسال
    // ========================================================

    private function assignees(Task $task): Collection
    {
        return Employee::withoutGlobalScopes()
            ->whereIn('id', $task->assignments()->where('status', TaskAssigneeStatus::Active->value)->pluck('employee_id'))
            ->get(['id', 'name'])
            // base collection: Eloquent merge بيدمج بالـ id، فموظف ومشرف بنفس الرقم كانوا هيتشالوا
            ->toBase();
    }

    /**
     * يبعت لكل مستلم مرة واحدة (موظف في البوابة، مشرف في اللوحة)
     * ويستبعد صاحب الحدث.
     */
    private function notify(Collection $recipients, Task $task, ?Model $actor, string $key, string $icon, string $level,
        NotificationType $adminType = NotificationType::TaskActivity, ?string $body = null): void
    {
        $actorName = $actor?->name ?? __('task.labels_text.system');
        $title = __('task.notifications.' . $key, ['actor' => $actorName]);
        $body = $body !== null && $body !== '' ? $task->title . ' — ' . $body : $task->title;

        $recipients
            ->filter()
            ->unique(fn (Model $m) => $m::class . ':' . $m->getKey())
            ->reject(fn (Model $m) => $actor && $m::class === $actor::class && $m->getKey() === $actor->getKey())
            ->each(function (Model $recipient) use ($task, $actor, $title, $body, $icon, $level, $adminType, $key) {
                if ($recipient instanceof Employee) {
                    $this->employees->send($recipient->id, 'task_' . $key, $title, $body, route('portal.tasks.show', $task->id),
                        $actor, ['task_id' => $task->id], $icon, $level);
                } elseif ($recipient instanceof Admin) {
                    $this->admins->send($recipient->id, $adminType, $title, $body, route('admin.tasks.show', $task->id),
                        $task->branch_id, ['task_id' => $task->id]);
                }
            });
    }
}
