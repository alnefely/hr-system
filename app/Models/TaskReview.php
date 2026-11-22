<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** تسليم مهمة للمراجعة وقرار المراجع. */
class TaskReview extends Model
{
    protected $fillable = [
        'task_id', 'submitter_type', 'submitter_id', 'submission_note',
        'reviewer_type', 'reviewer_id', 'status', 'decision_note', 'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function submitter(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewer(): MorphTo
    {
        return $this->morphTo();
    }
}
