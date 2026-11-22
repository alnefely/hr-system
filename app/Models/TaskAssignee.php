<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** تكليف موظف بمهمة (جدول وسيط بموديل عشان حالة الاعتذار وسببه). */
class TaskAssignee extends Pivot
{
    protected $table = 'task_assignees';

    public $incrementing = true;

    protected $fillable = [
        'task_id',
        'employee_id',
        'status',
        'decline_reason',
        'responded_at',
        'assigned_by_type',
        'assigned_by_id',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignedBy(): MorphTo
    {
        return $this->morphTo();
    }
}
