<?php

namespace App\Models;

use App\Enums\TaskRecurrenceFrequency;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\ScopedToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * قاعدة مهمة متكررة.
 *
 * blueprint (JSON): title, description, priority, assignee_ids[], checklist[],
 * requires_review, estimated_minutes, progress_update_interval ...
 */
class TaskRecurrence extends Model
{
    use SoftDeletes, ScopedToBranch, LogsActivity;

    protected $fillable = [
        'branch_id', 'project_id', 'blueprint', 'frequency', 'interval', 'weekdays',
        'month_day', 'duration_days', 'run_time', 'starts_on', 'ends_on',
        'next_run_at', 'last_run_at', 'occurrences_count', 'is_active',
        'creator_type', 'creator_id',
    ];

    protected $casts = [
        'blueprint'         => 'array',
        'weekdays'          => 'array',
        'interval'          => 'integer',
        'month_day'         => 'integer',
        'duration_days'     => 'integer',
        'starts_on'         => 'date',
        'ends_on'           => 'date',
        'next_run_at'       => 'datetime',
        'last_run_at'       => 'datetime',
        'occurrences_count' => 'integer',
        'is_active'         => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): MorphTo
    {
        return $this->morphTo();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'recurrence_id');
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now());
    }

    /** وصف مقروء: "كل 2 أسبوع · أيام: الأحد، الأربعاء · الساعة 08:00". */
    public function describe(): string
    {
        $parts = [$this->interval <= 1
            ? __('task.recurrence.every_one', ['unit' => __('task.recurrence.units_one.' . $this->frequency)])
            : __('task.recurrence.every', ['n' => $this->interval, 'unit' => __('task.recurrence.units.' . $this->frequency)])];

        if ($this->frequency === TaskRecurrenceFrequency::Weekly->value && ! empty($this->weekdays)) {
            $names = collect($this->weekdays)->sort()->map(fn ($d) => Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays((int) $d)->translatedFormat('l'));
            $parts[] = __('task.recurrence.on_days', ['days' => $names->implode('، ')]);
        }

        if ($this->frequency === TaskRecurrenceFrequency::Monthly->value && $this->month_day) {
            $parts[] = __('task.recurrence.on_day', ['day' => $this->month_day]);
        }

        $parts[] = __('task.recurrence.at', ['time' => substr((string) $this->run_time, 0, 5)]);

        return implode(' · ', $parts);
    }

    /**
     * الموعد اللي بعد $after حسب قاعدة التكرار، أو null لو القاعدة خلصت.
     */
    public function nextOccurrenceAfter(Carbon $after): ?Carbon
    {
        [$hour, $minute] = array_map('intval', explode(':', (string) $this->run_time) + [0, 0]);
        $interval = max(1, $this->interval);

        $candidate = match (TaskRecurrenceFrequency::from($this->frequency)) {
            TaskRecurrenceFrequency::Daily   => $after->copy()->addDays($interval),
            TaskRecurrenceFrequency::Weekly  => $this->nextWeekly($after, $interval),
            TaskRecurrenceFrequency::Monthly => $this->nextMonthly($after, $interval),
            TaskRecurrenceFrequency::Yearly  => $after->copy()->addYearsNoOverflow($interval),
        };

        $candidate->setTime($hour, $minute);

        if ($this->ends_on !== null && $candidate->greaterThan($this->ends_on->copy()->endOfDay())) {
            return null;
        }

        return $candidate;
    }

    /** نفس اليوم بعد X شهر — ولو الشهر أقصر (٣١ في فبراير) آخر يوم فيه. */
    private function nextMonthly(Carbon $after, int $interval): Carbon
    {
        $month = $after->copy()->startOfMonth()->addMonthsNoOverflow($interval);

        return $month->day(min($this->month_day ?: $after->day, $month->daysInMonth));
    }

    /** أقرب يوم من أيام الأسبوع المختارة بعد $after (مع احترام "كل X أسبوع"). */
    private function nextWeekly(Carbon $after, int $interval): Carbon
    {
        $days = collect($this->weekdays ?: [$after->dayOfWeek])->map(fn ($d) => (int) $d)->sort()->values();

        // يوم تاني في نفس الأسبوع
        $sameWeek = $days->first(fn (int $d) => $d > $after->dayOfWeek);

        if ($sameWeek !== null) {
            return $after->copy()->addDays($sameWeek - $after->dayOfWeek);
        }

        // أول يوم مختار في الأسبوع بعد X أسابيع
        return $after->copy()->startOfWeek(Carbon::SUNDAY)->addWeeks($interval)->addDays($days->first());
    }
}
