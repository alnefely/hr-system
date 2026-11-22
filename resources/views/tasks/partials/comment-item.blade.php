{{-- تعليق واحد (أو رد). $comment, $routes, $task, $actor, $abilities, $isReply --}}
@php
    $isReply ??= false;
    $author = $comment->author;
    $mine = $author && $author::class === $actor::class && $author->getKey() === $actor->getKey();
    $initial = mb_substr(trim($author?->name ?? '?'), 0, 1);
@endphp
<div class="d-flex gap-2 {{ $isReply ? 'mt-3' : '' }}" id="comment-{{ $comment->id }}">
    <div class="avatar avatar-sm flex-shrink-0">
        <span class="avatar-initial rounded-circle {{ $author instanceof \App\Models\Admin ? 'bg-label-primary' : 'bg-label-info' }}">{{ $initial }}</span>
    </div>
    <div class="flex-grow-1" style="min-width: 0">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <strong class="small">{{ $mine ? __('task.labels_text.you') : ($author?->name ?? '—') }}</strong>
            <small class="text-body-secondary" title="{{ $comment->created_at->format('Y-m-d H:i') }}">{{ $comment->created_at->diffForHumans() }}</small>
            @if ($comment->edited_at)
                <small class="text-body-secondary fst-italic">({{ __('task.comments.edited') }})</small>
            @endif
        </div>

        @if ($comment->body !== '')
            <div class="comment-body text-break mt-1" data-body>{!! nl2br(e($comment->body)) !!}</div>
        @endif

        @if ($mine)
            <form action="{{ route($routes . '.comments.update', [$task->id, $comment->id]) }}" method="POST" class="d-none mt-2" data-edit-form data-no-confirm>
                @csrf @method('PUT')
                <textarea name="body" class="form-control form-control-sm" rows="2" maxlength="5000" required aria-label="{{ __('task.comments.edit') }}">{{ $comment->body }}</textarea>
                <div class="d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('task.comments.save') }}</button>
                    <button type="button" class="btn btn-sm btn-label-secondary" data-edit-cancel>{{ __('task.comments.cancel') }}</button>
                </div>
            </form>
        @endif

        @if ($comment->attachments->isNotEmpty())
            <div class="d-flex flex-wrap gap-2 mt-2">
                @foreach ($comment->attachments as $file)
                    @include('tasks.partials.attachment-chip', ['file' => $file])
                @endforeach
            </div>
        @endif

        <div class="d-flex gap-3 mt-1 small">
            @unless ($isReply)
                <a href="#comment-form" class="text-body-secondary" data-reply="{{ $comment->id }}" data-reply-name="{{ $author?->name }}"><i class="fa-solid fa-reply me-1"></i>{{ __('task.comments.reply') }}</a>
            @endunless
            @if ($mine)
                <a href="#" class="text-body-secondary" data-edit><i class="fa-solid fa-pen me-1"></i>{{ __('task.comments.edit') }}</a>
            @endif
            @if ($mine || $abilities['update'])
                <form action="{{ route($routes . '.comments.destroy', [$task->id, $comment->id]) }}" method="POST" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-link p-0 small text-danger text-decoration-none"><i class="fa-solid fa-trash me-1"></i>{{ __('task.comments.delete') }}</button>
                </form>
            @endif
        </div>

        @unless ($isReply)
            @foreach ($comment->replies as $reply)
                @include('tasks.partials.comment-item', ['comment' => $reply, 'isReply' => true])
            @endforeach
        @endunless
    </div>
</div>
