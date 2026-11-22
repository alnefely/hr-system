<?php

namespace App\Services\Tasks;

use App\Exceptions\InvalidStateException;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\Task;
use App\Models\TaskTimeEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * تتبع الوقت على المهام: عدّاد (تشغيل/إيقاف) وإدخال يدوي.
 *
 * القواعد:
 *  - الوقت بيتسجّل لموظف (مكلّف أو صاحب المهمة) — مش للمشرف.
 *  - عدّاد واحد شغّال للموظف في نفس الوقت: تشغيل عدّاد جديد بيوقف القديم.
 *  - العدّاد اللي نسيه الموظف شغّال بيتحسب بحد أقصى (config) مش أيام.
 *  - spent_minutes على المهمة بيتحسب من مجموع السجلات (مش زيادة ونقص).
 *  - المشرف (task_update) يقدر يسجّل وقت يدوي لموظف مكلّف ويحذف أي سجل.
 */
class TaskTimeService
{
    public function __construct(
        private TaskAccess $access,
        private TaskService $tasks,
    ) {}

    public static function maxTimerMinutes(): int
    {
        return (int) config('tasks.time.max_timer_hours', 12) * 60;
    }

    /** العدّاد الشغّال للموظف (لو فيه). */
    public function running(Employee $employee): ?TaskTimeEntry
    {
        return TaskTimeEntry::query()->running()->where('employee_id', $employee->id)->latest('id')->first();
    }

    public function start(Task $task, Employee $employee): TaskTimeEntry
    {
        $this->authorizeWork($task, $employee);

        if (! $task->isOpen()) {
            throw new InvalidStateException(__('task.errors.closed'));
        }

        // مستنية مهمة تانية = الشغل عليها لسه مابدأش
        if ($task->isBlocked()) {
            throw new InvalidStateException(__('task.errors.blocked'));
        }

        return DB::transaction(function () use ($task, $employee) {
            // قفل على عدادات الموظف: ضغطتين ورا بعض مايشغّلوش عدّادين
            $current = TaskTimeEntry::query()->running()->where('employee_id', $employee->id)->lockForUpdate()->get();

            foreach ($current as $entry) {
                if ((int) $entry->task_id === (int) $task->id) {
                    return $entry;   // شغّال أصلاً على نفس المهمة
                }

                $this->close($entry);
            }

            return TaskTimeEntry::create([
                'task_id'     => $task->id,
                'employee_id' => $employee->id,
                'started_at'  => now(),
            ]);
        });
    }

    public function stop(Employee $employee, ?string $note = null): ?TaskTimeEntry
    {
        return DB::transaction(function () use ($employee, $note) {
            $entry = TaskTimeEntry::query()->running()->where('employee_id', $employee->id)->lockForUpdate()->latest('id')->first();

            if (! $entry) {
                return null;
            }

            $this->close($entry, $note);

            return $entry;
        });
    }

    /** إيقاف كل العدادات الشغّالة على مهمة (لما تتقفل). */
    public function stopAllFor(Task $task): void
    {
        TaskTimeEntry::query()->running()->where('task_id', $task->id)->get()->each(fn ($e) => $this->close($e));
    }

    /**
     * وقت يدوي: "اشتغلت ساعتين على المهمة يوم كذا".
     *
     * $actor موظف (لنفسه) أو مشرف (لموظف مكلّف بالمهمة).
     */
    public function addManual(Task $task, Model $actor, int $minutes, Carbon $date, ?string $note = null, ?int $employeeId = null): TaskTimeEntry
    {
        if ($minutes < 1 || $minutes > 24 * 60) {
            throw new InvalidStateException(__('task.errors.invalid_minutes'));
        }

        if ($date->isFuture() && ! $date->isToday()) {
            throw new InvalidStateException(__('task.errors.future_time'));
        }

        if ($actor instanceof Employee) {
            // مسموح حتى على مهمة مقفولة: وقت اتنسى يتسجّل قبل الإغلاق
            $this->authorizeWork($task, $actor);
            $employeeId = $actor->id;
        } else {
            $this->authorize($actor instanceof Admin && $this->access->canUpdate($task, $actor));

            if (! $employeeId || ! $task->assignments()->where('employee_id', $employeeId)->exists()) {
                throw new InvalidStateException(__('task.errors.invalid_assignee'));
            }
        }

        return DB::transaction(function () use ($task, $actor, $minutes, $date, $note, $employeeId) {
            $start = $date->copy()->setTime(9, 0);
            $entry = TaskTimeEntry::create([
                'task_id'     => $task->id,
                'employee_id' => $employeeId,
                'started_at'  => $start,
                'ended_at'    => $start->copy()->addMinutes($minutes),
                'minutes'     => $minutes,
                'is_manual'   => true,
                'note'        => $note,
            ]);

            $this->tasks->record($task, $actor, 'time_logged', ['minutes' => $minutes, 'note' => $note, 'employee_ids' => [$employeeId]]);
            $this->refreshSpent($task);

            return $entry;
        });
    }

    public function delete(Task $task, TaskTimeEntry $entry, Model $actor): void
    {
        if ((int) $entry->task_id !== (int) $task->id) {
            throw new InvalidStateException(__('task.errors.forbidden'));
        }

        $own = $actor instanceof Employee && (int) $entry->employee_id === (int) $actor->id;
        $this->authorize($own || ($actor instanceof Admin && $this->access->canUpdate($task, $actor)));

        DB::transaction(function () use ($task, $entry) {
            $entry->delete();
            $this->refreshSpent($task);
        });
    }

    // ========================================================
    //  مساعدات
    // ========================================================

    /** قفل سجل شغّال: الدقائق من البداية (بحد أقصى) — ودقيقة على الأقل. */
    private function close(TaskTimeEntry $entry, ?string $note = null): void
    {
        $end = now();
        $minutes = (int) ceil($entry->started_at->diffInSeconds($end) / 60);
        $max = self::maxTimerMinutes();

        if ($minutes > $max) {
            $minutes = $max;
            $end = $entry->started_at->copy()->addMinutes($max);
        }

        $entry->update([
            'ended_at' => $end,
            'minutes'  => max(1, $minutes),
            'note'     => $note ?? $entry->note,
        ]);

        $task = Task::withoutGlobalScopes()->find($entry->task_id);

        if ($task) {
            $employee = Employee::withoutGlobalScopes()->find($entry->employee_id);
            $this->tasks->record($task, $employee, 'time_logged', ['minutes' => $entry->minutes, 'note' => $note]);
            $this->refreshSpent($task);
        }
    }

    private function refreshSpent(Task $task): void
    {
        $task->forceFill([
            'spent_minutes'    => (int) TaskTimeEntry::where('task_id', $task->id)->whereNotNull('ended_at')->sum('minutes'),
            'last_activity_at' => now(),
        ])->saveQuietly();
    }

    private function authorizeWork(Task $task, Employee $employee): void
    {
        $this->authorize($this->access->canWork($task, $employee));
    }

    private function authorize(bool $allowed): void
    {
        if (! $allowed) {
            throw new InvalidStateException(__('task.errors.forbidden'));
        }
    }
}
