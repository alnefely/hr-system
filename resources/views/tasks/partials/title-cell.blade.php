{{-- خانة العنوان في الجداول: الكود، العنوان، المشروع، التصنيفات، والعدادات. --}}
<div class="task-title-cell">
    <a href="{{ $url }}" class="fw-semibold d-block text-truncate-2">{{ $task->title }}</a>
    <div class="task-meta d-flex flex-wrap align-items-center gap-2 mt-1">
        <span dir="ltr">{{ $task->code }}</span>
        @if ($task->relationLoaded('project') && $task->project)
            <span><i class="fa-solid fa-diagram-project me-1"></i>{{ $task->project->name }}</span>
        @endif
        @if ($task->checklist_count > 0)
            <span title="{{ __('task.labels_text.steps_done', ['done' => $task->completed_checklist_count, 'total' => $task->checklist_count]) }}">
                <i class="fa-regular fa-square-check me-1"></i><span dir="ltr">{{ $task->completed_checklist_count }}/{{ $task->checklist_count }}</span>
            </span>
        @endif
        @if ($task->subtasks_count > 0)
            <span title="{{ __('task.labels_text.subtasks_done', ['done' => $task->completed_subtasks_count, 'total' => $task->subtasks_count]) }}">
                <i class="fa-solid fa-sitemap me-1"></i><span dir="ltr">{{ $task->completed_subtasks_count }}/{{ $task->subtasks_count }}</span>
            </span>
        @endif
        @if ($task->comments_count > 0)
            <span><i class="fa-regular fa-comment me-1"></i>{{ $task->comments_count }}</span>
        @endif
        @if ($task->attachments_count > 0)
            <span><i class="fa-solid fa-paperclip me-1"></i>{{ $task->attachments_count }}</span>
        @endif
    </div>
    @if ($task->relationLoaded('labels') && $task->labels->isNotEmpty())
        <div class="d-flex flex-wrap gap-1 mt-1">
            @foreach ($task->labels as $label)
                <span class="task-label" style="background: {{ $label->cssColor() }}">{{ $label->name }}</span>
            @endforeach
        </div>
    @endif
</div>
