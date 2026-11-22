<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * تحديث نسبة إنجاز: "وصلت ٤٠٪ — خلصت التصميم".
 *
 * expected_progress = النسبة المستهدفة وقت التحديث، عشان تقرير المتابعة
 * يبين الموظف كان سابق ولا متأخر في كل تحديث.
 */
class TaskProgressUpdate extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'task_id', 'author_type', 'author_id',
        'previous_progress', 'progress', 'expected_progress', 'note', 'created_at',
    ];

    protected $casts = [
        'previous_progress' => 'integer',
        'progress'          => 'integer',
        'expected_progress' => 'integer',
        'created_at'        => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function author(): MorphTo
    {
        return $this->morphTo();
    }
}
