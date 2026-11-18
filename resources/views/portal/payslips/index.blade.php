@extends('layouts.portal.app')
@section('title', __('portal.nav.payslips'))

@section('content')
<div class="card">
    <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-solid fa-file-invoice-dollar me-2 text-success"></i>{{ __('portal.payslips.title') }}</h6></div>
    <div class="card-body border-bottom">
        @include('portal.partials.date-filter', ['period' => $period])
    </div>
    <div class="card-body">
        <x-dataTable.table
            id="portal-payslips"
            :columns="[__('portal.payslips.period'), __('portal.payslips.basic'), __('portal.payslips.additions'), __('portal.payslips.deductions'), __('portal.payslips.net'), __('portal.payslips.paid_at'), '']"
            :fields="[
                ['data' => 'period', 'orderable' => false, 'searchable' => false],
                ['data' => 'basic_salary', 'searchable' => false],
                ['data' => 'allowances', 'searchable' => false],
                ['data' => 'deductions', 'searchable' => false],
                ['data' => 'net_salary', 'searchable' => false],
                ['data' => 'paid_at', 'searchable' => false],
                ['data' => 'action', 'orderable' => false, 'searchable' => false, 'className' => 'text-end'],
            ]"
            :order="[[5, 'desc']]"
            :exportable="false"
            :searchable="false" />
    </div>
</div>
@endsection
