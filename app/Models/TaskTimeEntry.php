<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** فترة شغل على مهمة (عدّاد أو إدخال يدوي). ended_at = null يعني العدّاد شغّال. */
class TaskTimeEntry extends Model
{
    protected $fillable = ['task_id', 'employee_id', 'started_at', 'ended_at', 'minutes', 'is_manual', 'note'];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
        'minutes'    => 'integer',
        'is_manual'  => 'boolean',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    public function isRunning(): bool
    {
        return $this->ended_at === null;
    }
}
