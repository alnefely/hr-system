@extends('layouts.portal.app')
@section('title', __('portal.nav.penalties'))

@section('content')
@php
    $currency = $employee->branch?->currency ?? '';
@endphp

{{-- ملخص الفترة المختارة --}}
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="avatar-initial rounded bg-label-danger p-3"><i class="fa-solid fa-scale-balanced fa-lg"></i></span>
                <div>
                    <div class="text-muted small">{{ __('portal.penalties.period_count') }}</div>
                    <h5 class="mb-0 fw-bold">{{ $count }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="avatar-initial rounded bg-label-warning p-3"><i class="fa-solid fa-money-bill-transfer fa-lg"></i></span>
                <div>
                    <div class="text-muted small">{{ __('portal.penalties.period_total') }}</div>
                    <h5 class="mb-0 fw-bold"><span dir="ltr">{{ number_format((float) $total, 2) }} <small class="fs-6 text-muted">{{ $currency }}</small></span></h5>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-1 fw-bold"><i class="fa-solid fa-scale-balanced me-2 text-danger"></i>{{ __('portal.penalties.title') }}</h6>
        <small class="text-muted">{{ __('portal.penalties.hint') }}</small>
    </div>
    <div class="card-body border-bottom">
        @include('portal.partials.date-filter', ['period' => $period])
    </div>
    <div class="card-body">
        <x-dataTable.table
            id="portal-penalties"
            :columns="[__('portal.penalties.date'), __('portal.penalties.type'), __('portal.penalties.reason'), __('portal.penalties.deduction')]"
            :fields="[
                'penalty_date',
                ['data' => 'penalty_type', 'searchable' => false],
                'reason',
                ['data' => 'deduction', 'orderable' => false, 'searchable' => false],
            ]"
            :order="[[0, 'desc']]"
            :exportable="false"
            :searchable="false" />
    </div>
</div>
@endsection
