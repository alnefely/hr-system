{{-- كارت مهمة على لوحة كانبان. $task (project/assignees/labels محمّلين)، $routes --}}
<div class="card task-card board-card mb-2 shadow-none border" data-id="{{ $task->id }}">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
            {!! \App\Enums\TaskPriority::badgeFor($task->priority) !!}
            <span class="task-meta" dir="ltr">{{ $task->code }}</span>
        </div>
        <a href="{{ route($routes . '.show', $task->id) }}" class="d-block fw-semibold text-heading text-break mb-1">{{ $task->title }}</a>
        @if ($task->project)
            <div class="task-meta mb-1"><i class="fa-solid fa-diagram-project me-1"></i>{{ $task->project->name }}</div>
        @endif
        @if ($task->labels->isNotEmpty())
            <div class="d-flex flex-wrap gap-1 mb-2">
                @foreach ($task->labels as $label)
                    <span class="task-label" style="background: {{ $label->cssColor() }}">{{ $label->name }}</span>
                @endforeach
            </div>
        @endif
        @include('tasks.partials.progress-bar', ['task' => $task, 'compact' => true])
        <div class="d-flex justify-content-between align-items-center gap-2 mt-2 small">
            <div>@include('tasks.partials.due', ['task' => $task])</div>
            @include('tasks.partials.avatars', ['employees' => $task->assignees, 'max' => 3])
        </div>
        @if ($task->comments_count || $task->checklist_count || $task->attachments_count)
            <div class="task-meta d-flex gap-3 mt-2">
                @if ($task->checklist_count)<span><i class="fa-regular fa-square-check me-1"></i><span dir="ltr">{{ $task->completed_checklist_count }}/{{ $task->checklist_count }}</span></span>@endif
                @if ($task->comments_count)<span><i class="fa-regular fa-comment me-1"></i>{{ $task->comments_count }}</span>@endif
                @if ($task->attachments_count)<span><i class="fa-solid fa-paperclip me-1"></i>{{ $task->attachments_count }}</span>@endif
            </div>
        @endif
    </div>
</div>
