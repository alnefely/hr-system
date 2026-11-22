@extends('layouts.admin.app')
@section('title', __('permission.trash'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="card-title mb-0 text-danger">
            <i class="fa-solid fa-trash me-2"></i>{{ __('permission.trash') }}
        </h4>
        <a href="{{ route('admin.permissions.index') }}" class="btn btn-label-secondary">
            <i class="fa-solid fa-arrow-right icon-back me-1" aria-hidden="true"></i> {{ __('permission.back') ?? __('global.back') }}
        </a>
    </div>
    <div class="card-body">
        @php
            $csrf = csrf_field();
            $deleteMethod = method_field('DELETE');
            $msgRestore = __('global.confirm_restore');
            $msgForce = __('global.confirm_force_delete');
            $txtRestore = __('global.restore');
            $txtForce = __('global.force_delete');
            $actions = [];

            if (admin_can('permission_restore')) {
                $actions[] = [
                    'type' => 'custom',
                    'html' => "<li><form action=\"/admin/permissions/\${row.id}/restore\" method=\"post\">{$csrf}<button type=\"button\" class=\"dropdown-item text-success btn-swal-confirm\" data-text=\"{$msgRestore}\" data-icon=\"warning\" data-btn-class=\"btn-success\"><i class=\"fa-solid fa-rotate-left fa-fw\"></i> {$txtRestore}</button></form></li>",
                ];
            }

            if (admin_can('permission_force_delete')) {
                $actions[] = [
                    'type' => 'custom',
                    'html' => "<li><form action=\"/admin/permissions/\${row.id}/force-delete\" method=\"post\">{$csrf}{$deleteMethod}<button type=\"button\" class=\"dropdown-item text-danger btn-swal-confirm\" data-text=\"{$msgForce}\" data-icon=\"warning\" data-btn-class=\"btn-danger\"><i class=\"fa-solid fa-trash-can fa-fw\"></i> {$txtForce}</button></form></li>",
                ];
            }
        @endphp

        <x-dataTable.table
            :columns="[
                '#',
                'الموظف',
                'كود الموظف',
                'تاريخ الاستئذان',
                'عدد الساعات',
                'تاريخ الحذف'
            ]"
            :fields="[
                'id',
                'employee_name',
                'employee_code',
                'permission_date',
                'hours_count',
                'deleted_at'
            ]"
            :actions="$actions"
        />
    </div>
</div>
@endsection
