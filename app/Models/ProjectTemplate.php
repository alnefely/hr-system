<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * قالب مشروع: هيكل مهام جاهز يتنسخ.
 *
 * structure (JSON):
 * [
 *   {
 *     "title": "...", "description": "...", "priority": "high",
 *     "start_offset_days": 0, "due_offset_days": 5, "estimated_minutes": 120,
 *     "checklist": ["خطوة ١", "خطوة ٢"],
 *     "subtasks": [ { ...نفس الشكل... } ]
 *   }
 * ]
 *
 * مش عليه ScopedToBranch لأن branch_id = null معناه قالب عام لكل الفروع؛
 * الفلترة بـ scopeAvailableFor.
 */
class ProjectTemplate extends Model
{
    use SoftDeletes, LogsActivity;

    protected string $activityLabelAttribute = 'name';

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'structure',
        'is_active',
        'creator_type',
        'creator_id',
    ];

    protected $casts = [
        'structure' => 'array',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): MorphTo
    {
        return $this->morphTo();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'template_id');
    }

    /** القوالب العامة + قوالب الفرع ده. */
    public function scopeAvailableFor(Builder $query, ?int $branchId): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('branch_id')
                ->when($branchId, fn (Builder $q) => $q->orWhere('branch_id', $branchId)));
    }
}
