<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\InvalidStateException;
use App\Http\Requests\Tasks\AttachmentRequest;
use App\Http\Requests\Tasks\CommentRequest;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Services\Tasks\TaskCollaborationService;
use App\Services\Tasks\TaskService;
use App\Services\Tasks\TaskTimeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * التعليقات والمرفقات — نفس الإجراءات في لوحة الإدارة والبوابة.
 *
 * كل كنترولر بيحدد:
 *   actor()            مين بيعمل العملية
 *   resolveTask($id)   المهمة لو المستخدم يحق له يشوفها (وإلا 404)
 */
trait HandlesTaskCollaboration
{
    abstract protected function actor(): Model;

    abstract protected function resolveTask(int $id): Task;

    public function storeComment(CommentRequest $request, int $task, TaskCollaborationService $service): RedirectResponse
    {
        $task = $this->resolveTask($task);

        return $this->collaborate(fn () => $service->addComment(
            $task,
            (string) $request->validated('body'),
            $this->actor(),
            $request->validated('parent_id'),
            $request->validated('mentions') ?? [],
            $request->file('files', []),
        ), 'task.messages.comment_added', '#comments');
    }

    public function updateComment(Request $request, int $task, int $comment, TaskCollaborationService $service): RedirectResponse
    {
        $task = $this->resolveTask($task);
        $comment = TaskComment::where('task_id', $task->id)->findOrFail($comment);
        $body = $request->validate(['body' => ['required', 'string', 'max:5000']])['body'];

        return $this->collaborate(fn () => $service->updateComment($comment, $body, $this->actor()), 'task.messages.comment_updated', '#comment-' . $comment->id);
    }

    public function destroyComment(int $task, int $comment, TaskCollaborationService $service): RedirectResponse
    {
        $task = $this->resolveTask($task);
        $comment = TaskComment::where('task_id', $task->id)->findOrFail($comment);

        return $this->collaborate(fn () => $service->deleteComment($task, $comment, $this->actor()), 'task.messages.comment_deleted', '#comments');
    }

    public function storeAttachments(AttachmentRequest $request, int $task, TaskCollaborationService $service): RedirectResponse
    {
        $task = $this->resolveTask($task);

        return $this->collaborate(fn () => $service->addAttachments($task, $request->file('files', []), $this->actor()), 'task.messages.files_uploaded', '#attachments');
    }

    public function destroyAttachment(int $task, int $attachment, TaskCollaborationService $service): RedirectResponse
    {
        $task = $this->resolveTask($task);
        $attachment = TaskAttachment::where('task_id', $task->id)->findOrFail($attachment);

        return $this->collaborate(fn () => $service->deleteAttachment($task, $attachment, $this->actor()), 'task.messages.file_deleted', '#attachments');
    }

    public function storeDependency(Request $request, int $task, TaskService $tasks): RedirectResponse
    {
        $task = $this->resolveTask($task);
        $dependsOn = (int) $request->validate(['depends_on_id' => ['required', 'integer']])['depends_on_id'];

        return $this->collaborate(fn () => $tasks->addDependency($task, $dependsOn, $this->actor()), 'task.messages.dependency_added', '#dependencies');
    }

    public function destroyDependency(int $task, int $dependency, TaskService $tasks): RedirectResponse
    {
        $task = $this->resolveTask($task);

        return $this->collaborate(fn () => $tasks->removeDependency($task, $dependency, $this->actor()), 'task.messages.dependency_removed', '#dependencies');
    }

    // ========================================================
    //  تتبع الوقت
    // ========================================================

    public function startTimer(int $task, TaskTimeService $time): RedirectResponse
    {
        $task = $this->resolveTask($task);
        $actor = $this->actor();

        if (! $actor instanceof \App\Models\Employee) {
            return back()->with('error', __('task.errors.employee_only'));
        }

        return $this->collaborate(fn () => $time->start($task, $actor), 'task.messages.timer_started', '#time');
    }

    public function storeTime(Request $request, int $task, TaskTimeService $time): RedirectResponse
    {
        $task = $this->resolveTask($task);
        $data = $request->validate([
            'hours'       => ['nullable', 'integer', 'min:0', 'max:24'],
            'minutes'     => ['nullable', 'integer', 'min:0', 'max:59'],
            'date'        => ['required', 'date'],
            'note'        => ['nullable', 'string', 'max:500'],
            'employee_id' => ['nullable', 'integer'],
        ]);
        $minutes = (int) ($data['hours'] ?? 0) * 60 + (int) ($data['minutes'] ?? 0);

        return $this->collaborate(fn () => $time->addManual(
            $task, $this->actor(), $minutes, \Illuminate\Support\Carbon::parse($data['date']), $data['note'] ?? null, $data['employee_id'] ?? null,
        ), 'task.messages.time_logged', '#time');
    }

    public function destroyTime(int $task, int $entry, TaskTimeService $time): RedirectResponse
    {
        $task = $this->resolveTask($task);
        $entry = \App\Models\TaskTimeEntry::where('task_id', $task->id)->findOrFail($entry);

        return $this->collaborate(fn () => $time->delete($task, $entry, $this->actor()), 'task.messages.time_deleted', '#time');
    }

    /**
     * تنزيل/عرض مرفق — بعد التحقق إن المستخدم يشوف المهمة.
     * الصور بتتعرض في المتصفح (?inline=1)، وأي نوع تاني بيتنزل دايماً
     * (مانعرضش HTML أو SVG مرفوع جوّه الموقع).
     */
    public function downloadAttachment(Request $request, int $task, int $attachment): StreamedResponse
    {
        $task = $this->resolveTask($task);
        $attachment = TaskAttachment::where('task_id', $task->id)->findOrFail($attachment);
        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 404);

        $inline = $request->boolean('inline') && in_array($attachment->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);

        return $disk->response($attachment->path, $attachment->original_name, [
            'Content-Type'           => $inline ? $attachment->mime_type : 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, max-age=3600',
        ], $inline ? 'inline' : 'attachment');
    }

    private function collaborate(\Closure $action, string $successKey, string $anchor = ''): RedirectResponse
    {
        try {
            $action();
        } catch (InvalidStateException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to(url()->previous() . $anchor)->with('success', __($successKey));
    }
}
