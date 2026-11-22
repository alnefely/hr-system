@extends('layouts.admin.app')
@section('title', __('project.templates'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
        <h5 class="card-title mb-0"><i class="fa-solid fa-clone text-primary me-2"></i>{{ __('project.templates') }}</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.projects.index') }}" class="btn btn-label-secondary"><i class="fa-solid fa-arrow-right icon-back me-1"></i>{{ __('project.actions.back') }}</a>
            <a href="{{ route('admin.project-templates.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>{{ __('project.actions.new_template') }}</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('project.fields.template_name') }}</th>
                    <th>{{ __('project.fields.tasks_count') }}</th>
                    <th>{{ __('project.fields.branch') }}</th>
                    <th>{{ __('project.title') }}</th>
                    <th>{{ __('project.fields.is_active') }}</th>
                    <th class="text-end">{{ __('global.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($templates as $template)
                    <tr>
                        <td class="fw-semibold">{{ $template->name }}</td>
                        <td>{{ count($template->structure ?? []) }}</td>
                        <td>{{ $template->branch?->name ?? __('project.fields.template_global') }}</td>
                        <td>{{ $template->projects_count }}</td>
                        <td>{!! $template->is_active ? '<i class="fa-solid fa-check text-success"></i>' : '<i class="fa-solid fa-minus text-body-secondary"></i>' !!}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.project-templates.edit', $template->id) }}" class="btn btn-sm btn-icon btn-label-primary" aria-label="{{ __('global.edit') }}" title="{{ __('global.edit') }}"><i class="fa-solid fa-pen-to-square"></i></a>
                            <form action="{{ route('admin.project-templates.destroy', $template->id) }}" method="POST" class="d-inline" data-confirm-text="{{ $template->name }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-icon btn-label-danger" aria-label="{{ __('global.delete') }}" title="{{ __('global.delete') }}"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-body-secondary py-4">{{ __('project.labels_text.no_templates') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
