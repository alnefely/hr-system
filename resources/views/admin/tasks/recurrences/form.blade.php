@extends('layouts.admin.app')
@section('title', $rule->exists ? __('task.recurrence.edit') : __('task.recurrence.add_new'))

@section('content')
@php
    $bp = $rule->blueprint ?? [];
    $freq = old('frequency', $rule->frequency);
    $days = collect(old('weekdays', $rule->weekdays ?? []))->map(fn ($d) => (int) $d)->all();
    $assignees = collect(old('assignee_ids', $bp['assignee_ids'] ?? []))->map(fn ($v) => (int) $v)->all();
    $checklist = old('checklist', $bp['checklist'] ?? []) ?: [''];
    $weekNames = collect(range(0, 6))->mapWithKeys(fn ($d) => [$d => now()->startOfWeek(\Illuminate\Support\Carbon::SUNDAY)->addDays($d)->translatedFormat('l')]);
    // ترتيب العرض يبدأ بالسبت
    $weekOrder = [6, 0, 1, 2, 3, 4, 5];
@endphp

<form action="{{ $rule->exists ? route('admin.tasks.recurrences.update', $rule->id) : route('admin.tasks.recurrences.store') }}" method="POST" data-no-confirm>
    @csrf
    @if ($rule->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-repeat text-primary me-2"></i>{{ $rule->exists ? __('task.recurrence.edit') : __('task.recurrence.add_new') }}</h5>
                    <a href="{{ route('admin.tasks.recurrences.index') }}" class="btn btn-sm btn-label-secondary"><i class="fa-solid fa-arrow-right icon-back me-1"></i>{{ __('task.recurrence.back') }}</a>
                </div>
                <div class="card-body pt-4">
                    <div class="mb-3">
                        <label for="title" class="form-label">{{ __('task.fields.title') }} <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" maxlength="255" required value="{{ old('title', $bp['title'] ?? '') }}">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">{{ __('task.fields.description') }}</label>
                        <textarea name="description" id="description" rows="3" class="form-control" maxlength="20000">{{ old('description', $bp['description'] ?? '') }}</textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="project_id" class="form-label">{{ __('task.fields.project') }}</label>
                            <select name="project_id" id="project_id" class="form-select select2">
                                <option value="">{{ __('task.filters.no_project') }}</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}" @selected((int) old('project_id', $rule->project_id) === $project->id)>{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($branches->isNotEmpty())
                            <div class="col-md-6">
                                <label for="branch_id" class="form-label">{{ __('task.fields.branch') }}</label>
                                <select name="branch_id" id="branch_id" class="form-select select2">
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected((int) old('branch_id') === $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                @error('branch_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label for="assignee_ids" class="form-label">{{ __('task.fields.assignees') }}</label>
                        <select name="assignee_ids[]" id="assignee_ids" class="form-select select2" multiple>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(in_array($employee->id, $assignees, true))>{{ $employee->name }} ({{ $employee->employee_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="priority" class="form-label">{{ __('task.fields.priority') }}</label>
                            <select name="priority" id="priority" class="form-select no-select2">
                                @foreach (\App\Enums\TaskPriority::options() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('priority', $bp['priority'] ?? 'medium') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="estimated_hours" class="form-label">{{ __('task.fields.estimated_hours') }}</label>
                            <input type="number" name="estimated_hours" id="estimated_hours" min="0" max="1000" step="0.5" class="form-control" dir="ltr"
                                   value="{{ old('estimated_hours', isset($bp['estimated_minutes']) ? round($bp['estimated_minutes'] / 60, 1) : null) }}">
                        </div>
                    </div>
                    <label class="form-label">{{ __('task.fields.checklist') }}</label>
                    <div id="recChecklist">
                        @foreach ($checklist as $step)
                            <input type="text" name="checklist[]" class="form-control mb-2" maxlength="500" value="{{ $step }}" aria-label="{{ __('task.fields.checklist') }}">
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-label-secondary" onclick="const r=document.getElementById('recChecklist'); const i=r.firstElementChild.cloneNode(); i.value=''; r.appendChild(i); i.focus();">
                        <i class="fa-solid fa-plus me-1"></i>{{ __('task.actions.add_step_row') }}
                    </button>
                    <div class="form-check form-switch mt-3">
                        <input type="hidden" name="requires_review" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" name="requires_review" id="requires_review" value="1" @checked(old('requires_review', $bp['requires_review'] ?? true))>
                        <label class="form-check-label" for="requires_review">{{ __('task.fields.requires_review') }}</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-solid fa-calendar-days text-primary me-2"></i>{{ __('task.recurrence.schedule') }}</h6></div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-5">
                            <label for="interval" class="form-label">{{ __('task.recurrence.interval') }}</label>
                            <input type="number" name="interval" id="interval" min="1" max="52" class="form-control" dir="ltr" required value="{{ old('interval', $rule->interval) }}">
                        </div>
                        <div class="col-7">
                            <label for="frequency" class="form-label">&nbsp;</label>
                            <select name="frequency" id="frequency" class="form-select no-select2" aria-label="{{ __('task.recurrence.schedule') }}">
                                @foreach (array_keys(__('task.recurrence.units')) as $unit)
                                    <option value="{{ $unit }}" @selected($freq === $unit)>{{ __('task.recurrence.units.' . $unit) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 {{ $freq === 'weekly' ? '' : 'd-none' }}" data-for="weekly">
                        <label class="form-label">{{ __('task.recurrence.weekdays') }} <x-inputs.tip :text="__('task.recurrence.hints.weekdays')" /></label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($weekOrder as $d)
                                <input type="checkbox" class="btn-check" name="weekdays[]" value="{{ $d }}" id="wd{{ $d }}" @checked(in_array($d, $days, true))>
                                <label class="btn btn-sm btn-outline-primary" for="wd{{ $d }}">{{ $weekNames[$d] }}</label>
                            @endforeach
                        </div>
                        @error('weekdays')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3 {{ $freq === 'monthly' ? '' : 'd-none' }}" data-for="monthly">
                        <label for="month_day" class="form-label">{{ __('task.recurrence.month_day') }}</label>
                        <input type="number" name="month_day" id="month_day" min="1" max="31" class="form-control @error('month_day') is-invalid @enderror" dir="ltr" value="{{ old('month_day', $rule->month_day) }}">
                        @error('month_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="run_time" class="form-label">{{ __('task.recurrence.run_time') }} <x-inputs.tip :text="__('task.recurrence.hints.run_time')" /></label>
                            <input type="time" name="run_time" id="run_time" class="form-control" dir="ltr" required value="{{ old('run_time', substr((string) $rule->run_time, 0, 5)) }}">
                        </div>
                        <div class="col-6">
                            <label for="duration_days" class="form-label">{{ __('task.recurrence.duration_days') }} <x-inputs.tip :text="__('task.recurrence.hints.duration_days')" /></label>
                            <input type="number" name="duration_days" id="duration_days" min="0" max="365" class="form-control" dir="ltr" required value="{{ old('duration_days', $rule->duration_days) }}">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="starts_on" class="form-label">{{ __('task.recurrence.starts_on') }}</label>
                            <input type="date" name="starts_on" id="starts_on" class="form-control" dir="ltr" required value="{{ old('starts_on', $rule->starts_on?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-6">
                            <label for="ends_on" class="form-label">{{ __('task.recurrence.ends_on') }}</label>
                            <input type="date" name="ends_on" id="ends_on" class="form-control @error('ends_on') is-invalid @enderror" dir="ltr" value="{{ old('ends_on', $rule->ends_on?->format('Y-m-d')) }}">
                            @error('ends_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="is_active" value="1" @checked(old('is_active', $rule->is_active))>
                        <label class="form-check-label" for="is_active">{{ __('task.recurrence.active') }}</label>
                    </div>
                    @if ($rule->exists && $rule->next_run_at)
                        <div class="alert alert-info small mt-3 mb-0"><i class="fa-regular fa-clock me-1"></i>{{ __('task.recurrence.next_run') }}: <span dir="ltr">{{ $rule->next_run_at->format('Y-m-d H:i') }}</span></div>
                    @endif
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-floppy-disk me-1"></i>{{ __('global.save') }}</button>
        </div>
    </div>
</form>
@endsection

@push('js')
<script>
    document.getElementById('frequency').addEventListener('change', function () {
        document.querySelectorAll('[data-for]').forEach(el => el.classList.toggle('d-none', el.dataset.for !== this.value));
    });
</script>
@endpush
