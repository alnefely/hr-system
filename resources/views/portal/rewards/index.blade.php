@extends('layouts.portal.app')
@section('title', __('portal.nav.rewards'))

@section('content')
@php
    $currency = $employee->branch?->currency ?? '';
@endphp

{{-- ملخص الفترة المختارة --}}
<div class="row g-4 mb-4">
    @foreach ([
        [$total,     'fa-gift',            'primary', __('portal.rewards.period_total')],
        [$upcoming,  'fa-hourglass-half',  'warning', __('portal.rewards.upcoming')],
        [$paidTotal, 'fa-money-bill-wave', 'success', __('portal.rewards.paid_total')],
    ] as [$value, $icon, $color, $label])
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar-initial rounded bg-label-{{ $color }} p-3"><i class="fa-solid {{ $icon }} fa-lg"></i></span>
                    <div>
                        <div class="text-muted small">{{ $label }}</div>
                        <h5 class="mb-0 fw-bold"><span dir="ltr">{{ number_format($value, 2) }} <small class="fs-6 text-muted">{{ $currency }}</small></span></h5>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-1 fw-bold"><i class="fa-solid fa-gift me-2 text-primary"></i>{{ __('portal.rewards.title') }}</h6>
        <small class="text-muted">{{ __('portal.rewards.hint') }}</small>
    </div>
    <div class="card-body border-bottom">
        @include('portal.partials.date-filter', ['period' => $period])
    </div>
    <div class="card-body">
        <x-dataTable.table
            id="portal-rewards"
            :columns="[__('portal.rewards.date'), __('portal.rewards.type'), __('portal.rewards.details'), __('portal.rewards.amount'), __('portal.fields.status')]"
            :fields="[
                'reward_date',
                ['data' => 'reward_type', 'searchable' => false],
                'title',
                ['data' => 'amount', 'searchable' => false],
                ['data' => 'status_badge', 'name' => 'status', 'searchable' => false],
            ]"
            :order="[[0, 'desc']]"
            :exportable="false"
            :searchable="false" />
    </div>
</div>
@endsection
