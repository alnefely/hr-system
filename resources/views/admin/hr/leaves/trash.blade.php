@extends('layouts.admin.app')
@section('title', __('leave.trash'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="card-title mb-0 text-danger">
            <i class="fa-solid fa-trash me-2"></i>{{ __('leave.trash') }}
        </h4>
        <a href="{{ route('admin.leaves.index') }}" class="btn btn-label-secondary">
            <i class="fa-solid fa-arrow-right icon-back me-1" aria-hidden="true"></i> {{ __('leave.back') ?? __('global.back') }}
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

            if (admin_can('leave_restore')) {
                $actions[] = [
                    'type' => 'custom',
                    'html' => "<li><form action=\"/admin/leaves/\${row.id}/restore\" method=\"post\">{$csrf}<button type=\"button\" class=\"dropdown-item text-success btn-swal-confirm\" data-confirm-text=\"{$msgRestore}\" data-confirm-btn=\"{$txtRestore}\"><i class=\"fa-solid fa-rotate-left fa-fw\"></i> {$txtRestore}</button></form></li>",
                ];
            }

            if (admin_can('leave_force_delete')) {
                $actions[] = [
                    'type' => 'custom',
                    'html' => "<li><form action=\"/admin/leaves/\${row.id}/force-delete\" method=\"post\">{$csrf}{$deleteMethod}<button type=\"button\" class=\"dropdown-item text-danger btn-swal-confirm\" data-confirm-text=\"{$msgForce}\" data-confirm-btn=\"{$txtForce}\"><i class=\"fa-solid fa-trash-can fa-fw\"></i> {$txtForce}</button></form></li>",
                ];
            }
        @endphp

        <x-dataTable.table
            :columns="[
                '#',
                'الموظف',
                'كود الموظف',
                'نوع الإجازة',
                'تاريخ البدء',
                'تاريخ الانتهاء',
                'تاريخ الحذف'
            ]"
            :fields="[
                'id',
                'employee_name',
                'employee_code',
                'leave_type_badge',
                'formatted_start',
                'formatted_end',
                'deleted_at'
            ]"
            :actions="$actions"
        />
    </div>
</div>
@endsection
