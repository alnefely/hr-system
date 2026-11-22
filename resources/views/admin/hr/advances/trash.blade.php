@extends('layouts.admin.app')
@section('title', 'سلة محذوفات السلف والقروض')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="card-title mb-0 text-danger">
            <i class="fa-solid fa-trash me-2"></i> سلة محذوفات السلف والقروض
        </h4>
        <a href="{{ route('admin.advances.index') }}" class="btn btn-label-secondary">
            <i class="fa-solid fa-arrow-right icon-back me-1" aria-hidden="true"></i> {{ __('global.back') }}
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

            if (admin_can('advance_restore')) {
                $actions[] = [
                    'type' => 'custom',
                    'html' => "<li><form action=\"/admin/advances/\${row.id}/restore\" method=\"post\">{$csrf}<button type=\"button\" class=\"dropdown-item text-success btn-swal-confirm\" data-confirm-text=\"{$msgRestore}\" data-confirm-btn=\"{$txtRestore}\"><i class=\"fa-solid fa-rotate-left fa-fw\"></i> {$txtRestore}</button></form></li>",
                ];
            }

            if (admin_can('advance_force_delete')) {
                $actions[] = [
                    'type' => 'custom',
                    'html' => "<li><form action=\"/admin/advances/\${row.id}/force-delete\" method=\"post\">{$csrf}{$deleteMethod}<button type=\"button\" class=\"dropdown-item text-danger btn-swal-confirm\" data-confirm-text=\"{$msgForce}\" data-confirm-btn=\"{$txtForce}\"><i class=\"fa-solid fa-trash-can fa-fw\"></i> {$txtForce}</button></form></li>",
                ];
            }
        @endphp

        <x-dataTable.table
            :columns="[
                '#',
                'الموظف',
                'كود الموظف',
                'مبلغ السلفة',
                'تاريخ السلفة',
                'تاريخ الحذف'
            ]"
            :fields="[
                'id',
                'employee_name',
                'employee_code',
                'formatted_amount',
                'formatted_advance_date',
                'deleted_at'
            ]"
            :actions="$actions"
        />
    </div>
</div>
@endsection
