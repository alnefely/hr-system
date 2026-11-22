<?php

namespace App\Services\Tasks;

use App\Enums\TaskRecurrenceFrequency;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\Task;
use App\Models\TaskRecurrence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * المهام المتكررة: حفظ القاعدة، وإنشاء المهام لما ميعادها ييجي.
 *
 * - الأمر المجدول بينشئ نسخة واحدة بس لكل قاعدة في كل تشغيل؛ لو السيرفر
 *   كان واقف وفات أكتر من ميعاد، مابنغرقش الموظف بمهام قديمة — بنعمل
 *   الأحدث ونكمّل من الميعاد الجاي.
 * - قفل على القاعدة وقت التشغيل: تشغيلين متزامنين مايعملوش نفس المهمة مرتين.
 * - مكلّف اتفصل أو اتنقل؟ المهمة بتتعمل بالباقيين بدل ما القاعدة توقف.
 */
class TaskRecurrenceService
{
    private const BLUEPRINT_KEYS = ['title', 'description', 'priority', 'assignee_ids', 'checklist', 'requires_review', 'estimated_minutes', 'progress_update_interval', 'progress_update_every_days'];

    public function __construct(
        private TaskService $tasks,
        private TaskAccess $access,
    ) {}

    public function save(array $data, Model $actor, ?TaskRecurrence $rule = null): TaskRecurrence
    {
        $blueprint = array_intersect_key($data, array_flip(self::BLUEPRINT_KEYS));
        $blueprint['checklist'] = array_values(array_filter(array_map('trim', (array) ($blueprint['checklist'] ?? []))));
        $blueprint['assignee_ids'] = array_values(array_map('intval', (array) ($blueprint['assignee_ids'] ?? [])));

        $attributes = [
            'blueprint'     => $blueprint,
            'project_id'    => $data['project_id'] ?? null,
            'frequency'     => $data['frequency'],
            'interval'      => max(1, (int) ($data['interval'] ?? 1)),
            'weekdays'      => $data['frequency'] === TaskRecurrenceFrequency::Weekly->value
                ? array_values(array_unique(array_map('intval', $data['weekdays'] ?? []))) ?: null : null,
            'month_day'     => $data['frequency'] === TaskRecurrenceFrequency::Monthly->value ? ($data['month_day'] ?? null) : null,
            'duration_days' => (int) ($data['duration_days'] ?? 0),
            'run_time'      => $data['run_time'] ?? '08:00',
            'starts_on'     => $data['starts_on'],
            'ends_on'       => $data['ends_on'] ?? null,
            'is_active'     => (bool) ($data['is_active'] ?? true),
        ];

        $rule ??= new TaskRecurrence([
            'branch_id'    => $data['branch_id'],
            'creator_type' => $actor::class,
            'creator_id'   => $actor->getKey(),
        ]);

        $rule->fill($attributes);
        $rule->next_run_at = $rule->is_active ? $this->firstOccurrence($rule) : null;
        $rule->save();

        return $rule;
    }

    public function toggle(TaskRecurrence $rule): TaskRecurrence
    {
        $rule->is_active = ! $rule->is_active;
        $rule->next_run_at = $rule->is_active ? $this->firstOccurrence($rule) : null;
        $rule->save();

        return $rule;
    }

    /**
     * أول ميعاد جاي (من النهاردة أو من تاريخ البداية) حسب القاعدة.
     * null = القاعدة خلصت (تاريخ النهاية عدّى).
     */
    public function firstOccurrence(TaskRecurrence $rule, ?Carbon $now = null): ?Carbon
    {
        $now ??= now();
        [$hour, $minute] = array_map('intval', explode(':', (string) $rule->run_time) + [0, 0]);
        $candidate = Carbon::parse($rule->starts_on)->setTime($hour, $minute);

        // أول يوم مطابق للقاعدة من تاريخ البداية
        $frequency = TaskRecurrenceFrequency::from($rule->frequency);

        if ($frequency === TaskRecurrenceFrequency::Weekly && ! empty($rule->weekdays) && ! in_array($candidate->dayOfWeek, $rule->weekdays, true)) {
            $candidate = $rule->nextOccurrenceAfter($candidate->copy()->subDay()->setTime($hour, $minute)) ?? $candidate;
        } elseif ($frequency === TaskRecurrenceFrequency::Monthly && $rule->month_day) {
            // يوم الشهر ده لو لسه ماعدّاش، وإلا نفس اليوم في الشهر الجاي (أو آخره لو أقصر)
            $month = $candidate->day <= min($rule->month_day, $candidate->daysInMonth)
                ? $candidate->copy()->startOfMonth()
                : $candidate->copy()->startOfMonth()->addMonthNoOverflow();
            $candidate = $month->day(min($rule->month_day, $month->daysInMonth))->setTime($hour, $minute);
        }

        // لقدّام لحد ما نوصل لميعاد لسه ماجاش (بحد أقصى للأمان)
        for ($i = 0; $candidate !== null && $candidate->lt($now) && $i < 5000; $i++) {
            $candidate = $rule->nextOccurrenceAfter($candidate);
        }

        if ($candidate !== null && $rule->ends_on && $candidate->gt(Carbon::parse($rule->ends_on)->endOfDay())) {
            return null;
        }

        return $candidate;
    }

    /**
     * ينشئ مهام كل القواعد اللي ميعادها جه.
     *
     * @return int عدد المهام اللي اتعملت
     */
    public function runDue(): int
    {
        $created = 0;

        foreach (TaskRecurrence::withoutGlobalScopes()->whereNull('deleted_at')->due()->pluck('id') as $id) {
            try {
                $created += $this->runOne($id) ? 1 : 0;
            } catch (\Throwable $e) {
                // قاعدة بايظة ماتوقفش الباقي
                Log::error('Task recurrence failed', ['recurrence_id' => $id, 'error' => $e->getMessage()]);
            }
        }

        return $created;
    }

    private function runOne(int $id): ?Task
    {
        return DB::transaction(function () use ($id) {
            /** @var TaskRecurrence|null $rule */
            $rule = TaskRecurrence::withoutGlobalScopes()->whereKey($id)->lockForUpdate()->first();

            // اتشغّلت من عملية تانية في نفس اللحظة
            if (! $rule || ! $rule->is_active || ! $rule->next_run_at || $rule->next_run_at->isFuture()) {
                return null;
            }

            $occurrence = $rule->next_run_at->copy();
            // اللي عمل القاعدة هو صاحب المهام (مشرف أو موظف) — من غير عزل فروع
            $actor = match ($rule->creator_type) {
                Admin::class    => Admin::find($rule->creator_id),
                Employee::class => Employee::withoutGlobalScopes()->whereNull('deleted_at')->where('status', '!=', 'terminated')->find($rule->creator_id),
                default         => null,
            };

            if (! $actor instanceof Admin && ! $actor instanceof Employee) {
                $rule->forceFill(['is_active' => false, 'next_run_at' => null])->save();

                return null;
            }

            $task = $this->createTask($rule, $occurrence, $actor);

            // الميعاد الجاي — لو فات أكتر من ميعاد بنعدّيهم (نسخة واحدة بس)
            $next = $rule->nextOccurrenceAfter($occurrence);
            for ($i = 0; $next !== null && $next->lte(now()) && $i < 5000; $i++) {
                $next = $rule->nextOccurrenceAfter($next);
            }

            $rule->forceFill([
                'last_run_at'       => now(),
                'occurrences_count' => $rule->occurrences_count + 1,
                'next_run_at'       => $next,
                'is_active'         => $next !== null,
            ])->save();

            return $task;
        });
    }

    private function createTask(TaskRecurrence $rule, Carbon $occurrence, Model $actor): Task
    {
        $blueprint = $rule->blueprint ?? [];

        // المكلّفين اللي لسه ينفع يتكلّفوا بس (مفصول أو اتنقل فرع ← بيتشال)
        $assignees = $this->access->assignableEmployeesQuery($rule->branch_id, $rule->project_id)
            ->whereIn('id', $blueprint['assignee_ids'] ?? [])
            ->pluck('id')->all();

        $due = $occurrence->copy()->addDays($rule->duration_days)->setTime(17, 0);
        if ($due->lte($occurrence)) {
            $due = $occurrence->copy()->addHours(8);
        }

        $task = $this->tasks->create([
            'title'                      => $blueprint['title'] ?? '—',
            'description'                => $blueprint['description'] ?? null,
            'priority'                   => $blueprint['priority'] ?? 'medium',
            'branch_id'                  => $rule->branch_id,
            'project_id'                 => $rule->project_id,
            'assignee_ids'               => $assignees,
            'checklist'                  => $blueprint['checklist'] ?? [],
            'requires_review'            => $blueprint['requires_review'] ?? true,
            'estimated_minutes'          => $blueprint['estimated_minutes'] ?? null,
            'progress_update_interval'   => $blueprint['progress_update_interval'] ?? 'none',
            'progress_update_every_days' => $blueprint['progress_update_every_days'] ?? null,
            'starts_at'                  => $occurrence,
            'due_at'                     => $due,
        ], $actor);

        $task->forceFill(['recurrence_id' => $rule->id])->saveQuietly();

        return $task;
    }
}
