@extends('layouts.admin.app')
@section('title', __('customer.title'))

@section('content')
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-6">
        <div class="card h-100 border-start border-primary border-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="avatar avatar-md rounded bg-label-primary d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-user-group fs-4"></i>
                    </div>
                    <span class="badge bg-label-primary" data-bs-toggle="tooltip" title="إجمالي قاعدة بيانات العملاء">{{ $customersCount }} عميل</span>
                </div>
                <h4 class="mb-1 text-primary fw-bold"><span dir="ltr">{{ $customersCount }}</span></h4>
                <p class="mb-0 text-muted small" data-bs-toggle="tooltip" title="قاعدة بيانات العملاء والشركات المتعاقدة">{{ __('customer.title') }}</p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-6">
        <div class="card h-100 border-start border-success border-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="avatar avatar-md rounded bg-label-success d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-user-check fs-4"></i>
                    </div>
                    <span class="badge bg-label-success" data-bs-toggle="tooltip" title="العملاء النشطون والمتاحون لإصدار فواتير لهم">نشط</span>
                </div>
                <h4 class="mb-1 text-success fw-bold"><span dir="ltr">{{ $activeCustomersCount }}</span></h4>
                <p class="mb-0 text-muted small" data-bs-toggle="tooltip" title="حسابات العملاء المفعلة">العملاء النشطون</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-users text-primary me-2"></i> {{ __('customer.title') }}
        </h5>
        @if(admin_can('customer_create'))
            <a href="{{ route('admin.customers.create') }}" class="btn btn-primary waves-effect waves-light">
                <i class="fa-solid fa-plus me-1"></i> {{ __('customer.actions.add_new') }}
            </a>
        @endif
    </div>

    <div class="card-body pt-4">
        @php
            $columns = [
                __('global.id'),
                __('customer.fields.code'),
                __('customer.fields.name'),
                __('customer.fields.company_name'),
                __('customer.fields.phone'),
                __('customer.fields.email'),
                __('customer.fields.sales_count'),
                __('customer.fields.is_active'),
                __('global.actions'),
            ];

            $fields = [
                'id',
                'code',
                'name',
                ['data' => 'company_name', 'name' => 'company_name'],
                ['data' => 'phone_formatted', 'name' => 'phone'],
                'email',
                ['data' => 'sales_badge', 'name' => 'sales_count', 'orderable' => false, 'searchable' => false],
                ['data' => 'status_badge', 'name' => 'is_active', 'searchType' => 'select', 'searchOptions' => ['1' => __('global.active'), '0' => __('global.inactive')]],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ];
        @endphp

        <x-dataTable.table
            :columns="$columns"
            :fields="$fields"
            :url="route('admin.customers.index')"
        />
    </div>
</div>
@endsection
