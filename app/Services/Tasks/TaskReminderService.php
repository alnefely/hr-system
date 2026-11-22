<?php

namespace App\Services\Tasks;

use App\Enums\TaskAssigneeStatus;
use App\Enums\TaskProgressSource;
use App\Enums\TaskReminderType;
use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\Task;
use App\Models\TaskReminder;
use App\Models\TaskTimeEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * التذكيرات التلقائية (بيشغّلها الأمر المجدول tasks:automation):
 *
 *   قرب التسليم   ← المكلّفين، مرة واحدة لكل موعد تسليم
 *   اتأخرت        ← المكلّفين واللي عمل المهمة، مرة واحدة لكل موعد
 *   تحديث النسبة  ← المكلّفين، كل فترة حسب إعداد المهمة
 *
 * عدم التكرار: كل تذكير بيتسجّل في task_reminders بنوعه وموعده
 * (remind_at = موعد التسليم وقتها). لو الموعد اتغيّر، التذكير بيتبعت تاني
 * للموعد الجديد — ولو ماتغيّرش مابيتبعتش مرتين مهما الأمر اشتغل.
 */
class TaskReminderService
{
    /** المهام القديمة جداً مابنفكّرش بيها (عشان أول تشغيل مايغرقش الناس). */
    private const OVERDUE_LOOKBACK_DAYS = 30;

    public function __construct(
        private TaskNotifier $notifier,
    ) {}

    public function sendDueSoon(): int
    {
        $hours = (int) config('tasks.reminders.due_soon_hours', 24);

        return $this->each(
            Task::query()->open()->whereNotNull('due_at')->whereBetween('due_at', [now(), now()->addHours($hours)]),
            TaskReminderType::DueSoon,
            fn (Task $task) => $this->notifier->remind($task, 'due_soon', $this->assignees($task), 'fa-solid fa-hourglass-half', 'warning'),
        );
    }

    public function sendOverdue(): int
    {
        return $this->each(
            Task::query()->overdue()->where('due_at', '>=', now()->subDays(self::OVERDUE_LOOKBACK_DAYS)),
            TaskReminderType::Overdue,
            fn (Task $task) => $this->notifier->remind($task, 'overdue', $this->assignees($task)->push($task->creator), 'fa-solid fa-triangle-exclamation', 'danger'),
        );
    }

    /** "حدّث نسبة الإنجاز" للمهام اللي موعد تحديثها جه — والموعد الجاي بيتحسب. */
    public function sendProgressDue(): int
    {
        $count = 0;

        Task::query()->open()
            ->where('progress_source', TaskProgressSource::Manual->value)
            ->where('progress_update_interval', '!=', 'none')
            ->whereNotNull('next_progress_update_at')
            ->where('next_progress_update_at', '<=', now())
            ->with('creator')
            ->chunkById(200, function ($tasks) use (&$count) {
                foreach ($tasks as $task) {
                    $this->notifier->remind($task, 'progress_due', $this->assignees($task), 'fa-solid fa-chart-line', 'info');
                    $this->record($task, TaskReminderType::ProgressUpdate, $task->next_progress_update_at);

                    $task->forceFill(['next_progress_update_at' => $task->nextProgressUpdateDate(now())])->saveQuietly();
                    $count++;
                }
            });

        return $count;
    }

    /** عدادات اتنست شغّالة أكتر من الحد ← بتتقفل بالحد الأقصى. */
    public function closeStaleTimers(TaskTimeService $time): int
    {
        $stale = TaskTimeEntry::query()->running()
            ->where('started_at', '<', now()->subMinutes(TaskTimeService::maxTimerMinutes()))
            ->pluck('employee_id')->unique();

        foreach ($stale as $employeeId) {
            $employee = Employee::withoutGlobalScopes()->find($employeeId);
            $employee && $time->stop($employee);
        }

        return $stale->count();
    }

    // ========================================================
    //  مساعدات
    // ========================================================

    /** يبعت لكل مهمة مالهاش تذكير من النوع ده لنفس الموعد، ويسجّله. */
    private function each(Builder $query, TaskReminderType $type, callable $send): int
    {
        $count = 0;

        $query->whereNotExists(fn ($q) => $q->selectRaw('1')->from('task_reminders')
                ->whereColumn('task_reminders.task_id', 'tasks.id')
                ->where('task_reminders.type', $type->value)
                ->whereColumn('task_reminders.remind_at', 'tasks.due_at'))
            ->with('creator')
            ->chunkById(200, function ($tasks) use ($type, $send, &$count) {
                foreach ($tasks as $task) {
                    $send($task);
                    $this->record($task, $type, $task->due_at);
                    $count++;
                }
            });

        return $count;
    }

    private function record(Task $task, TaskReminderType $type, $remindAt): void
    {
        TaskReminder::create([
            'task_id'   => $task->id,
            'type'      => $type->value,
            'remind_at' => $remindAt,
            'sent_at'   => now(),
        ]);
    }

    private function assignees(Task $task): Collection
    {
        return Employee::withoutGlobalScopes()
            ->whereIn('id', $task->assignments()->where('status', TaskAssigneeStatus::Active->value)->pluck('employee_id'))
            ->get(['id', 'name'])
            ->toBase();
    }
}
