{{-- فلاتر مختصرة للوحة والتقويم. $action, $filters, $projects, $employees (اختياري), $hidden (حقول مخفية زي التبويب والشهر) --}}
<form action="{{ $action }}" method="GET" class="row g-2 align-items-end">
    @foreach ($hidden ?? [] as $name => $value)
        @if ($value !== null)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
    @endforeach
    <div class="col-12 col-md-4">
        <label for="vf_q" class="form-label small mb-1">{{ __('task.filters.search') }}</label>
        <input type="search" name="q" id="vf_q" class="form-control" value="{{ $filters['q'] ?? '' }}">
    </div>
    @if ($projects->isNotEmpty())
        <div class="col-6 col-md-3">
            <label for="vf_project" class="form-label small mb-1">{{ __('task.fields.project') }}</label>
            <select name="project_id" id="vf_project" class="form-select" onchange="this.form.submit()">
                <option value="">{{ __('task.filters.all_projects') }}</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected((string) ($filters['project_id'] ?? '') === (string) $project->id)>{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    @if (! empty($employees) && $employees->isNotEmpty())
        <div class="col-6 col-md-3">
            <label for="vf_employee" class="form-label small mb-1">{{ __('task.fields.assignees') }}</label>
            <select name="employee_id" id="vf_employee" class="form-select" onchange="this.form.submit()">
                <option value="">{{ __('task.filters.all_employees') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected((string) ($filters['employee_id'] ?? '') === (string) $employee->id)>{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="col-6 col-md-2">
        <label for="vf_priority" class="form-label small mb-1">{{ __('task.fields.priority') }}</label>
        <select name="priority" id="vf_priority" class="form-select" onchange="this.form.submit()">
            <option value="">{{ __('task.filters.all_priorities') }}</option>
            @foreach (\App\Enums\TaskPriority::options() as $value => $label)
                <option value="{{ $value }}" @selected(($filters['priority'] ?? null) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</form>
