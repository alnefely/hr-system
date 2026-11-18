@extends('layouts.portal.app')
@section('title', __('portal.nav.expenses'))

@section('content')
@php
    [$from, $to] = $period;
    $range = ($from || $to)
        ? trim(($from ?? '…') . ' → ' . ($to ?? '…'))
        : __('portal.filter.all');
@endphp

<div class="mb-4">
    <h5 class="fw-bold mb-1"><i class="fa-solid fa-wallet me-2 text-primary"></i>{{ __('portal.expenses.title') }}</h5>
    <small class="text-muted">{{ __('portal.expenses.hint') }}</small>
</div>

{{-- إجمالي الفترة لكل عملة --}}
<div class="row g-4 mb-4">
    @forelse ($totals as $t)
        <div class="col-sm-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar-initial rounded bg-label-primary p-3"><i class="fa-solid fa-receipt fa-lg"></i></span>
                    <div>
                        <div class="text-muted small">{{ __('portal.expenses.period_total') }} <span dir="ltr">({{ $range }})</span></div>
                        <h5 class="mb-0 fw-bold"><span dir="ltr">{{ number_format((float) $t->total, 2) }} <small class="fs-6 text-muted">{{ $t->currency }}</small></span></h5>
                        <small class="text-muted">{{ __('portal.expenses.count', ['count' => $t->cnt]) }}</small>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-secondary mb-0">{{ __('portal.expenses.empty_period') }} <span dir="ltr">({{ $range }})</span></div>
        </div>
    @endforelse
</div>

<div class="card">
    <div class="card-body border-bottom">
        @include('portal.partials.date-filter', ['period' => $period])
    </div>
    <div class="card-body">
        <x-dataTable.table
            id="portal-expenses"
            :columns="[__('portal.expenses.date'), __('portal.expenses.item'), __('portal.expenses.category'), __('portal.expenses.amount'), __('portal.expenses.method'), __('portal.expenses.invoice')]"
            :fields="[
                'expense_date',
                'title',
                ['data' => 'category_name', 'name' => 'category.name', 'orderable' => false],
                ['data' => 'amount', 'searchable' => false],
                ['data' => 'payment_method', 'searchable' => false],
                ['data' => 'invoice', 'name' => 'invoice_number', 'orderable' => false],
            ]"
            :order="[[0, 'desc']]"
            :exportable="false"
            :searchable="false" />
    </div>
</div>
@endsection
