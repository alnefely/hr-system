<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** حدث في الخط الزمني للمهمة (اتكلّف فلان، الحالة اتغيّرت...). سجل للإضافة بس. */
class TaskActivity extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['task_id', 'actor_type', 'actor_id', 'event', 'properties', 'created_at'];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
