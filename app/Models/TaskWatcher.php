<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** متابع للمهمة (مشرف أو موظف) — بيوصله إشعار بكل جديد من غير ما يكون مكلّف. */
class TaskWatcher extends Model
{
    protected $fillable = ['task_id', 'watcher_type', 'watcher_id'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function watcher(): MorphTo
    {
        return $this->morphTo();
    }
}
