<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** خطوة صغيرة جوّه المهمة. */
class TaskChecklistItem extends Model
{
    protected $fillable = ['task_id', 'title', 'is_done', 'done_by_type', 'done_by_id', 'done_at', 'position'];

    protected $casts = [
        'is_done'  => 'boolean',
        'done_at'  => 'datetime',
        'position' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function doneBy(): MorphTo
    {
        return $this->morphTo();
    }
}
