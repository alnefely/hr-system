<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * تصنيف للمهام (وسم ملوّن).
 *
 * branch_id = null ← تصنيف عام لكل الفروع، فمفيش ScopedToBranch؛
 * الفلترة بـ scopeAvailableFor.
 */
class TaskLabel extends Model
{
    use SoftDeletes;

    protected $fillable = ['branch_id', 'name', 'color'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_label_task');
    }

    public function scopeAvailableFor(Builder $query, ?int $branchId): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('branch_id')
            ->when($branchId, fn (Builder $q) => $q->orWhere('branch_id', $branchId)));
    }
}
