@extends('layouts.admin.app')
@section('title', __('task.title'))



@section('content')
@include('tasks.partials.styles')
@php
    $statusFilter = $filters['status'] ?? null;
    $cards = [
        ['open',        'fa-list-check',           'primary', \App\Services\Tasks\TaskFilters::STATUS_OPEN],
        ['in_progress', 'fa-person-digging',       'info',    'in_progress'],
        ['in_review',   'fa-magnifying-glass',     'warning', 'in_review'],
        ['overdue',     'fa-triangle-exclamation', 'danger',  \App\Services\Tasks\TaskFilters::STATUS_OVERDUE],
    ];
    $query = fn (array $extra) => route('admin.tasks.index', array_filter(array_merge($filters, $extra), fn ($v) => $v !== null));
@endphp

{{-- كروت الملخص — الضغط على أي كارت بيفلتر بالحالة دي --}}
<div class="row g-3 mb-4">
    @foreach ($cards as [$key, $icon, $color, $value])
        <div class="col-6 col-lg-3">
            <a href="{{ $query(['status' => $statusFilter === $value ? null : $value]) }}" class="task-stat {{ $statusFilter === $value ? 'active' : '' }}">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="icon bg-label-{{ $color }}"><i class="fa-solid {{ $icon }}"></i></span>
                        <div>
                            <div class="h4 mb-0">{{ number_format($stats[$key]) }}</div>
                            <div class="small text-body-secondary">{{ __('task.stats.' . $key) }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

{{-- الفلاتر --}}
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('admin.tasks.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-6 col-lg-3">
                <label for="f_q" class="form-label fw-bold mb-1">{{ __('task.filters.search') }}</label>
                <input type="search" name="q" id="f_q" class="form-control" value="{{ $filters['q'] ?? '' }}">
            </div>
            <div class="col-md-6 col-lg-3">
                <label for="f_project" class="form-label fw-bold mb-1">{{ __('task.fields.project') }}</label>
                <select name="project_id" id="f_project" class="form-select select2" onchange="this.form.submit()">
                    <option value="">{{ __('task.filters.all_projects') }}</option>
                    <option value="none" @selected(($filters['project_id'] ?? null) === 'none')>{{ __('task.filters.no_project') }}</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) ($filters['project_id'] ?? '') === (string) $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label for="f_employee" class="form-label fw-bold mb-1">{{ __('task.fields.assignees') }} <x-inputs.help :text="__('task.hints.employee_filter')" /></label>
                <select name="employee_id" id="f_employee" class="form-select select2" onchange="this.form.submit()">
                    <option value="">{{ __('task.filters.all_employees') }}</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((string) ($filters['employee_id'] ?? '') === (string) $employee->id)>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label for="f_status" class="form-label fw-bold mb-1">{{ __('task.fields.status') }}</label>
                <select name="status" id="f_status" class="form-select select2" onchange="this.form.submit()">
                    <option value="">{{ __('task.filters.all_statuses') }}</option>
                    <option value="open" @selected($statusFilter === 'open')>{{ __('task.filters.open') }}</option>
                    <option value="overdue" @selected($statusFilter === 'overdue')>{{ __('task.filters.overdue') }}</option>
                    @foreach (\App\Enums\TaskStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label for="f_priority" class="form-label fw-bold mb-1">{{ __('task.fields.priority') }}</label>
                <select name="priority" id="f_priority" class="form-select select2" onchange="this.form.submit()">
                    <option value="">{{ __('task.filters.all_priorities') }}</option>
                    @foreach (\App\Enums\TaskPriority::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['priority'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <label for="f_due_from" class="form-label fw-bold mb-1">{{ __('task.filters.due_from') }}</label>
                <input type="date" name="due_from" id="f_due_from" class="form-control" value="{{ $filters['due_from'] ?? '' }}">
            </div>
            <div class="col-6 col-lg-3">
                <label for="f_due_to" class="form-label fw-bold mb-1">{{ __('task.filters.due_to') }}</label>
                <input type="date" name="due_to" id="f_due_to" class="form-control" value="{{ $filters['due_to'] ?? '' }}">
            </div>
            <div class="col-6 col-lg-3">
                <button type="submit" class="btn btn-primary w-100 h-38"><i class="fa-solid fa-filter me-1"></i>{{ __('task.filters.apply') }}</button>
            </div>
            <div class="col-6 col-lg-3">
                <a href="{{ route('admin.tasks.index') }}" class="btn btn-label-secondary w-100 d-flex align-items-center justify-content-center h-38">
                    <i class="fa-solid fa-rotate-left me-1"></i> {{ __('global.reset_filter') }}
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
        <h5 class="card-title mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i>{{ __('task.title') }} <x-tasks.help key="list" /></h5>
        <div class="d-flex gap-2 flex-wrap">
            @include('tasks.partials.view-switcher', ['routes' => 'admin.tasks', 'current' => 'index', 'query' => array_diff_key($filters, ['status' => 1, 'review' => 1]), 'canBoard' => admin_can('task_board')])
            @if ($reviewCount > 0 || ($filters['review'] ?? null) === 'mine')
                <a href="{{ ($filters['review'] ?? null) === 'mine' ? route('admin.tasks.index') : route('admin.tasks.index', ['review' => 'mine']) }}"
                   class="btn {{ ($filters['review'] ?? null) === 'mine' ? 'btn-warning' : 'btn-label-warning' }}">
                    <i class="fa-solid fa-clipboard-check me-1"></i>{{ __('task.review.awaiting_me') }}
                    @if ($reviewCount > 0)<span class="badge bg-warning text-dark ms-1">{{ $reviewCount }}</span>@endif
                </a>
            @endif
            @if (admin_can('task_trash'))
                <a href="{{ route('admin.tasks.trash') }}" class="btn btn-label-danger">
                    <i class="fa-solid fa-trash me-1"></i>{{ __('task.trash') }}
                    @if ($trashCount > 0)<span class="badge bg-danger ms-1">{{ $trashCount }}</span>@endif
                </a>
            @endif
            @if (admin_can('task_create'))
                <a href="{{ route('admin.tasks.create', array_filter(['project_id' => is_numeric($filters['project_id'] ?? null) ? $filters['project_id'] : null])) }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-1"></i>{{ __('task.actions.add_new') }}
                </a>
            @endif
        </div>
    </div>
    <div class="card-body pt-4">
        @include('admin.tasks.partials.table', ['url' => route('admin.tasks.index', $filters)])
    </div>
</div>
@endsection
