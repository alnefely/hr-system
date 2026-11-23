@extends('layouts.portal.app')
@section('title', __('task.reports.my_title'))

@section('content')
@include('tasks.partials.styles')

<div class="card mb-4">
    <div class="card-header border-bottom">
        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-chart-column text-primary me-2"></i>{{ __('task.reports.my_title') }} <x-tasks.help key="performance" /></h5>
    </div>
    <div class="card-body pt-3">
        @include('tasks.partials.reports.period-filter', ['action' => route('portal.tasks.performance')])
    </div>
</div>

@include('tasks.partials.reports.kpis')

<div class="row g-4 mb-4">
    <div class="col-lg-8">@include('tasks.partials.reports.trend', ['chartId' => 'myTrendChart'])</div>
    <div class="col-lg-4">@include('tasks.partials.reports.breakdown')</div>
</div>

@if ($team)
    <h5 class="fw-bold mt-2 mb-3"><i class="fa-solid fa-users text-primary me-2"></i>{{ __('task.reports.team_title') }} <x-tasks.help key="team_performance" /></h5>
    @include('tasks.partials.reports.kpis', ['summary' => $team['summary']])
    @include('tasks.partials.reports.employees', [
        'title'     => __('task.reports.team_title'),
        'employees' => $team['employees'],
    ])
    @include('tasks.partials.reports.overdue', ['overdue' => $team['overdue'], 'routes' => 'portal.tasks'])
@endif
@endsection
