{{--
    فورم المشروع — لوحة الإدارة والبوابة (مدير المشروع).

    $project, $employees, $branches, $templates, $formAction, $cancelUrl
    $canManager  هل يقدر يغيّر مدير المشروع (لوحة الإدارة بس)
--}}
@include('tasks.partials.styles')
@php
    $isEdit = $project->exists;
    $roles = $isEdit ? $project->members->mapWithKeys(fn ($m) => [$m->id => $m->pivot->role]) : collect();
    $members = collect(old('members', $roles->filter(fn ($r) => $r === 'member')->keys()->all()))->map(fn ($v) => (int) $v)->all();
    $viewers = collect(old('viewers', $roles->filter(fn ($r) => $r === 'viewer')->keys()->all()))->map(fn ($v) => (int) $v)->all();
    $date = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('Y-m-d') : null;
    $label = fn ($e) => $e->name . ' (' . $e->employee_code . ')' . ($e->relationLoaded('branch') && $e->branch ? ' — ' . $e->branch->name : '');
@endphp

<form action="{{ $formAction }}" method="POST" data-no-confirm>
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid {{ $isEdit ? 'fa-pen-to-square' : 'fa-plus' }} text-primary me-2"></i>{{ $isEdit ? __('project.edit_title') : __('project.create_title') }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('project.fields.name') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('project.hints.name')" /></label>
                        <input type="text" name="name" id="name" maxlength="191" required autofocus class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $project->name) }}">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">{{ __('project.fields.description') }}</label>
                        <textarea name="description" id="description" rows="4" maxlength="20000" class="form-control">{{ old('description', $project->description) }}</textarea>
                    </div>
                    @if ($canManager)
                        <div class="mb-3">
                            <label for="manager_id" class="form-label">{{ __('project.fields.manager') }} <x-inputs.tip :text="__('project.hints.manager')" /></label>
                            <select name="manager_id" id="manager_id" class="form-select select2" data-placeholder="{{ __('project.labels_text.no_manager') }}">
                                <option value="">{{ __('project.labels_text.no_manager') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}" @selected((int) old('manager_id', $project->manager_id) === $employee->id)>{{ $label($employee) }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label for="members" class="form-label">{{ __('project.member_role_enums.member') }} <x-inputs.tip :text="__('project.hints.members')" /></label>
                        <select name="members[]" id="members" class="form-select select2" multiple data-placeholder="{{ __('global.choose') }}...">
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(in_array($employee->id, $members, true))>{{ $label($employee) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="viewers" class="form-label">{{ __('project.member_role_enums.viewer') }}</label>
                        <select name="viewers[]" id="viewers" class="form-select select2" multiple data-placeholder="{{ __('global.choose') }}...">
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(in_array($employee->id, $viewers, true))>{{ $label($employee) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            @if (! $isEdit && $templates->isNotEmpty())
                <div class="card">
                    <div class="card-body">
                        <label for="template_id" class="form-label">{{ __('project.fields.template') }} <x-inputs.tip :text="__('project.hints.template')" /></label>
                        <select name="template_id" id="template_id" class="form-select">
                            <option value="">{{ __('project.labels_text.no_template') }}</option>
                            @foreach ($templates as $template)
                                <option value="{{ $template->id }}" @selected((int) old('template_id') === $template->id)>
                                    {{ $template->name }} — {{ __('project.labels_text.template_tasks', ['count' => count($template->structure ?? [])]) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body">
                    @if ($branches->isNotEmpty() && ! $isEdit)
                        <div class="mb-3">
                            <label for="branch_id" class="form-label">{{ __('project.fields.branch') }} <span class="text-danger">*</span></label>
                            <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((int) old('branch_id') === $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endif
                    <div class="mb-3">
                        <label for="status" class="form-label">{{ __('project.fields.status') }}</label>
                        <select name="status" id="status" class="form-select">
                            @foreach (\App\Enums\ProjectStatus::options() as $value => $text)
                                <option value="{{ $value }}" @selected(old('status', $project->status) === $value)>{{ $text }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="priority" class="form-label">{{ __('project.fields.priority') }}</label>
                        <select name="priority" id="priority" class="form-select">
                            @foreach (\App\Enums\TaskPriority::options() as $value => $text)
                                <option value="{{ $value }}" @selected(old('priority', $project->priority ?? 'medium') === $value)>{{ $text }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="start_date" class="form-label">{{ __('project.fields.start_date') }} <x-inputs.tip :text="__('project.hints.start_date')" /></label>
                        <input type="date" name="start_date" id="start_date" class="form-control" dir="ltr" value="{{ old('start_date', $date($project->start_date)) }}">
                    </div>
                    <div class="mb-3">
                        <label for="due_date" class="form-label">{{ __('project.fields.due_date') }}</label>
                        <input type="date" name="due_date" id="due_date" class="form-control @error('due_date') is-invalid @enderror" dir="ltr" value="{{ old('due_date', $date($project->due_date)) }}">
                        @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="color" class="form-label">{{ __('project.fields.color') }} <x-inputs.tip :text="__('project.hints.color')" /></label>
                        <input type="color" name="color" id="color" class="form-control form-control-color w-100" value="{{ old('color', $project->color ?: '#7367f0') }}">
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
