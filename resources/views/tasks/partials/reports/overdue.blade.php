{{-- أكتر المهام تأخيراً. $overdue, $routes --}}
<div class="card h-100">
    <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>{{ __('task.reports.most_overdue') }}</h6></div>
    <div class="list-group list-group-flush">
        @forelse ($overdue as $task)
            <a href="{{ route($routes . '.show', $task->id) }}" class="list-group-item list-group-item-action d-flex flex-wrap align-items-center gap-2">
                <div class="flex-grow-1" style="min-width: 10rem">
                    <div class="fw-semibold text-break">{{ $task->title }}</div>
                    <div class="task-meta">{{ $task->project?->name }}</div>
                </div>
                @include('tasks.partials.avatars', ['employees' => $task->assignees, 'max' => 3])
                <span class="badge bg-label-danger">{{ __('task.reports.days_late', ['days' => max(1, (int) ceil($task->due_at->diffInHours(now()) / 24))]) }}</span>
            </a>
        @empty
            <div class="list-group-item text-body-secondary small">{{ __('task.reports.no_data') }}</div>
        @endforelse
    </div>
</div>
