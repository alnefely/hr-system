<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * مرفق على مهمة أو تعليق.
 *
 * الملفات بتتخزن على disk خاص (مش public) وبتتنزل من مسار بيتحقق من
 * صلاحية الوصول للمهمة — مفيش رابط مباشر للملف.
 */
class TaskAttachment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'task_id', 'comment_id', 'uploader_type', 'uploader_id',
        'disk', 'path', 'original_name', 'mime_type', 'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(TaskComment::class, 'comment_id');
    }

    public function uploader(): MorphTo
    {
        return $this->morphTo();
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
