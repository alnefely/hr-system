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

    /**
     * ملفات مرفقات المهام دي (والمهام الفرعية تحتها) — قبل الحذف النهائي.
     *
     * الحذف النهائي بيشيل الصفوف بالـ cascade من قاعدة البيانات، فلازم نعرف
     * الملفات قبله ونمسحها بعده (deleteFiles)؛ وإلا بتفضل في storage للأبد.
     *
     * @param  array<int, int>  $taskIds
     * @return array<int, array{disk: string, path: string}>
     */
    public static function filesForTasks(array $taskIds): array
    {
        $ids = $taskIds;
        $frontier = $taskIds;

        // المهام الفرعية (حتى المحذوفة) بتتشال بالـ cascade برضه
        while ($frontier !== []) {
            $frontier = Task::withoutGlobalScopes()->whereIn('parent_id', $frontier)
                ->pluck('id')->map(fn ($id) => (int) $id)->diff($ids)->values()->all();
            $ids = array_merge($ids, $frontier);
        }

        return $ids === [] ? [] : static::withTrashed()->whereIn('task_id', $ids)
            ->get(['disk', 'path'])
            ->map(fn (self $a) => ['disk' => $a->disk, 'path' => $a->path])
            ->all();
    }

    /** @param  array<int, array{disk: string, path: string}>  $files */
    public static function deleteFiles(array $files): void
    {
        foreach ($files as $file) {
            try {
                \Illuminate\Support\Facades\Storage::disk($file['disk'])->delete($file['path']);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Task attachment cleanup failed: ' . $e->getMessage(), $file);
            }
        }
    }
}
