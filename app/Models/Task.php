<?php

namespace App\Models;

use App\Enums\TaskAssigneeStatus;
use App\Enums\TaskProgressInterval;
use App\Enums\TaskProgressSource;
use App\Enums\TaskReviewStatus;
use App\Enums\TaskStatus;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\ScopedToBranch;
use App\Support\CodeSequence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * المهمة.
 *
 * ملاحظات أداء:
 *  - العدادات (المهام الفرعية، الخطوات، التعليقات، المرفقات) مخزّنة على
 *    الصف نفسه وبتتحدّث من refreshCounters()، فالقوائم ولوحة كانبان
 *    مابتحتاجش withCount ولا استعلام لكل كارت.
 *  - أي قائمة لازم تحمّل العلاقات اللي هتعرضها مقدماً (with) — الاختبارات
 *    شغّالة بـ LAZY_LOADING_THROW فأي N+1 بيكسر الصفحة.
 */
class Task extends Model
{
    use SoftDeletes, ScopedToBranch, LogsActivity;

    public const CODE_PREFIX = 'TSK-';

    protected string $activityLabelAttribute = 'title';

    /** العدادات والطوابع اللي بتتغير تلقائياً — ضوضاء في سجل الحركات. */
    protected array $activityHidden = [
        'subtasks_count', 'completed_subtasks_count', 'checklist_count', 'completed_checklist_count',
        'comments_count', 'attachments_count', 'spent_minutes', 'last_activity_at', 'position',
        'last_progress_update_at', 'next_progress_update_at',
    ];

    protected $fillable = [
        'code',
        'branch_id',
        'project_id',
        'parent_id',
        'recurrence_id',
        'title',
        'description',
        'status',
        'priority',
        'starts_at',
        'due_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'progress',
        'progress_source',
        'progress_update_interval',
        'progress_update_every_days',
        'last_progress_update_at',
        'next_progress_update_at',
        'estimated_minutes',
        'spent_minutes',
        'requires_review',
        'position',
        'creator_type',
        'creator_id',
        'last_activity_at',
    ];

    protected $casts = [
        'starts_at'                  => 'datetime',
        'due_at'                     => 'datetime',
        'started_at'                 => 'datetime',
        'completed_at'               => 'datetime',
        'cancelled_at'               => 'datetime',
        'last_progress_update_at'    => 'datetime',
        'next_progress_update_at'    => 'datetime',
        'last_activity_at'           => 'datetime',
        'progress'                   => 'integer',
        'progress_update_every_days' => 'integer',
        'estimated_minutes'          => 'integer',
        'spent_minutes'              => 'integer',
        'requires_review'            => 'boolean',
        'subtasks_count'             => 'integer',
        'completed_subtasks_count'   => 'integer',
        'checklist_count'            => 'integer',
        'completed_checklist_count'  => 'integer',
        'comments_count'             => 'integer',
        'attachments_count'          => 'integer',
        'position'                   => 'integer',
    ];

    protected $attributes = [
        'status'                   => 'new',
        'priority'                 => 'medium',
        'progress'                 => 0,
        'progress_source'          => 'manual',
        'progress_update_interval' => 'none',
        'requires_review'          => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (Task $task) {
            $task->code ??= CodeSequence::next(static::class, 'code', self::CODE_PREFIX, 5);
            $task->last_activity_at ??= now();

            // المهمة الفرعية بتاخد فرع ومشروع المهمة الأم
            if ($task->parent_id && (! $task->branch_id || ! $task->project_id)) {
                $parent = static::withoutGlobalScopes()->find($task->parent_id, ['id', 'branch_id', 'project_id']);
                $task->branch_id ??= $parent?->branch_id;
                $task->project_id ??= $parent?->project_id;
            }
        });

        // الحذف النهائي: نجمع ملفات المرفقات قبله ونمسحها بعد ما ينجح
        // (الصفوف بتتشال بالـ cascade، والملفات كانت بتفضل في storage)
        static::forceDeleting(function (Task $task) {
            $task->pendingFileCleanup = TaskAttachment::filesForTasks([$task->id]);
        });

        static::forceDeleted(function (Task $task) {
            TaskAttachment::deleteFiles($task->pendingFileCleanup);
        });
    }

    /** @var array<int, array{disk: string, path: string}> ملفات تتمسح بعد الحذف النهائي */
    public array $pendingFileCleanup = [];

    // ========================================================
    //  العلاقات
    // ========================================================

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(TaskRecurrence::class, 'recurrence_id');
    }

    /** اللي أنشأ المهمة: Admin أو Employee. */
    public function creator(): MorphTo
    {
        return $this->morphTo();
    }

    /** سجلات التكليف كلها (شاملة اللي اعتذروا). */
    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignee::class);
    }

    /** المكلّفين الفعليين (من غير المعتذرين). */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'task_assignees')
            ->using(TaskAssignee::class)
            ->withPivot(['id', 'status', 'decline_reason', 'responded_at', 'assigned_by_type', 'assigned_by_id'])
            ->withTimestamps()
            ->wherePivot('status', TaskAssigneeStatus::Active->value);
    }

    public function watchers(): HasMany
    {
        return $this->hasMany(TaskWatcher::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(TaskChecklistItem::class)->orderBy('position');
    }

    /** المهام اللي لازم تخلص الأول قبل المهمة دي. */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'task_dependencies', 'task_id', 'depends_on_id')->withTimestamps();
    }

    /** المهام اللي مستنية المهمة دي تخلص. */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'task_dependencies', 'depends_on_id', 'task_id')->withTimestamps();
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(TaskLabel::class, 'task_label_task');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->latest('created_at');
    }

    public function progressUpdates(): HasMany
    {
        return $this->hasMany(TaskProgressUpdate::class)->latest('created_at');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(TaskReview::class);
    }

    public function pendingReview(): HasOne
    {
        return $this->hasOne(TaskReview::class)
            ->where('status', TaskReviewStatus::Pending->value)
            ->latestOfMany();
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TaskTimeEntry::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(TaskReminder::class);
    }

    // ========================================================
    //  Scopes
    // ========================================================

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn($this->qualifyColumn('status'), TaskStatus::openValues());
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereNotIn($this->qualifyColumn('status'), TaskStatus::openValues());
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull($this->qualifyColumn('due_at'))
            ->where($this->qualifyColumn('due_at'), '<', now());
    }

    public function scopeRootOnly(Builder $query): Builder
    {
        return $query->whereNull($this->qualifyColumn('parent_id'));
    }

    /** مهام مكلّف بيها الموظف (تكليف فعّال). */
    public function scopeAssignedTo(Builder $query, int $employeeId): Builder
    {
        return $query->whereIn($this->qualifyColumn('id'), fn ($sub) => $sub->select('task_id')
            ->from('task_assignees')
            ->where('employee_id', $employeeId)
            ->where('status', TaskAssigneeStatus::Active->value));
    }

    /** مهام مستنية مراجعة الشخص ده بالذات (المراجع المسجّل وقت التسليم). */
    public function scopeAwaitingReviewBy(Builder $query, Model $reviewer): Builder
    {
        return $query->where($this->qualifyColumn('status'), TaskStatus::InReview->value)
            ->whereIn($this->qualifyColumn('id'), fn ($sub) => $sub->select('task_id')
                ->from('task_reviews')
                ->where('status', TaskReviewStatus::Pending->value)
                ->where('reviewer_type', $reviewer::class)
                ->where('reviewer_id', $reviewer->getKey()));
    }

    /**
     * كل المهام اللي الموظف يحق له يشوفها في البوابة:
     * مكلّف بيها، أو هو اللي عملها، أو بيتابعها، أو مكلّف بيها حد من
     * فريقه (مدير مباشر)، أو في مشروع هو عضو فيه أو مديره.
     *
     * كله استعلامات فرعية (whereIn) مش whereHas عشان مانجرّش Global
     * Scopes للموديلات التانية ونفضل على فهارس الجداول الوسيطة.
     */
    public function scopeVisibleToEmployee(Builder $query, Employee $employee): Builder
    {
        $id = $employee->id;

        return $query->where(function (Builder $q) use ($id) {
            $q->whereIn($this->qualifyColumn('id'), fn ($sub) => $sub->select('task_id')
                ->from('task_assignees')
                ->where('employee_id', $id))
                ->orWhere(fn (Builder $c) => $c
                    ->where($this->qualifyColumn('creator_type'), Employee::class)
                    ->where($this->qualifyColumn('creator_id'), $id))
                ->orWhereIn($this->qualifyColumn('id'), fn ($sub) => $sub->select('task_id')
                    ->from('task_watchers')
                    ->where('watcher_type', Employee::class)
                    ->where('watcher_id', $id))
                ->orWhereIn($this->qualifyColumn('id'), fn ($sub) => $sub->select('task_id')
                    ->from('task_assignees')
                    ->whereIn('employee_id', fn ($team) => $team->select('id')
                        ->from('employees')
                        ->where('manager_id', $id)))
                ->orWhereIn($this->qualifyColumn('project_id'), fn ($sub) => $sub->select('id')
                    ->from('projects')
                    ->where('manager_id', $id))
                ->orWhereIn($this->qualifyColumn('project_id'), fn ($sub) => $sub->select('project_id')
                    ->from('project_members')
                    ->where('employee_id', $id));
        });
    }

    // ========================================================
    //  الحالة والمواعيد
    // ========================================================

    public function statusEnum(): TaskStatus
    {
        return TaskStatus::from($this->status);
    }

    public function isOpen(): bool
    {
        return $this->statusEnum()->isOpen();
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_at !== null && $this->due_at->isPast();
    }

    /**
     * في مهمة لازم تخلص قبلها ولسه مفتوحة. يحتاج dependencies محمّلة أو بيستعلم مرة واحدة.
     *
     * المهمة المطلوبة لو اتقفلت بأي شكل (اكتملت أو اتلغت) ماتعطّلش اللي بعدها —
     * وإلا الإلغاء كان بيقفل المهمة المستنية للأبد. المحذوفة مابتتحسبش أصلاً
     * (الـ soft delete scope على العلاقة).
     */
    public function isBlocked(): bool
    {
        $deps = $this->relationLoaded('dependencies')
            ? $this->dependencies
            : $this->dependencies()->get(['tasks.id', 'tasks.status']);

        return $deps->contains(fn (Task $t) => in_array($t->status, TaskStatus::openValues(), true));
    }

    /** مهام فرعية لسه مفتوحة (من غير المحذوفة) — الأم ماتكتملش قبلها. */
    public function hasOpenSubtasks(): bool
    {
        return static::withoutGlobalScopes()
            ->where('parent_id', $this->id)
            ->whereNull('deleted_at')
            ->whereIn('status', TaskStatus::openValues())
            ->exists();
    }

    // ========================================================
    //  نسبة الإنجاز
    // ========================================================

    /**
     * النسبة المستهدفة: المفروض المهمة تكون وصلت كام لحد اللحظة دي،
     * على افتراض إن الشغل موزّع بالتساوي من البداية لموعد التسليم.
     *
     * null لو مفيش بداية أو موعد تسليم (مفيش حاجة نقارن بيها).
     */
    public function expectedProgress(?Carbon $at = null): ?int
    {
        $start = $this->starts_at ?? $this->created_at;

        if ($start === null || $this->due_at === null) {
            return null;
        }

        $at ??= now();

        if ($at->lessThanOrEqualTo($start)) {
            return 0;
        }

        if ($at->greaterThanOrEqualTo($this->due_at)) {
            return 100;
        }

        $total = $start->diffInSeconds($this->due_at);

        return $total <= 0 ? 100 : (int) round($start->diffInSeconds($at) / $total * 100);
    }

    /**
     * مقارنة الإنجاز الفعلي بالمستهدف:
     *  ahead = سابق · on_track = في الميعاد (فرق ١٠٪ مسموح) · behind = متأخر
     */
    public function progressHealth(): ?string
    {
        if (! $this->isOpen()) {
            return null;
        }

        $expected = $this->expectedProgress();

        if ($expected === null) {
            return null;
        }

        $diff = $this->progress - $expected;

        return match (true) {
            $diff >= 10  => 'ahead',
            $diff <= -10 => 'behind',
            default      => 'on_track',
        };
    }

    /** موعد التحديث الدوري الجاي لنسبة الإنجاز (null = مش مطلوب). */
    public function nextProgressUpdateDate(?Carbon $from = null): ?Carbon
    {
        $from ??= now();

        $days = match (TaskProgressInterval::from($this->progress_update_interval)) {
            TaskProgressInterval::None   => null,
            TaskProgressInterval::Daily  => 1,
            TaskProgressInterval::Weekly => 7,
            TaskProgressInterval::Custom => max(1, (int) $this->progress_update_every_days),
        };

        return $days === null ? null : $from->copy()->addDays($days);
    }

    /**
     * إعادة حساب العدادات المخزّنة (وكمان النسبة لو تلقائية).
     *
     * ٤ استعلامات تجميعية بس، مهما كان عدد المهام الفرعية أو الخطوات.
     */
    public function refreshCounters(): void
    {
        $completed = TaskStatus::Completed->value;

        $subtasks = static::withoutGlobalScopes()
            ->where('parent_id', $this->id)
            ->whereNull('deleted_at')
            ->where('status', '!=', TaskStatus::Cancelled->value)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as done', [$completed])
            ->selectRaw('AVG(CASE WHEN status = ? THEN 100 ELSE progress END) as avg_progress', [$completed])
            ->first();

        $checklist = TaskChecklistItem::query()
            ->where('task_id', $this->id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_done = 1 THEN 1 ELSE 0 END) as done')
            ->first();

        $this->subtasks_count            = (int) $subtasks->total;
        $this->completed_subtasks_count  = (int) $subtasks->done;
        $this->checklist_count           = (int) $checklist->total;
        $this->completed_checklist_count = (int) $checklist->done;
        $this->comments_count            = TaskComment::where('task_id', $this->id)->count();
        $this->attachments_count         = TaskAttachment::where('task_id', $this->id)->count();

        $source = TaskProgressSource::from($this->progress_source);

        if ($source === TaskProgressSource::Checklist && $this->checklist_count > 0) {
            $this->progress = (int) round($this->completed_checklist_count / $this->checklist_count * 100);
        } elseif ($source === TaskProgressSource::Subtasks && $this->subtasks_count > 0) {
            $this->progress = (int) round((float) $subtasks->avg_progress);
        }

        $this->saveQuietly();
    }

    // ========================================================
    //  المراجعة
    // ========================================================

    /**
     * مين يراجع المهمة لما تتسلّم:
     *  - اللي أنشأها هو المراجع.
     *  - لو موظف أنشأها لنفسه بس (هو المكلّف الوحيد) ← مديره المباشر.
     *  - لو مفيش مدير مباشر ← null (المهمة بتتقفل من غير مراجعة).
     *
     * يحتاج creator و assignments (أو assignees) محمّلين أو بيستعلم.
     */
    public function resolveReviewer(): ?Model
    {
        $creator = $this->creator;

        if (! $creator instanceof Employee) {
            return $creator;
        }

        $assigneeIds = $this->relationLoaded('assignments')
            ? $this->assignments->where('status', TaskAssigneeStatus::Active->value)->pluck('employee_id')->map(fn ($i) => (int) $i)->values()
            : $this->assignees->pluck('id');
        $selfOnly = $assigneeIds->isEmpty() || $assigneeIds->all() === [$creator->id];

        return $selfOnly ? $creator->manager : $creator;
    }
}
