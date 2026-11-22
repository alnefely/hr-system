{{--
    حقول فورم المهمة (إنشاء وتعديل) — مشتركة بين لوحة الإدارة والبوابة.

    المتغيرات: $task, $projects, $branches, $employees, $labels, $parent,
               $formAction, $cancelUrl
    - المشروع والفرع والمكلّفين والخطوات في الإنشاء بس؛ التعديل بيغيّر البيانات
      الأساسية، والمكلّفين والخطوات من شاشة التفاصيل.
--}}
@include('tasks.partials.styles')
@php
    $isEdit = $task->exists;
    $dt = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\TH:i') : null;
    $selectedLabels = collect(old('label_ids', $isEdit ? $task->labels->pluck('id')->all() : []))->map(fn ($v) => (int) $v)->all();
    $selectedAssignees = collect(old('assignee_ids', []))->map(fn ($v) => (int) $v)->all();
    $interval = old('progress_update_interval', $task->progress_update_interval ?? 'none');
@endphp

<form action="{{ $formAction }}" method="POST" id="taskForm" data-no-confirm>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif
    @if ($parent)
        <input type="hidden" name="parent_id" value="{{ $parent->id }}">
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid {{ $isEdit ? 'fa-pen-to-square' : 'fa-plus' }} text-primary me-2"></i>
                        {{ $isEdit ? __('task.edit_title') : __('task.create_title') }}
                    </h5>
                    @if ($parent)
                        <div class="small text-body-secondary mt-1"><i class="fa-solid fa-turn-up me-1"></i>{{ __('task.fields.parent') }}: {{ $parent->title }}</div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">{{ __('task.fields.title') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('task.hints.title')" /></label>
                        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" maxlength="255" required autofocus
                               value="{{ old('title', $task->title) }}">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">{{ __('task.fields.description') }}</label>
                        <textarea name="description" id="description" rows="5" maxlength="20000" class="form-control @error('description') is-invalid @enderror">{{ old('description', $task->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    @unless ($isEdit)
                        <div class="row g-3 mb-3">
                            @if (! $parent)
                                <div class="col-md-6">
                                    <label for="project_id" class="form-label">{{ __('task.fields.project') }} <x-inputs.tip :text="__('task.hints.project')" /></label>
                                    <select name="project_id" id="project_id" class="form-select select2">
                                        <option value="">{{ __('task.filters.no_project') }}</option>
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->id }}" @selected((int) old('project_id', $task->project_id) === $project->id)>{{ $project->name }}</option>
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
                                    </div>
                                @endif
                            @endif
                            <div class="col-12">
                                <label for="assignee_ids" class="form-label">{{ __('task.fields.assignees') }} <x-inputs.tip :text="__('task.hints.assignees')" /></label>
                                <select name="assignee_ids[]" id="assignee_ids" class="form-select select2 @error('assignee_ids') is-invalid @enderror" multiple data-placeholder="{{ __('global.choose') }}...">
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}" @selected(in_array($employee->id, $selectedAssignees, true))>{{ $employee->name }} ({{ $employee->employee_code }})</option>
                                    @endforeach
                                </select>
                                @error('assignee_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-1">
                            <label class="form-label">{{ __('task.fields.checklist') }} <x-inputs.tip :text="__('task.hints.checklist')" /></label>
                            <div id="checklistRows">
                                @foreach (old('checklist', ['']) as $step)
                                    <div class="input-group mb-2">
                                        <span class="input-group-text"><i class="fa-regular fa-square"></i></span>
                                        <input type="text" name="checklist[]" class="form-control" maxlength="500" value="{{ $step }}" aria-label="{{ __('task.fields.checklist') }}">
                                        <button type="button" class="btn btn-label-danger" data-remove-step title="{{ __('task.actions.delete') }}" aria-label="{{ __('task.actions.delete') }}">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-sm btn-label-secondary" id="addChecklistRow"><i class="fa-solid fa-plus me-1"></i>{{ __('task.actions.add_step_row') }}</button>
                        </div>
                    @endunless
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="priority" class="form-label">{{ __('task.fields.priority') }} <x-inputs.tip :text="__('task.hints.priority')" /></label>
                        <select name="priority" id="priority" class="form-select">
                            @foreach (\App\Enums\TaskPriority::options() as $value => $label)
                                <option value="{{ $value }}" @selected(old('priority', $task->priority ?? 'medium') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="starts_at" class="form-label">{{ __('task.fields.starts_at') }}</label>
                        <input type="datetime-local" name="starts_at" id="starts_at" class="form-control @error('starts_at') is-invalid @enderror" dir="ltr"
                               value="{{ old('starts_at', $dt($task->starts_at)) }}">
                        @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="due_at" class="form-label">{{ __('task.fields.due_at') }} <x-inputs.tip :text="__('task.hints.due_at')" /></label>
                        <input type="datetime-local" name="due_at" id="due_at" class="form-control @error('due_at') is-invalid @enderror" dir="ltr"
                               value="{{ old('due_at', $dt($task->due_at)) }}">
                        @error('due_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="estimated_hours" class="form-label">{{ __('task.fields.estimated_hours') }} <x-inputs.tip :text="__('task.hints.estimated_hours')" /></label>
                        <input type="number" name="estimated_hours" id="estimated_hours" class="form-control" min="0" max="10000" step="0.5" dir="ltr"
                               value="{{ old('estimated_hours', $task->estimated_minutes ? round($task->estimated_minutes / 60, 1) : null) }}">
                    </div>
                    @if ($labels->isNotEmpty())
                        <div class="mb-3">
                            <label for="label_ids" class="form-label">{{ __('task.fields.labels') }}</label>
                            <select name="label_ids[]" id="label_ids" class="form-select select2" multiple>
                                @foreach ($labels as $label)
                                    <option value="{{ $label->id }}" @selected(in_array($label->id, $selectedLabels, true))>{{ $label->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="form-check form-switch">
                        <input type="hidden" name="requires_review" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" name="requires_review" id="requires_review" value="1"
                               @checked(old('requires_review', $task->exists ? $task->requires_review : true))>
                        <label class="form-check-label" for="requires_review">{{ __('task.fields.requires_review') }} <x-inputs.tip :text="__('task.hints.requires_review')" /></label>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-solid fa-chart-line text-primary me-2"></i>{{ __('task.fields.progress') }}</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="progress_source" class="form-label">{{ __('task.fields.progress_source') }} <x-inputs.tip :text="__('task.hints.progress_source')" /></label>
                        <select name="progress_source" id="progress_source" class="form-select">
                            @foreach (\App\Enums\TaskProgressSource::options() as $value => $label)
                                <option value="{{ $value }}" @selected(old('progress_source', $task->progress_source ?? 'manual') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="progress_update_interval" class="form-label">{{ __('task.fields.progress_update_interval') }} <x-inputs.tip :text="__('task.hints.progress_update_interval')" /></label>
                        <select name="progress_update_interval" id="progress_update_interval" class="form-select">
                            @foreach (\App\Enums\TaskProgressInterval::options() as $value => $label)
                                <option value="{{ $value }}" @selected($interval === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="{{ $interval === 'custom' ? '' : 'd-none' }}" id="everyDaysWrapper">
                        <label for="progress_update_every_days" class="form-label">{{ __('task.fields.progress_update_every_days') }}</label>
                        <input type="number" name="progress_update_every_days" id="progress_update_every_days" min="1" max="365" dir="ltr"
                               class="form-control @error('progress_update_every_days') is-invalid @enderror"
                               value="{{ old('progress_update_every_days', $task->progress_update_every_days) }}">
                        @error('progress_update_every_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="fa-solid fa-floppy-disk me-1"></i>{{ __('global.save') }}</button>
                <a href="{{ $cancelUrl }}" class="btn btn-label-secondary">{{ __('global.cancel') }}</a>
            </div>
        </div>
    </div>
</form>

@push('js')
<script>
    (function () {
        const interval = document.getElementById('progress_update_interval');
        const wrapper = document.getElementById('everyDaysWrapper');
        const days = document.getElementById('progress_update_every_days');
        // jQuery عشان Select2 بيبعت change عن طريقه بس (addEventListener مابيسمعهوش)
        if (interval) {
            $(interval).on('change', function () {
                const custom = this.value === 'custom';
                wrapper.classList.toggle('d-none', !custom);
                days.required = custom;
            });
        }

        const starts = document.getElementById('starts_at');
        const due = document.getElementById('due_at');
        starts?.addEventListener('change', function () { due.min = this.value; });

        const rows = document.getElementById('checklistRows');
        document.getElementById('addChecklistRow')?.addEventListener('click', function () {
            const row = rows.firstElementChild.cloneNode(true);
            row.querySelector('input').value = '';
            rows.appendChild(row);
            row.querySelector('input').focus();
        });

        // حذف خطوة: لو هي الوحيدة بنفضّيها بس (الصف الأول هو اللي بيتنسخ للإضافة)
        rows?.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-remove-step]');
            if (!btn) return;
            const row = btn.closest('.input-group');
            if (rows.children.length > 1) {
                const next = row.nextElementSibling || row.previousElementSibling;
                row.remove();
                next?.querySelector('input')?.focus();
            } else {
                const input = row.querySelector('input');
                input.value = '';
                input.focus();
            }
        });
    })();
</script>
@endpush
