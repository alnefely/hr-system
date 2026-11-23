@extends('layouts.admin.app')
@section('title', __('project.title'))

@section('content')
@include('tasks.partials.styles')

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
        <h5 class="card-title mb-0"><i class="fa-solid fa-diagram-project text-primary me-2"></i>{{ __('project.title') }} <x-tasks.help key="projects" /></h5>
        <div class="d-flex gap-2 flex-wrap">
            @if (admin_can('project_template_manage'))
                <a href="{{ route('admin.project-templates.index') }}" class="btn btn-label-secondary"><i class="fa-solid fa-clone me-1"></i>{{ __('project.templates') }}</a>
            @endif
            @if (admin_can('project_trash'))
                <a href="{{ route('admin.projects.trash') }}" class="btn btn-label-danger">
                    <i class="fa-solid fa-trash me-1"></i>{{ __('project.trash') }}
                    @if ($trashCount > 0)<span class="badge bg-danger ms-1">{{ $trashCount }}</span>@endif
                </a>
            @endif
            @if (admin_can('project_create'))
                <a href="{{ route('admin.projects.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>{{ __('project.actions.add_new') }}</a>
            @endif
        </div>
    </div>
    <div class="card-body pt-3">
        <form action="{{ route('admin.projects.index') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label for="f_q" class="form-label small mb-1">{{ __('project.filters.search') }}</label>
                <input type="search" name="q" id="f_q" class="form-control" value="{{ request('q') }}">
            </div>
            <div class="col-6 col-md-3">
                <label for="f_status" class="form-label small mb-1">{{ __('project.fields.status') }}</label>
                <select name="status" id="f_status" class="form-select" onchange="this.form.submit()">
                    <option value="ongoing" @selected($status === 'ongoing')>{{ __('project.filters.active_only') }}</option>
                    <option value="all" @selected($status === 'all')>{{ __('project.filters.all_statuses') }}</option>
                    @foreach (\App\Enums\ProjectStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label for="f_manager" class="form-label small mb-1">{{ __('project.fields.manager') }}</label>
                <select name="manager_id" id="f_manager" class="form-select" onchange="this.form.submit()">
                    <option value="">{{ __('project.filters.all_managers') }}</option>
                    @foreach ($managers as $manager)
                        <option value="{{ $manager->id }}" @selected((string) request('manager_id') === (string) $manager->id)>{{ $manager->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-label-primary w-100"><i class="fa-solid fa-magnifying-glass me-1"></i>{{ __('task.filters.apply') }}</button>
            </div>
        </form>
    </div>
</div>

@if ($projects->isEmpty())
    <div class="card"><div class="card-body text-center py-5">
        <i class="fa-solid fa-diagram-project fa-2x text-body-secondary mb-3"></i>
        <h6 class="mb-0">{{ __('project.labels_text.empty') }}</h6>
    </div></div>
@else
    <div class="row g-3">
        @foreach ($projects as $project)
            <div class="col-12 col-md-6 col-xl-4">
                @include('projects.partials.card', ['url' => route('admin.projects.show', $project->id)])
            </div>
        @endforeach
    </div>
    <div class="mt-4 d-flex justify-content-center">{{ $projects->links() }}</div>
@endif
@endsection
