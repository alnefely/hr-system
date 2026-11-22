<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\ScopedToBranch;
use App\Support\CodeSequence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * المشروع: مجموعة مهام وأعضاء تحت مدير واحد.
 *
 * progress و tasks_count و completed_tasks_count مخزّنين وبيتحدّثوا من
 * refreshStats() لما مهمة في المشروع تتغيّر — القوائم بتقراهم مباشرة.
 */
class Project extends Model
{
    use SoftDeletes, ScopedToBranch, LogsActivity;

    public const CODE_PREFIX = 'PRJ-';

    protected string $activityLabelAttribute = 'name';

    protected $fillable = [
        'code',
        'branch_id',
        'name',
        'description',
        'color',
        'manager_id',
        'status',
        'priority',
        'start_date',
        'due_date',
        'completed_at',
        'progress',
        'tasks_count',
        'completed_tasks_count',
        'template_id',
        'creator_type',
        'creator_id',
    ];

    protected $casts = [
        'start_date'            => 'date',
        'due_date'              => 'date',
        'completed_at'          => 'datetime',
        'progress'              => 'integer',
        'tasks_count'           => 'integer',
        'completed_tasks_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            $project->code ??= CodeSequence::next(static::class, 'code', self::CODE_PREFIX);
        });
    }

    // ========================================================
    //  العلاقات
    // ========================================================

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function creator(): MorphTo
    {
        return $this->morphTo();
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'template_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** المهام الرئيسية بس (من غير الفرعية). */
    public function rootTasks(): HasMany
    {
        return $this->hasMany(Task::class)->whereNull('parent_id');
    }

    // ========================================================
    //  Scopes
    // ========================================================

    /** المشاريع اللي الموظف مديرها أو عضو فيها. */
    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where(function (Builder $q) use ($employeeId) {
            $q->where('manager_id', $employeeId)
                ->orWhereIn('id', fn ($sub) => $sub->select('project_id')
                    ->from('project_members')
                    ->where('employee_id', $employeeId));
        });
    }

    // ========================================================
    //  الحسابات
    // ========================================================

    /**
     * إعادة حساب العدادات ونسبة الإنجاز باستعلام تجميعي واحد.
     *
     * النسبة = متوسط نسب المهام الرئيسية غير الملغية (المكتملة = 100).
     */
    public function refreshStats(): void
    {
        $stats = Task::query()
            ->withoutGlobalScopes()
            ->where('project_id', $this->id)
            ->whereNull('parent_id')
            ->whereNull('deleted_at')
            ->where('status', '!=', TaskStatus::Cancelled->value)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as done', [TaskStatus::Completed->value])
            ->selectRaw('AVG(CASE WHEN status = ? THEN 100 ELSE progress END) as avg_progress', [TaskStatus::Completed->value])
            ->first();

        $this->forceFill([
            'tasks_count'           => (int) $stats->total,
            'completed_tasks_count' => (int) $stats->done,
            'progress'              => (int) round((float) $stats->avg_progress),
        ])->saveQuietly();
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->completed_at === null
            && $this->due_date->endOfDay()->isPast();
    }
}
