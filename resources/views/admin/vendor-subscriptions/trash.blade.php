@extends('layouts.admin.app')
@section('title', __('vendor_subscription.trash'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0 text-danger">
            <i class="fa-solid fa-trash me-2"></i>{{ __('vendor_subscription.trash') }}
        </h4>
        <a href="{{ route('admin.vendor-subscriptions.index') }}" class="btn btn-label-secondary">
            <i class="fa-solid fa-arrow-right me-1"></i> {{ __('vendor_subscription.back') ?? __('global.back') }}
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

            if (admin_can('vendor_subscription_restore')) {
                $actions[] = [
                    'type' => 'custom',
                    'html' => "<li><form action=\"/admin/vendor-subscriptions/\${row.id}/restore\" method=\"post\">{$csrf}<button type=\"button\" class=\"dropdown-item text-success btn-swal-confirm\" data-text=\"{$msgRestore}\" data-icon=\"warning\" data-btn-class=\"btn-success\"><i class=\"fa-solid fa-rotate-left fa-fw\"></i> {$txtRestore}</button></form></li>",
                ];
            }

            if (admin_can('vendor_subscription_force_delete')) {
                $actions[] = [
                    'type' => 'custom',
                    'html' => "<li><form action=\"/admin/vendor-subscriptions/\${row.id}/force-delete\" method=\"post\">{$csrf}{$deleteMethod}<button type=\"button\" class=\"dropdown-item text-danger btn-swal-confirm\" data-text=\"{$msgForce}\" data-icon=\"warning\" data-btn-class=\"btn-danger\"><i class=\"fa-solid fa-trash-can fa-fw\"></i> {$txtForce}</button></form></li>",
                ];
            }
        @endphp

        <x-dataTable.table
            :columns="[
                '#',
                'اسم الخدمة / الاشتراك',
                'المورد',
                'التكلفة',
                'تاريخ التجديد القادم',
                'تاريخ الحذف'
            ]"
            :fields="[
                'id',
                'service_name',
                'vendor_name',
                'cost_formatted',
                'next_renewal_date',
                'deleted_at'
            ]"
            :actions="$actions"
        />
    </div>
</div>
@endsection
