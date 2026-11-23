@extends('layouts.portal.app')
@section('title', __('task.views.calendar_title'))

@section('content')
@php
    $tabs = ['assigned' => 'fa-user-check'] + ($hasTeam ? ['team' => 'fa-users'] : []) + ['created' => 'fa-pen'];
    $query = ['tab' => $tab] + array_diff_key($filters, ['parent' => 1]);
@endphp
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
        <ul class="nav nav-pills flex-wrap gap-1">
            @foreach ($tabs as $key => $icon)
                <li class="nav-item"><a class="nav-link text-nowrap {{ $tab === $key ? 'active' : '' }}" href="{{ route('portal.tasks.calendar', ['tab' => $key, 'month' => request('month')]) }}"><i class="fa-solid {{ $icon }} me-1"></i>{{ __('task.tabs.' . $key) }}</a></li>
            @endforeach
            <li class="nav-item align-self-center ms-1"><x-tasks.help key="tabs" /></li>
        </ul>
        @include('tasks.partials.view-switcher', ['routes' => 'portal.tasks', 'current' => 'calendar', 'query' => $query])
    </div>
</div>

@include('tasks.partials.calendar', ['routes' => 'portal.tasks', 'calendarRoute' => 'portal.tasks.calendar'])
@endsection
