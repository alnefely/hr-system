@extends('layouts.portal.app')
@section('title', __('portal.nav.projects'))

@section('content')
@include('tasks.partials.styles')

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-diagram-project text-primary me-2"></i>{{ __('portal.nav.projects') }} <x-tasks.help key="projects" /></h5>
        <ul class="nav nav-pills gap-1">
            <li class="nav-item"><a class="nav-link {{ $ongoing ? 'active' : '' }}" href="{{ route('portal.projects.index') }}">{{ __('project.filters.active_only') }}</a></li>
            <li class="nav-item"><a class="nav-link {{ $ongoing ? '' : 'active' }}" href="{{ route('portal.projects.index', ['status' => 'all']) }}">{{ __('project.filters.all_statuses') }}</a></li>
        </ul>
    </div>
</div>

@if ($projects->isEmpty())
    <div class="card"><div class="card-body text-center py-5">
        <i class="fa-solid fa-diagram-project fa-2x text-body-secondary mb-3"></i>
        <h6 class="mb-1">{{ __('project.labels_text.empty') }}</h6>
        <p class="text-body-secondary small mb-0">{{ __('project.labels_text.empty_hint') }}</p>
    </div></div>
@else
    <div class="row g-3">
        @foreach ($projects as $project)
            <div class="col-12 col-md-6 col-xl-4">
                @include('projects.partials.card', ['url' => route('portal.projects.show', $project->id)])
            </div>
        @endforeach
    </div>
    <div class="mt-4 d-flex justify-content-center">{{ $projects->links() }}</div>
@endif
@endsection
