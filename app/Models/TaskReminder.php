<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** تذكير مجدول على مهمة — sent_at بيمنع إرساله مرتين. */
class TaskReminder extends Model
{
    protected $fillable = ['task_id', 'type', 'remind_at', 'sent_at', 'creator_type', 'creator_id'];

    protected $casts = [
        'remind_at' => 'datetime',
        'sent_at'   => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function creator(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->whereNull('sent_at')->where('remind_at', '<=', now());
    }
}
