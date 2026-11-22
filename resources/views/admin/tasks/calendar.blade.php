@extends('layouts.admin.app')
@section('title', __('task.views.calendar_title'))

@section('content')
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-calendar-days text-primary me-2"></i>{{ __('task.views.calendar_title') }}</h5>
        @include('tasks.partials.view-switcher', ['routes' => 'admin.tasks', 'current' => 'calendar', 'query' => array_diff_key($filters, ['parent' => 1]), 'canBoard' => admin_can('task_board')])
    </div>
    <div class="card-body pt-3">
        @include('tasks.partials.view-filters', ['action' => route('admin.tasks.calendar'), 'hidden' => ['month' => request('month')]])
    </div>
</div>

@include('tasks.partials.calendar', ['routes' => 'admin.tasks', 'calendarRoute' => 'admin.tasks.calendar', 'query' => array_diff_key($filters, ['parent' => 1])])
@endsection
