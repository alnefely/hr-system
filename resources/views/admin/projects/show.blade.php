@extends('layouts.admin.app')
@section('title', $project->name)

@section('content')
@include('tasks.partials.styles')
@php
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $project->color) ? $project->color : 'var(--bs-primary)';
    $cards = [
        ['total',     'fa-list-check',           'primary', ['project_id' => $project->id, 'status' => null]],
        ['open',      'fa-person-digging',       'info',    ['project_id' => $project->id, 'status' => 'open']],
        ['completed', 'fa-circle-check',         'success', ['project_id' => $project->id, 'status' => 'completed']],
        ['overdue',   'fa-triangle-exclamation', 'danger',  ['project_id' => $project->id, 'status' => 'overdue']],
    ];
@endphp

<div class="card mb-4" style="border-top: 4px solid {{ $color }}">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div class="flex-grow-1" style="min-width: 0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge bg-label-secondary" dir="ltr">{{ $project->code }}</span>
                    {!! \App\Enums\ProjectStatus::badgeFor($project->status) !!}
                    {!! \App\Enums\TaskPriority::badgeFor($project->priority) !!}
                </div>
                <h4 class="mb-1 text-break">{{ $project->name }}</h4>
                <div class="task-meta d-flex flex-wrap gap-3">
                    <span><i class="fa-regular fa-user me-1"></i>{{ $project->manager?->name ?? __('project.labels_text.no_manager') }}</span>
                    @if ($project->branch)<span><i class="fa-solid fa-building me-1"></i>{{ $project->branch->name }}</span>@endif
                    @if ($project->start_date || $project->due_date)
                        <span dir="ltr"><i class="fa-regular fa-calendar me-1"></i>{{ $project->start_date?->format('Y-m-d') ?? '—' }} → {{ $project->due_date?->format('Y-m-d') ?? '—' }}</span>
                    @endif
                    @if ($project->template)<span><i class="fa-solid fa-clone me-1"></i>{{ $project->template->name }}</span>@endif
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($abilities['add_tasks'])
                    <a href="{{ route('admin.tasks.create', ['project_id' => $project->id]) }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>{{ __('project.actions.add_task') }}</a>
                @endif
                @if ($abilities['update'])
                    <a href="{{ route('admin.projects.edit', $project->id) }}" class="btn btn-label-primary"><i class="fa-solid fa-pen-to-square me-1"></i>{{ __('project.actions.edit') }}</a>
                @endif
                @if ($abilities['template'])
                    <button type="button" class="btn btn-label-secondary" data-bs-toggle="modal" data-bs-target="#saveTemplateModal"><i class="fa-solid fa-clone me-1"></i>{{ __('project.actions.save_as_template') }}</button>
                @endif
                @if ($abilities['delete'])
                    <form action="{{ route('admin.projects.destroy', $project->id) }}" method="POST" class="d-inline" data-confirm-text="{{ $project->name }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-label-danger"><i class="fa-solid fa-trash me-1"></i>{{ __('project.actions.delete') }}</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="mt-3 pt-3 border-top">
            <div class="d-flex justify-content-between small mb-1">
                <span class="fw-semibold">{{ __('project.fields.progress') }}</span>
                <strong dir="ltr">{{ $project->progress }}%</strong>
            </div>
            <div class="task-progress task-progress-lg"><div class="bar {{ $project->status === 'completed' ? 'bg-success' : 'bg-primary' }}" style="width: {{ $project->progress }}%"></div></div>
        </div>

        @if ($project->description)
            <div class="task-description mt-3 text-body">{{ $project->description }}</div>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach ($cards as [$key, $icon, $tone, $query])
        <div class="col-6 col-lg-3">
            <a href="{{ route('admin.tasks.index', array_filter($query)) }}" class="task-stat">
                <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                    <span class="icon bg-label-{{ $tone }}"><i class="fa-solid {{ $icon }}"></i></span>
                    <div><div class="h4 mb-0">{{ $stats[$key] }}</div><div class="small text-body-secondary">{{ __('project.stats.' . $key) }}</div></div>
                </div></div>
            </a>
        </div>
    @endforeach
</div>

{{-- الأعضاء سطر واحد فوق الجدول — الجدول محتاج العرض كله عشان أعمدته --}}
<div class="card mb-4">
    <div class="card-body d-flex flex-wrap align-items-center gap-2">
        <span class="fw-bold me-2"><i class="fa-solid fa-users text-primary me-1"></i>{{ __('project.fields.members') }}
            <span class="badge bg-label-secondary ms-1">{{ $project->members->count() }}</span></span>
        @forelse ($project->members->sortBy(fn ($m) => ['manager' => 0, 'member' => 1, 'viewer' => 2][$m->pivot->role] ?? 3) as $member)
            <span class="badge rounded-pill bg-label-{{ ['manager' => 'primary', 'member' => 'info', 'viewer' => 'secondary'][$member->pivot->role] ?? 'secondary' }} fw-normal py-2 px-3"
                  title="{{ \App\Enums\ProjectMemberRole::tryFrom($member->pivot->role)?->label() }}">{{ $member->name }}</span>
        @empty
            <span class="text-body-secondary small">{{ __('project.labels_text.no_members') }}</span>
        @endforelse
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom">
        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-list-check text-primary me-2"></i>{{ __('project.fields.tasks') }}</h6>
        <a href="{{ route('admin.tasks.index', ['project_id' => $project->id]) }}" class="btn btn-sm btn-label-primary">{{ __('project.actions.all_tasks') }}</a>
    </div>
    <div class="card-body pt-3">
        @include('admin.tasks.partials.table', ['url' => route('admin.tasks.index', ['project_id' => $project->id])])
    </div>
</div>

@if ($abilities['template'])
    <div class="modal fade" id="saveTemplateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.projects.save-template', $project->id) }}" method="POST" data-no-confirm>
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-clone text-primary me-2"></i>{{ __('project.actions.save_as_template') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('global.cancel') }}"></button>
                    </div>
                    <div class="modal-body">
                        <label for="template_name" class="form-label">{{ __('project.fields.template_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="template_name" class="form-control" maxlength="191" required value="{{ $project->name }}">
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>{{ __('global.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
