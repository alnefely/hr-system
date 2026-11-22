@extends('layouts.admin.app')
@section('title', __('task.reports.title'))

@section('content')
@include('tasks.partials.styles')
@php
    $query = array_filter(['period' => $period['preset'], 'from' => $period['preset'] === 'custom' ? $period['from']->toDateString() : null,
        'to' => $period['preset'] === 'custom' ? $period['to']->toDateString() : null, 'project_id' => $projectId]);
    $projectSelect = '<div class="col-6 col-md-3"><label for="rp_project" class="form-label small mb-1">' . e(__('task.fields.project')) . '</label>'
        . '<select name="project_id" id="rp_project" class="form-select" onchange="this.form.submit()"><option value="">' . e(__('task.reports.all_projects')) . '</option>'
        . $projectOptions->map(fn ($p) => '<option value="' . $p->id . '"' . ($projectId === $p->id ? ' selected' : '') . '>' . e($p->name) . '</option>')->implode('')
        . '</select></div>';
@endphp

<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-chart-column text-primary me-2"></i>{{ __('task.reports.title') }}</h5>
    </div>
    <div class="card-body pt-3">
        @include('tasks.partials.reports.period-filter', ['action' => route('admin.tasks.reports'), 'slotHtml' => $projectSelect])
    </div>
</div>

@include('tasks.partials.reports.kpis')

<div class="row g-4 mb-4">
    <div class="col-lg-8">@include('tasks.partials.reports.trend', ['chartId' => 'tasksTrendChart'])</div>
    <div class="col-lg-4">@include('tasks.partials.reports.breakdown')</div>
</div>

@include('tasks.partials.reports.employees', [
    'title'        => __('task.reports.employees_title'),
    'exportUrl'    => route('admin.tasks.reports.export', $query),
    'employeeLink' => fn ($e) => route('admin.tasks.index', ['employee_id' => $e->id, 'status' => 'open']),
])

<div class="row g-4">
    @if ($projects->isNotEmpty())
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-solid fa-diagram-project text-primary me-2"></i>{{ __('task.reports.projects') }}</h6></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead><tr>
                            <th>{{ __('project.fields.name') }}</th>
                            <th style="min-width: 9rem">{{ __('project.fields.progress') }}</th>
                            <th class="text-center">{{ __('task.reports.overdue') }}</th>
                            <th>{{ __('project.fields.due_date') }}</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($projects as $p)
                                <tr>
                                    <td><a href="{{ route('admin.projects.show', $p->id) }}" class="fw-semibold">{{ $p->name }}</a>
                                        <div class="task-meta">{{ $p->manager?->name }}</div></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="task-progress flex-grow-1"><div class="bar bg-primary" style="width: {{ $p->progress }}%"></div></div>
                                            <span class="small fw-semibold" dir="ltr">{{ $p->progress }}%</span>
                                        </div>
                                        <div class="task-meta">{{ __('project.labels_text.tasks_done', ['done' => $p->completed_tasks_count, 'total' => $p->tasks_count]) }}</div>
                                    </td>
                                    <td class="text-center {{ $p->overdue_count ? 'text-danger fw-bold' : '' }}">{{ $p->overdue_count }}</td>
                                    <td class="{{ $p->isOverdue() ? 'text-danger' : '' }}" dir="ltr">{{ $p->due_date?->format('Y-m-d') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
    <div class="{{ $projects->isNotEmpty() ? 'col-lg-5' : 'col-12' }}">
        @include('tasks.partials.reports.overdue', ['routes' => 'admin.tasks'])
    </div>
</div>
@endsection
