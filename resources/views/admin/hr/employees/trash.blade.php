@extends('layouts.admin.app')
@section('title', __('employee.trash'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="card-title mb-0 text-danger">
            <i class="fa-solid fa-trash me-2"></i>{{ __('employee.trash') }}
        </h4>
        <a href="{{ route('admin.employees') }}" class="btn btn-label-secondary">
            <i class="fa-solid fa-arrow-right icon-back me-1" aria-hidden="true"></i> {{ __('employee.back') }}
        </a>
    </div>
    <div class="card-body">
        @php
            $csrf = csrf_field();
            $deleteMethod = method_field('DELETE');
            $actions = [];

            if (admin_can('employee_restore')) {
                $actions[] = [
                    'type' => 'custom',
                    'html' => "<li><form action=\"/admin/employees/\${row.id}/restore\" method=\"post\">{$csrf}<button type=\"button\" class=\"dropdown-item text-success btn-swal-confirm\" data-text=\"" . __('global.confirm_restore') . "\" data-icon=\"warning\" data-btn-class=\"btn-success\"><i class=\"fa-solid fa-rotate-left fa-fw\"></i> " . __('global.restore') . "</button></form></li>",
                ];
            }

            if (admin_can('employee_force_delete')) {
                $actions[] = [
                    'type' => 'custom',
                    'html' => "<li><form action=\"/admin/employees/\${row.id}/force-delete\" method=\"post\">{$csrf}{$deleteMethod}<button type=\"button\" class=\"dropdown-item text-danger btn-swal-confirm\" data-text=\"" . __('global.confirm_force_delete') . "\" data-icon=\"warning\" data-btn-class=\"btn-danger\"><i class=\"fa-solid fa-trash-can fa-fw\"></i> " . __('global.force_delete') . "</button></form></li>",
                ];
            }
        @endphp

        <x-dataTable.table
            :columns="[
                __('global.id'),
                __('employee.fields.code'),
                __('employee.fields.name'),
                __('employee.fields.branch'),
                __('employee.fields.job_title'),
                __('employee.fields.deleted_at'),
            ]"
            :fields="[
                'id',
                'employee_code',
                'name',
                ['data' => 'branch.name', 'name' => 'branch_id'],
                ['data' => 'job_title.name', 'name' => 'job_title_id'],
                'deleted_at',
            ]"
            :actions="$actions"
        />
    </div>
</div>
@endsection
