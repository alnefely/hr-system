@extends('layouts.portal.app')
@section('title', __('task.views.board'))

@section('content')
@php $tabs = ['assigned' => 'fa-user-check'] + ($hasTeam ? ['team' => 'fa-users'] : []) + ['created' => 'fa-pen']; @endphp
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
        <ul class="nav nav-pills flex-wrap gap-1">
            @foreach ($tabs as $key => $icon)
                <li class="nav-item"><a class="nav-link text-nowrap {{ $tab === $key ? 'active' : '' }}" href="{{ route('portal.tasks.board', ['tab' => $key]) }}"><i class="fa-solid {{ $icon }} me-1"></i>{{ __('task.tabs.' . $key) }}</a></li>
            @endforeach
            <li class="nav-item align-self-center ms-1"><x-tasks.help key="tabs" /></li>
        </ul>
        @include('tasks.partials.view-switcher', ['routes' => 'portal.tasks', 'current' => 'board', 'query' => ['tab' => $tab] + $filters])
    </div>
    <div class="card-body pt-3">
        <small class="text-body-secondary d-block mb-2">{{ __('task.views.drag_hint') }} <x-tasks.help key="board" /></small>
        @include('tasks.partials.view-filters', ['action' => route('portal.tasks.board'), 'projects' => collect(), 'hidden' => ['tab' => $tab]])
    </div>
</div>

@include('tasks.partials.board', ['routes' => 'portal.tasks', 'moveUrl' => route('portal.tasks.move', '__ID__')])
@endsection
