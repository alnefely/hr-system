@extends('layouts.admin.app')
@section('title', __('task.views.board_title'))

@section('content')
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
        <div>
            <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                <i class="fa-solid fa-table-columns text-primary"></i>{{ __('task.views.board_title') }}
                @if (config('broadcasting.default') === 'pusher' && config('broadcasting.connections.pusher.key'))
                    <span class="badge bg-label-success d-inline-flex align-items-center gap-1" title="{{ __('task.views.live_hint') }}">
                        <span class="live-dot" id="liveDot"></span>{{ __('task.views.live') }}
                    </span>
                @endif
            </h5>
            <small class="text-body-secondary">{{ __('task.views.drag_hint') }} <x-tasks.help key="board" /></small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @include('tasks.partials.view-switcher', ['routes' => 'admin.tasks', 'current' => 'board', 'query' => $filters])
            @if (admin_can('task_create'))
                <a href="{{ route('admin.tasks.create', array_filter(['project_id' => $filters['project_id'] ?? null])) }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>{{ __('task.actions.add_new') }}</a>
            @endif
        </div>
    </div>
    <div class="card-body pt-3">
        @include('tasks.partials.view-filters', ['action' => route('admin.tasks.board')])
    </div>
</div>

@include('tasks.partials.board', [
    'routes'  => 'admin.tasks',
    'moveUrl' => route('admin.tasks.move', '__ID__'),
    'live'    => ['channel' => $channel, 'cardUrl' => route('admin.tasks.board.card', '__ID__'), 'authUrl' => route('admin.broadcasting.auth')],
])
@endsection
