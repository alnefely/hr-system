@extends('layouts.portal.app')
@section('title', __('portal.nav.tasks'))

@section('content')
@include('tasks.partials.styles')
@php
    $tabs = ['assigned' => 'fa-user-check'];
    // تبويب المراجعة بيظهر لما يكون فيه مهام مستنية مراجعتي (أو انا فاتحه)
    if ($reviewCount > 0 || $tab === 'review') {
        $tabs['review'] = 'fa-clipboard-check';
    }
    if ($hasTeam) {
        $tabs['team'] = 'fa-users';
    }
    $tabs += ['created' => 'fa-pen', 'watching' => 'fa-eye'];
    $statusFilter = $filters['status'] ?? 'open';
    $cards = [
        ['open',        'fa-list-check',           'primary', 'open'],
        ['in_progress', 'fa-person-digging',       'info',    'in_progress'],
        ['in_review',   'fa-magnifying-glass',     'warning', 'in_review'],
        ['overdue',     'fa-triangle-exclamation', 'danger',  'overdue'],
    ];
@endphp

{{-- ملخص المهام المكلّف بيها --}}
<div class="row g-3 mb-4">
    @foreach ($cards as [$key, $icon, $color, $value])
        <div class="col-6 col-lg-3">
            <a href="{{ route('portal.tasks.index', ['tab' => 'assigned', 'status' => $value]) }}" class="task-stat {{ $tab === 'assigned' && $statusFilter === $value ? 'active' : '' }}">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="icon bg-label-{{ $color }}"><i class="fa-solid {{ $icon }}"></i></span>
                        <div>
                            <div class="h4 mb-0">{{ $stats[$key] }}</div>
                            <div class="small text-body-secondary">{{ __('task.stats.' . $key) }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
        <ul class="nav nav-pills flex-wrap gap-1" role="tablist">
            @foreach ($tabs as $key => $icon)
                <li class="nav-item">
                    <a class="nav-link text-nowrap {{ $tab === $key ? 'active' : '' }}" href="{{ route('portal.tasks.index', ['tab' => $key]) }}">
                        <i class="fa-solid {{ $icon }} me-1"></i>{{ __('task.tabs.' . $key) }}
                        @if ($key === 'review' && $reviewCount > 0)<span class="badge bg-warning ms-1">{{ $reviewCount }}</span>@endif
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="d-flex gap-2">
            @include('tasks.partials.view-switcher', ['routes' => 'portal.tasks', 'current' => 'index', 'query' => in_array($tab, ['assigned', 'created', 'team'], true) ? ['tab' => $tab] : []])
            <a href="{{ route('portal.tasks.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i><span class="d-none d-sm-inline ms-1">{{ __('task.actions.add_new') }}</span></a>
        </div>
    </div>
    <div class="card-body pt-3">
        <form action="{{ route('portal.tasks.index') }}" method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="col-12 col-md-4">
                <label for="f_q" class="form-label small mb-1">{{ __('task.filters.search') }}</label>
                <input type="search" name="q" id="f_q" class="form-control" value="{{ $filters['q'] ?? '' }}">
            </div>
            <div class="col-6 col-md-2">
                <label for="f_status" class="form-label small mb-1">{{ __('task.fields.status') }}</label>
                <select name="status" id="f_status" class="form-select" onchange="this.form.submit()">
                    <option value="open" @selected($statusFilter === 'open')>{{ __('task.filters.open') }}</option>
                    <option value="overdue" @selected($statusFilter === 'overdue')>{{ __('task.filters.overdue') }}</option>
                    @foreach (\App\Enums\TaskStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="f_priority" class="form-label small mb-1">{{ __('task.fields.priority') }}</label>
                <select name="priority" id="f_priority" class="form-select" onchange="this.form.submit()">
                    <option value="">{{ __('task.filters.all_priorities') }}</option>
                    @foreach (\App\Enums\TaskPriority::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['priority'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if ($projects->isNotEmpty())
                <div class="col-8 col-md-3">
                    <label for="f_project" class="form-label small mb-1">{{ __('task.fields.project') }}</label>
                    <select name="project_id" id="f_project" class="form-select" onchange="this.form.submit()">
                        <option value="">{{ __('task.filters.all_projects') }}</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) ($filters['project_id'] ?? '') === (string) $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-4 col-md-1">
                <button type="submit" class="btn btn-label-primary w-100" aria-label="{{ __('task.filters.apply') }}"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>
    </div>
</div>

@if ($tasks->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fa-solid fa-mug-hot fa-2x text-body-secondary mb-3"></i>
            <h6 class="mb-1">{{ __('task.labels_text.empty') }}</h6>
            <p class="text-body-secondary small mb-0">{{ __('task.labels_text.empty_hint') }}</p>
        </div>
    </div>
@else
    <div class="row g-3">
        @foreach ($tasks as $task)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 task-card position-relative">
                    <div class="card-body d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="d-flex flex-wrap gap-1">
                                {!! \App\Enums\TaskStatus::badgeFor($task->status) !!}
                                {!! \App\Enums\TaskPriority::badgeFor($task->priority) !!}
                            </div>
                            <span class="task-meta" dir="ltr">{{ $task->code }}</span>
                        </div>
                        <a href="{{ route('portal.tasks.show', $task->id) }}" class="stretched-link text-heading fw-semibold text-break">{{ $task->title }}</a>
                        @if ($task->project)
                            <div class="task-meta"><i class="fa-solid fa-diagram-project me-1"></i>{{ $task->project->name }}</div>
                        @endif
                        @if ($task->labels->isNotEmpty())
                            <div class="d-flex flex-wrap gap-1">
                                @foreach ($task->labels as $label)
                                    <span class="task-label" style="background: {{ $label->cssColor() }}">{{ $label->name }}</span>
                                @endforeach
                            </div>
                        @endif
                        <div class="mt-auto pt-2">
                            @include('tasks.partials.progress-bar', ['task' => $task, 'compact' => true])
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-2 small">
                            <div>@include('tasks.partials.due', ['task' => $task])</div>
                            @include('tasks.partials.avatars', ['employees' => $task->assignees, 'max' => 3])
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $tasks->links() }}</div>
@endif
@endsection
