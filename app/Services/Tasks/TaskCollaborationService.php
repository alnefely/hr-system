<?php

namespace App\Services\Tasks;

use App\Enums\TaskAssigneeStatus;
use App\Events\Tasks\TaskCommented;
use App\Exceptions\InvalidStateException;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * التواصل على المهمة: التعليقات (مع الرد والإشارة @) والمرفقات.
 *
 * - أي حد يقدر يشوف المهمة يقدر يعلّق ويرفع ملفات.
 * - التعليق يعدّله صاحبه بس، ويحذفه صاحبه أو صاحب قرار المهمة.
 * - الإشارة مقصورة على الناس المرتبطين بالمهمة (المكلّفين، اللي أنشأها،
 *   المتابعين، أعضاء المشروع) — مانبعتش إشعار لحد مالوش علاقة.
 * - الملفات على disk خاص وبأسماء عشوائية؛ الاسم الأصلي في قاعدة البيانات.
 */
class TaskCollaborationService
{
    public function __construct(
        private TaskAccess $access,
        private TaskService $tasks,
    ) {}

    // ========================================================
    //  التعليقات
    // ========================================================

    /**
     * @param  array<int, int>  $mentionIds
     * @param  array<int, UploadedFile>  $files
     */
    public function addComment(Task $task, string $body, Model $actor, ?int $parentId = null, array $mentionIds = [], array $files = []): TaskComment
    {
        $this->authorize($this->access->canView($task, $actor));

        $body = trim($body);

        if ($body === '' && $files === []) {
            throw new InvalidStateException(__('task.errors.empty_comment'));
        }

        // الرد على تعليق في نفس المهمة بس، ومستوى واحد (رد الرد بيتعلّق على الأصل)
        if ($parentId) {
            $parent = TaskComment::where('task_id', $task->id)->findOrFail($parentId);
            $parentId = $parent->parent_id ?? $parent->id;
        }

        $mentionIds = array_values(array_intersect(
            array_map('intval', $mentionIds),
            $this->mentionableIds($task),
        ));

        $stored = [];

        try {
            $comment = DB::transaction(function () use ($task, $body, $actor, $parentId, $mentionIds, $files, &$stored) {
                $comment = TaskComment::create([
                    'task_id'     => $task->id,
                    'parent_id'   => $parentId,
                    'author_type' => $actor::class,
                    'author_id'   => $actor->getKey(),
                    'body'        => $body,
                    'mentions'    => $mentionIds ?: null,
                ]);

                foreach ($files as $file) {
                    $stored[] = $this->storeFile($task, $file, $actor, $comment->id);
                }

                $this->tasks->record($task, $actor, 'commented', array_filter([
                    'comment_id' => $comment->id,
                    'excerpt'    => Str::limit($body, 120),
                    'files'      => count($files) ?: null,
                ]));

                $task->forceFill(['last_activity_at' => now()])->saveQuietly();
                $task->refreshCounters();

                return $comment;
            });
        } catch (\Throwable $e) {
            $this->deleteFiles($stored);

            throw $e;
        }

        TaskCommented::dispatch($task, $comment, $mentionIds, $actor);

        return $comment;
    }

    public function updateComment(TaskComment $comment, string $body, Model $actor): TaskComment
    {
        $this->authorize($this->isAuthor($comment, $actor));

        $body = trim($body);

        if ($body === '') {
            throw new InvalidStateException(__('task.errors.empty_comment'));
        }

        $comment->update(['body' => $body, 'edited_at' => now()]);

        return $comment;
    }

    public function deleteComment(Task $task, TaskComment $comment, Model $actor): void
    {
        $this->ensureBelongs($task, $comment->task_id);
        $this->authorize($this->isAuthor($comment, $actor) || $this->access->canUpdate($task, $actor));

        DB::transaction(function () use ($task, $comment) {
            // المرفقات اللي في التعليق بتفضل على المهمة (مش بتضيع)
            TaskAttachment::where('comment_id', $comment->id)->update(['comment_id' => null]);
            TaskComment::where('parent_id', $comment->id)->delete();
            $comment->delete();
            $task->refreshCounters();
        });
    }

    // ========================================================
    //  المرفقات
    // ========================================================

    /** @param  array<int, UploadedFile>  $files */
    public function addAttachments(Task $task, array $files, Model $actor): array
    {
        $this->authorize($this->access->canView($task, $actor));

        $stored = [];

        try {
            DB::transaction(function () use ($task, $files, $actor, &$stored) {
                foreach ($files as $file) {
                    $stored[] = $this->storeFile($task, $file, $actor);
                }

                $this->tasks->record($task, $actor, 'attachment_added', [
                    'names' => array_map(fn ($a) => $a->original_name, $stored),
                ]);

                $task->forceFill(['last_activity_at' => now()])->saveQuietly();
                $task->refreshCounters();
            });
        } catch (\Throwable $e) {
            $this->deleteFiles($stored);

            throw $e;
        }

        return $stored;
    }

    public function deleteAttachment(Task $task, TaskAttachment $attachment, Model $actor): void
    {
        $this->ensureBelongs($task, $attachment->task_id);
        $this->authorize($this->isUploader($attachment, $actor) || $this->access->canUpdate($task, $actor));

        DB::transaction(function () use ($task, $attachment, $actor) {
            $name = $attachment->original_name;
            $attachment->delete();
            Storage::disk($attachment->disk)->delete($attachment->path);

            $this->tasks->record($task, $actor, 'attachment_removed', ['names' => [$name]]);
            $task->refreshCounters();
        });
    }

    private function storeFile(Task $task, UploadedFile $file, Model $actor, ?int $commentId = null): TaskAttachment
    {
        $disk = config('tasks.attachments.disk', 'local');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $path = $file->storeAs('tasks/' . $task->id, Str::uuid() . '.' . $extension, $disk);

        if ($path === false) {
            throw new InvalidStateException(__('task.errors.upload_failed'));
        }

        return TaskAttachment::create([
            'task_id'       => $task->id,
            'comment_id'    => $commentId,
            'uploader_type' => $actor::class,
            'uploader_id'   => $actor->getKey(),
            'disk'          => $disk,
            'path'          => $path,
            // الاسم الأصلي للعرض بس — بنشيل أي مسار أو حروف تحكم
            'original_name' => Str::limit(preg_replace('/[\x00-\x1F\x7F\/\\\\]+/u', '', basename($file->getClientOriginalName())) ?: 'file.' . $extension, 250, ''),
            'mime_type'     => $file->getMimeType(),
            'size'          => (int) $file->getSize(),
        ]);
    }

    /** @param  array<int, TaskAttachment>  $attachments */
    private function deleteFiles(array $attachments): void
    {
        foreach ($attachments as $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }
    }

    // ========================================================
    //  الإشارة (@)
    // ========================================================

    /**
     * الموظفين اللي ينفع يتشار لهم في المهمة دي.
     *
     * @return array<int, int>
     */
    public function mentionableIds(Task $task): array
    {
        $ids = $task->assignments()->where('status', TaskAssigneeStatus::Active->value)->pluck('employee_id');

        if ($task->creator_type === Employee::class) {
            $ids->push($task->creator_id);
        }

        $ids = $ids->merge($task->watchers()->where('watcher_type', Employee::class)->pluck('watcher_id'));

        if ($task->project_id) {
            $ids = $ids->merge(DB::table('project_members')->where('project_id', $task->project_id)->pluck('employee_id'))
                ->push(DB::table('projects')->where('id', $task->project_id)->value('manager_id'));
        }

        // المدير المباشر لكل مكلّف
        $ids = $ids->merge(DB::table('employees')->whereIn('id', $ids->filter()->all())->whereNotNull('manager_id')->pluck('manager_id'));

        return $ids->filter()->map(fn ($i) => (int) $i)->unique()->values()->all();
    }

    // ========================================================
    //  مساعدات
    // ========================================================

    private function isAuthor(TaskComment $comment, Model $actor): bool
    {
        return $comment->author_type === $actor::class && (int) $comment->author_id === (int) $actor->getKey();
    }

    private function isUploader(TaskAttachment $attachment, Model $actor): bool
    {
        return $attachment->uploader_type === $actor::class && (int) $attachment->uploader_id === (int) $actor->getKey();
    }

    private function ensureBelongs(Task $task, int $taskId): void
    {
        if ((int) $task->id !== $taskId) {
            throw new InvalidStateException(__('task.errors.forbidden'));
        }
    }

    private function authorize(bool $allowed): void
    {
        if (! $allowed) {
            throw new InvalidStateException(__('task.errors.forbidden'));
        }
    }
}
