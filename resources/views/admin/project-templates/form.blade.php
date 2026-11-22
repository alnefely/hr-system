@extends('layouts.admin.app')
@section('title', $template->exists ? $template->name : __('project.actions.new_template'))

@section('content')
@php
    $rows = old('tasks') ?? collect($template->structure ?? [])->map(fn ($t) => [
        'title'             => $t['title'] ?? '',
        'priority'          => $t['priority'] ?? 'medium',
        'start_offset_days' => $t['start_offset_days'] ?? null,
        'due_offset_days'   => $t['due_offset_days'] ?? null,
        'checklist'         => implode("\n", $t['checklist'] ?? []),
        'subtasks'          => ! empty($t['subtasks']) ? json_encode($t['subtasks'], JSON_UNESCAPED_UNICODE) : '',
    ])->all();
    $rows = $rows ?: [['title' => '', 'priority' => 'medium', 'start_offset_days' => 0, 'due_offset_days' => null, 'checklist' => '', 'subtasks' => '']];
@endphp

<form action="{{ $template->exists ? route('admin.project-templates.update', $template->id) : route('admin.project-templates.store') }}" method="POST" data-no-confirm>
    @csrf
    @if ($template->exists) @method('PUT') @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
            <h5 class="mb-0 fw-bold"><i class="fa-solid fa-clone text-primary me-2"></i>{{ $template->exists ? $template->name : __('project.actions.new_template') }}</h5>
            <a href="{{ route('admin.project-templates.index') }}" class="btn btn-label-secondary"><i class="fa-solid fa-arrow-right icon-back me-1"></i>{{ __('project.templates') }}</a>
        </div>
        <div class="card-body pt-4">
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="name" class="form-label">{{ __('project.fields.template_name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" maxlength="191" required value="{{ old('name', $template->name) }}">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="is_active" value="1" @checked(old('is_active', $template->is_active))>
                        <label class="form-check-label" for="is_active">{{ __('project.fields.is_active') }}</label>
                    </div>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">{{ __('project.fields.description') }}</label>
                    <textarea name="description" id="description" rows="2" class="form-control" maxlength="5000">{{ old('description', $template->description) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0 fw-bold"><i class="fa-solid fa-list-check text-primary me-2"></i>{{ __('project.fields.template_tasks') }} <x-inputs.tip :text="__('project.hints.offsets')" /></h6>
            <button type="button" class="btn btn-sm btn-label-primary" id="addTemplateRow"><i class="fa-solid fa-plus me-1"></i>{{ __('project.actions.add_template_task') }}</button>
        </div>
        <div class="card-body" id="templateRows">
            @foreach ($rows as $i => $row)
                <div class="template-row border rounded p-3 mb-3">
                    <input type="hidden" name="tasks[{{ $i }}][subtasks]" value="{{ $row['subtasks'] ?? '' }}" data-field="subtasks">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('project.fields.task_title') }}</label>
                            <input type="text" class="form-control" name="tasks[{{ $i }}][title]" maxlength="255" value="{{ $row['title'] ?? '' }}" data-field="title">
                        </div>
                        <div class="col-4 col-md-2">
                            <label class="form-label small">{{ __('task.fields.priority') }}</label>
                            <select class="form-select no-select2" name="tasks[{{ $i }}][priority]" data-field="priority">
                                @foreach (\App\Enums\TaskPriority::options() as $value => $label)
                                    <option value="{{ $value }}" @selected(($row['priority'] ?? 'medium') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-4 col-md-2">
                            <label class="form-label small">{{ __('project.fields.start_offset_days') }}</label>
                            <input type="number" min="0" max="3650" class="form-control" dir="ltr" name="tasks[{{ $i }}][start_offset_days]" value="{{ $row['start_offset_days'] ?? '' }}" data-field="start_offset_days">
                        </div>
                        <div class="col-4 col-md-2">
                            <label class="form-label small">{{ __('project.fields.due_offset_days') }}</label>
                            <input type="number" min="0" max="3650" class="form-control" dir="ltr" name="tasks[{{ $i }}][due_offset_days]" value="{{ $row['due_offset_days'] ?? '' }}" data-field="due_offset_days">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">{{ __('project.fields.checklist_lines') }}</label>
                            <div class="d-flex gap-2 align-items-start">
                                <textarea class="form-control" rows="2" name="tasks[{{ $i }}][checklist]" maxlength="5000" data-field="checklist">{{ $row['checklist'] ?? '' }}</textarea>
                                <button type="button" class="btn btn-icon btn-label-danger remove-row" aria-label="{{ __('project.actions.remove') }}" title="{{ __('project.actions.remove') }}"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('admin.project-templates.index') }}" class="btn btn-label-secondary">{{ __('global.cancel') }}</a>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>{{ __('global.save') }}</button>
    </div>
</form>
@endsection

@push('js')
<script>
    (function () {
        const box = document.getElementById('templateRows');
        let next = box.querySelectorAll('.template-row').length;

        function renumber(row, index) {
            row.querySelectorAll('[data-field]').forEach(function (el) {
                el.name = 'tasks[' + index + '][' + el.dataset.field + ']';
            });
        }

        document.getElementById('addTemplateRow').addEventListener('click', function () {
            const row = box.querySelector('.template-row').cloneNode(true);
            row.querySelectorAll('[data-field]').forEach(function (el) {
                if (el.tagName === 'SELECT') { el.value = 'medium'; } else { el.value = ''; }
            });
            renumber(row, next++);
            box.appendChild(row);
            row.querySelector('[data-field=title]').focus();
        });

        box.addEventListener('click', function (e) {
            const btn = e.target.closest('.remove-row');
            if (!btn) return;
            const rows = box.querySelectorAll('.template-row');
            const row = btn.closest('.template-row');
            if (rows.length === 1) {
                row.querySelectorAll('[data-field]').forEach(function (el) { if (el.tagName !== 'SELECT') el.value = ''; });
            } else {
                row.remove();
            }
        });
    })();
</script>
@endpush
