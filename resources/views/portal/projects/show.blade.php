@extends('layouts.portal.app')
@section('title', $project->name)

@section('content')
@include('tasks.partials.styles')
@php
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $project->color) ? $project->color : 'var(--bs-primary)';
    $statusFilter = $filters['status'] ?? 'open';
@endphp

<div class="card mb-4" style="border-top: 4px solid {{ $color }}">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div class="flex-grow-1" style="min-width: 0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge bg-label-secondary" dir="ltr">{{ $project->code }}</span>
                    {!! \App\Enums\ProjectStatus::badgeFor($project->status) !!}
                    @if ($role)
                        <span class="badge bg-label-info">{{ __('project.labels_text.your_role') }}: {{ $role->label() }}</span>
                    @endif
                </div>
                <h4 class="mb-1 text-break">{{ $project->name }}</h4>
                <div class="task-meta d-flex flex-wrap gap-3">
                    <span><i class="fa-regular fa-user me-1"></i>{{ $project->manager?->name ?? __('project.labels_text.no_manager') }}</span>
                    @if ($project->start_date || $project->due_date)
                        <span dir="ltr"><i class="fa-regular fa-calendar me-1"></i>{{ $project->start_date?->format('Y-m-d') ?? '—' }} → {{ $project->due_date?->format('Y-m-d') ?? '—' }}</span>
                    @endif
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($abilities['add_tasks'])
                    <a href="{{ route('portal.tasks.create', ['project_id' => $project->id]) }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>{{ __('project.actions.add_task') }}</a>
                @endif
                @if ($abilities['update'])
                    <a href="{{ route('portal.projects.edit', $project->id) }}" class="btn btn-label-primary"><i class="fa-solid fa-pen-to-square me-1"></i>{{ __('project.actions.edit') }}</a>
                @endif
                <a href="{{ route('portal.projects.index') }}" class="btn btn-label-secondary"><i class="fa-solid fa-arrow-right icon-back me-1"></i>{{ __('project.actions.back') }}</a>
            </div>
        </div>
        <div class="mt-3 pt-3 border-top">
            <div class="d-flex justify-content-between small mb-1">
                <span>{{ __('project.labels_text.tasks_done', ['done' => $project->completed_tasks_count, 'total' => $project->tasks_count]) }}</span>
                <strong dir="ltr">{{ $project->progress }}%</strong>
            </div>
            <div class="task-progress task-progress-lg"><div class="bar {{ $project->status === 'completed' ? 'bg-success' : 'bg-primary' }}" style="width: {{ $project->progress }}%"></div></div>
        </div>
        @if ($project->description)
            <div class="task-description mt-3">{{ $project->description }}</div>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8 order-2 order-lg-1">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h6 class="mb-0 fw-bold"><i class="fa-solid fa-list-check text-primary me-2"></i>{{ __('project.fields.tasks') }}</h6>
            <form method="GET" action="{{ route('portal.projects.show', $project->id) }}">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="{{ __('task.fields.status') }}">
                    <option value="open" @selected($statusFilter === 'open')>{{ __('task.filters.open') }}</option>
                    <option value="overdue" @selected($statusFilter === 'overdue')>{{ __('task.filters.overdue') }}</option>
                    @foreach (\App\Enums\TaskStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="card">
            <div class="list-group list-group-flush">
                @forelse ($tasks as $task)
                    <a href="{{ route('portal.tasks.show', $task->id) }}" class="list-group-item list-group-item-action d-flex flex-wrap align-items-center gap-3 py-3">
                        <div class="flex-grow-1" style="min-width: 12rem">
                            <div class="fw-semibold text-break">{{ $task->title }}</div>
                            <div class="d-flex flex-wrap gap-2 mt-1 align-items-center">
                                {!! \App\Enums\TaskStatus::badgeFor($task->status) !!}
                                {!! \App\Enums\TaskPriority::badgeFor($task->priority) !!}
                                <span class="small">@include('tasks.partials.due', ['task' => $task])</span>
                            </div>
                        </div>
                        @include('tasks.partials.avatars', ['employees' => $task->assignees, 'max' => 3])
                        <div style="width: 8rem">@include('tasks.partials.progress-bar', ['task' => $task, 'compact' => true])</div>
                    </a>
                @empty
                    <div class="list-group-item text-center text-body-secondary py-4">{{ __('task.labels_text.empty') }}</div>
                @endforelse
            </div>
        </div>
        <div class="mt-3 d-flex justify-content-center">{{ $tasks->links() }}</div>
    </div>
    <div class="col-lg-4 order-1 order-lg-2">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-users text-primary me-2"></i>{{ __('project.fields.members') }}</h6>
                <span class="badge bg-label-secondary">{{ __('project.labels_text.members_count', ['count' => $project->members->count()]) }}</span>
            </div>
            @include('projects.partials.members')
        </div>
    </div>
</div>
@endsection
