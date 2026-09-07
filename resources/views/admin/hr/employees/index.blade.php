@extends('layouts.admin.app')
@section('title', __('employee.title'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">{{ __('employee.title') }}</h4>
        <div class="d-flex gap-2">
            @if (admin_can('employee_salary_view'))
                <button type="button" class="btn btn-label-primary waves-effect" id="btnToggleSalaries">
                    <i class="fa-solid fa-eye me-1"></i> <span id="salaryToggleText">{{ __('employee.show_salaries') }}</span>
                </button>
            @endif
            @if (admin_can('employee_trash'))
                <a href="{{ route('admin.employees.trash') }}" class="btn btn-label-danger waves-effect">
                    <i class="fa-solid fa-trash me-1"></i> {{ __('employee.trash') }}
                    @if ($trashCount > 0)
                        <span class="badge bg-danger ms-1">{{ $trashCount }}</span>
                    @endif
                </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        @php
            $actions = [
                ['type' => 'view', 'url' => url('admin/employees')],
            ];
            if (admin_can('employee_update')) {
                $actions[] = ['type' => 'edit', 'url' => url('admin/employees')];
            }
            if (admin_can('employee_delete')) {
                $actions[] = ['type' => 'delete', 'url' => url('admin/employees')];
            }
        @endphp

        <x-dataTable.table
            :columns="[
                __('global.id'),
                __('employee.fields.employee_code'),
                __('employee.fields.name'),
                __('employee.fields.branch'),
                __('employee.fields.job_title'),
                __('employee.fields.phone'),
                __('employee.fields.hire_date'),
                __('employee.fields.current_salary'),
                __('employee.fields.status'),
            ]"
            :fields="[
                'id',
                ['data' => 'code_badge', 'name' => 'employee_code'],
                ['data' => 'avatar_img', 'name' => 'name'],
                [
                    'data' => 'branch.name',
                    'name' => 'branch.name',
                    'searchType' => 'select',
                    'searchOptions' => $branches->pluck('name', 'id')->toArray(),
                ],
                [
                    'data' => 'job_title.name',
                    'name' => 'job_title.name',
                    'searchType' => 'select',
                    'searchOptions' => $jobTitles->pluck('name', 'id')->toArray(),
                ],
                'phone',
                [
                    'data' => 'hire_date',
                    'name' => 'hire_date',
                    'searchType' => 'date',
                ],
                ['data' => 'salary_display', 'searchable' => false, 'orderable' => false],
                [
                    'data' => 'status_badge',
                    'name' => 'status',
                    'searchType' => 'select',
                    'searchOptions' => [
                        'active'     => __('employee.statuses.active'),
                        'on_leave'   => __('employee.statuses.on_leave'),
                        'terminated' => __('employee.statuses.terminated'),
                    ],
                ],
            ]"
            :actions="$actions"
            add-new="{{ admin_can('employee_create') ? __('employee.add_new') : null }}"
            add-new-url="{{ admin_can('employee_create') ? route('admin.employees.create') : null }}"
        />
    </div>
</div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('btnToggleSalaries');
    if (!btn) return;

    let salariesVisible = localStorage.getItem('hr_salaries_visible') === 'true';

    function updateSalaryVisibility() {
        const amounts = document.querySelectorAll('.salary-amount');
        const maskeds = document.querySelectorAll('.salary-masked');
        const toggleText = document.getElementById('salaryToggleText');
        const icon = btn.querySelector('i');

        if (salariesVisible) {
            amounts.forEach(el => el.classList.remove('d-none'));
            maskeds.forEach(el => el.classList.add('d-none'));
            if (toggleText) toggleText.textContent = "{{ __('employee.hide_salaries') }}";
            if (icon) {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
            btn.classList.remove('btn-label-primary');
            btn.classList.add('btn-label-secondary');
        } else {
            amounts.forEach(el => el.classList.add('d-none'));
            maskeds.forEach(el => el.classList.remove('d-none'));
            if (toggleText) toggleText.textContent = "{{ __('employee.show_salaries') }}";
            if (icon) {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
            btn.classList.remove('btn-label-secondary');
            btn.classList.add('btn-label-primary');
        }
    }

    // Apply on load
    updateSalaryVisibility();

    // Re-apply whenever DataTables redraws
    $(document).on('draw.dt', function () {
        updateSalaryVisibility();
    });

    btn.addEventListener('click', function () {
        salariesVisible = !salariesVisible;
        localStorage.setItem('hr_salaries_visible', salariesVisible ? 'true' : 'false');
        updateSalaryVisibility();
    });
});
</script>
@endpush
