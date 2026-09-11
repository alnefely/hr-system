@extends('layouts.admin.app')
@section('title', __('dashboard.title'))

@section('content')

{{-- ═══════════ ما يحتاج إجراءً ═══════════ --}}
@php
    $totalPending = $pending['leaves'] + $pending['advances'] + $pending['penalties']
                  + $pending['rewards'] + $pending['payrolls'];
    $totalAlerts  = $alerts['overdue_invoices'] + $alerts['due_subscriptions'];
@endphp

@if ($totalPending > 0 || $totalAlerts > 0)
    <div class="card border-0 shadow-sm mb-4" style="border-right: 4px solid var(--bs-warning) !important;">
        <div class="card-body py-3">
            <div class="d-flex align-items-center flex-wrap gap-3">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="fa-solid fa-bell text-warning me-1"></i>
                    {{ __('dashboard.needs_attention') }}
                </h6>

                @if ($pending['leaves'])
                    <a href="{{ route('admin.leaves.index', ['status' => 'pending']) }}" class="btn btn-sm btn-label-warning">
                        <i class="fa-solid fa-calendar-check me-1"></i>
                        {{ __('dashboard.pending.leaves') }}
                        <span class="badge bg-warning ms-1">{{ $pending['leaves'] }}</span>
                    </a>
                @endif

                @if ($pending['advances'])
                    <a href="{{ route('admin.advances.index', ['status' => 'pending']) }}" class="btn btn-sm btn-label-warning">
                        <i class="fa-solid fa-hand-holding-dollar me-1"></i>
                        {{ __('dashboard.pending.advances') }}
                        <span class="badge bg-warning ms-1">{{ $pending['advances'] }}</span>
                    </a>
                @endif

                @if ($pending['penalties'])
                    <a href="{{ route('admin.penalties.index') }}" class="btn btn-sm btn-label-danger">
                        <i class="fa-solid fa-gavel me-1"></i>
                        {{ __('dashboard.pending.penalties') }}
                        <span class="badge bg-danger ms-1">{{ $pending['penalties'] }}</span>
                    </a>
                @endif

                @if ($pending['rewards'])
                    <a href="{{ route('admin.rewards.index') }}" class="btn btn-sm btn-label-success">
                        <i class="fa-solid fa-award me-1"></i>
                        {{ __('dashboard.pending.rewards') }}
                        <span class="badge bg-success ms-1">{{ $pending['rewards'] }}</span>
                    </a>
                @endif

                @if ($pending['payrolls'])
                    <a href="{{ route('admin.payrolls.index') }}" class="btn btn-sm btn-label-info">
                        <i class="fa-solid fa-money-check-dollar me-1"></i>
                        {{ __('dashboard.pending.payrolls') }}
                        <span class="badge bg-info ms-1">{{ $pending['payrolls'] }}</span>
                    </a>
                @endif

                @if ($alerts['overdue_invoices'])
                    <a href="{{ route('admin.sales.index', ['payment_status' => 'unpaid']) }}" class="btn btn-sm btn-label-danger">
                        <i class="fa-solid fa-file-invoice-dollar me-1"></i>
                        {{ __('dashboard.alerts.overdue_invoices') }}
                        <span class="badge bg-danger ms-1">{{ $alerts['overdue_invoices'] }}</span>
                    </a>
                @endif

                @if ($alerts['due_subscriptions'])
                    <a href="{{ route('admin.vendor-subscriptions.index') }}" class="btn btn-sm btn-label-warning">
                        <i class="fa-solid fa-repeat me-1"></i>
                        {{ __('dashboard.alerts.due_subscriptions') }}
                        <span class="badge bg-warning ms-1">{{ $alerts['due_subscriptions'] }}</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
@endif


{{-- ═══════════ فلاتر اللوحة ═══════════ --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form id="dashboardFilters" class="row g-2 align-items-end">

            {{-- الأعمدة بتتوزّع على 12 في الحالتين: بحقول الفترة
                 المخصصة (3+4+4+1) ومن غيرها (5+6+1) --}}
            <div class="col-md-{{ $filter['preset'] === 'custom' ? '3' : '5' }}" id="presetCol">
                <label class="form-label small mb-1">
                    {{ __('dashboard.filters.period') }}
                    <x-inputs.help :text="__('dashboard.hints.period')" />
                </label>
                <select name="preset" id="filterPreset" class="form-select select2">
                    @foreach (__('dashboard.presets') as $presetKey => $presetLabel)
                        <option value="{{ $presetKey }}" @selected($filter['preset'] === $presetKey)>{{ $presetLabel }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 {{ $filter['preset'] === 'custom' ? '' : 'd-none' }}" id="customRange">
                <label class="form-label small mb-1">
                    {{ __('dashboard.filters.range_label') }}
                    <x-inputs.help :text="__('dashboard.hints.range')" />
                </label>
                <div class="input-group">
                    <input type="date" name="date_from" class="form-control px-2" style="height: 38px;"
                           value="{{ $filter['date_from'] }}">
                    <input type="date" name="date_to" class="form-control px-2" style="height: 38px;"
                           value="{{ $filter['date_to'] }}">
                </div>
            </div>

            <div class="col-md-{{ $filter['preset'] === 'custom' ? '4' : '6' }}" id="branchCol">
                <label class="form-label small mb-1">
                    {{ __('dashboard.filters.branch') }}
                    <x-inputs.help :text="__('dashboard.hints.branch')" />
                </label>
                <select name="branch_id" class="form-select select2">
                    <option value="">{{ __('dashboard.filters.all_branches') }}</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((int) $filter['branch_id'] === (int) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>


            <div class="col-md-1 d-grid">
                <button type="button" id="resetDashboardFilters" class="btn btn-label-secondary"
                        style="height: 38px;"
                        data-bs-toggle="tooltip" title="{{ __('global.reset_filter') }}">
                    <i class="fa-solid fa-rotate-left"></i>
                </button>
            </div>

        </form>
    </div>
</div>

{{-- ═══════════ الملخص المالي ═══════════ --}}
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h5 class="mb-0 fw-bold text-dark">
        <i class="fa-solid fa-chart-line text-primary me-2"></i>
        {{ __('dashboard.financial_summary') }}
        <span class="badge bg-label-primary ms-1" id="periodLabel">{{ $period['label'] }}</span>
        <span class="spinner-border spinner-border-sm text-primary ms-1 d-none" id="dashboardSpinner"></span>
    </h5>

    @if (admin_can('report_financial_pl'))
        <a href="{{ route('admin.reports.financial-pl') }}" class="btn btn-sm btn-label-primary">
            <i class="fa-solid fa-file-lines me-1"></i> {{ __('dashboard.view_full_report') }}
        </a>
    @endif
</div>

<div id="financialSummary">
@forelse ($financial as $row)
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-3 align-items-center">

                        <div class="col-md-2 text-center border-end">
                            <span class="badge bg-label-primary fs-6 px-3 py-2">{{ $row['currency'] }}</span>
                            <div class="small text-muted mt-2" data-bs-toggle="tooltip"
                                 title="{{ __('dashboard.hints.invoice_count') }}">
                                <span dir="ltr">{{ $row['invoices'] }}</span> {{ __('dashboard.invoice_count') }}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar avatar-sm rounded bg-label-success d-flex align-items-center justify-content-center">
                                    <i class="fa-solid fa-arrow-trend-up"></i>
                                </span>
                                <div>
                                    <div class="small text-muted">{{ __('dashboard.revenue') }} <x-inputs.help :text="__('dashboard.hints.revenue')" /></div>
                                    <h6 class="mb-0 fw-bold text-success" dir="ltr">{{ number_format($row['revenue'], 2) }}</h6>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar avatar-sm rounded bg-label-warning d-flex align-items-center justify-content-center">
                                    <i class="fa-solid fa-arrow-trend-down"></i>
                                </span>
                                <div>
                                    <div class="small text-muted">{{ __('dashboard.expenses') }} <x-inputs.help :text="__('dashboard.hints.expenses')" /></div>
                                    <h6 class="mb-0 fw-bold text-warning" dir="ltr">{{ number_format($row['expenses'], 2) }}</h6>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar avatar-sm rounded bg-label-{{ $row['net'] >= 0 ? 'primary' : 'danger' }} d-flex align-items-center justify-content-center">
                                    <i class="fa-solid fa-scale-balanced"></i>
                                </span>
                                <div>
                                    <div class="small text-muted">{{ __('dashboard.net') }} <x-inputs.help :text="__('dashboard.hints.net')" /></div>
                                    <h6 class="mb-0 fw-bold text-{{ $row['net'] >= 0 ? 'primary' : 'danger' }}" dir="ltr">
                                        {{ $row['net'] >= 0 ? '+' : '' }}{{ number_format($row['net'], 2) }}
                                    </h6>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar avatar-sm rounded bg-label-danger d-flex align-items-center justify-content-center">
                                    <i class="fa-solid fa-clock"></i>
                                </span>
                                <div>
                                    <div class="small text-muted">{{ __('dashboard.outstanding') }} <x-inputs.help :text="__('dashboard.hints.outstanding')" /></div>
                                    <h6 class="mb-0 fw-bold text-danger" dir="ltr">{{ number_format($row['due'], 2) }}</h6>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body text-center py-5">
            <i class="fa-solid fa-chart-simple text-muted mb-3" style="font-size: 2.5rem; opacity: .25;"></i>
            <h6 class="text-muted">{{ __('dashboard.no_financial_data') }}</h6>
            <p class="small text-muted mb-0">{{ __('dashboard.no_financial_hint') }}</p>
        </div>
    </div>
@endforelse
</div>


{{-- ═══════════ الرسوم البيانية ═══════════ --}}
<div class="row g-4 mb-4">

    {{-- اتجاه الإيرادات والمصروفات --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold">
                    <i class="fa-solid fa-chart-area text-primary me-2"></i>
                    {{ __('dashboard.charts.trend') }}
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-label-secondary" id="trendCurrency" data-bs-toggle="tooltip"
                          title="{{ __('dashboard.hints.trend_currency') }}">
                        {{ __('dashboard.all_currencies') }}
                    </span>
                <div class="dropdown">
                    <button class="btn btn-sm btn-label-secondary dropdown-toggle chart-type-btn"
                            type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                            data-target="trend"
                            aria-expanded="false"
                            title="{{ __('dashboard.chart_types.change') }}">
                        <i class="fa-solid fa-chart-simple"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><h6 class="dropdown-header">{{ __('dashboard.chart_types.change') }}</h6></li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="trend" data-type="line">
                                <i class="fa-solid fa-chart-line fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.line') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="trend" data-type="area">
                                <i class="fa-solid fa-chart-area fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.area') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="trend" data-type="column">
                                <i class="fa-solid fa-chart-column fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.column') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="trend" data-type="bar">
                                <i class="fa-solid fa-chart-bar fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.bar') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="trend" data-type="donut">
                                <i class="fa-solid fa-chart-pie fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.donut') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="trend" data-type="pie">
                                <i class="fa-solid fa-circle-half-stroke fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.pie') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="trend" data-type="pieLabeled">
                                <i class="fa-solid fa-share-nodes fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.pieLabeled') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="trend" data-type="radar">
                                <i class="fa-solid fa-spider fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.radar') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="trend" data-type="treemap">
                                <i class="fa-solid fa-table-cells-large fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.treemap') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="trend" data-type="polarArea">
                                <i class="fa-solid fa-bullseye fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.polarArea') }}</span>
                            </button>
                        </li>
                    </ul>
                </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="chartTrend"></div>
            </div>
        </div>
    </div>

    {{-- توزيع المصروفات --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold">
                    <i class="fa-solid fa-chart-pie text-warning me-2"></i>
                    {{ __('dashboard.charts.expense_split') }}
                    <x-inputs.help :text="__('dashboard.hints.expense_split')" />
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-label-warning" id="expenseCurrency">{{ __('dashboard.all_currencies') }}</span>
                <div class="dropdown">
                    <button class="btn btn-sm btn-label-warning dropdown-toggle chart-type-btn"
                            type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                            data-target="expenses"
                            aria-expanded="false"
                            title="{{ __('dashboard.chart_types.change') }}">
                        <i class="fa-solid fa-chart-simple"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><h6 class="dropdown-header">{{ __('dashboard.chart_types.change') }}</h6></li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="expenses" data-type="line">
                                <i class="fa-solid fa-chart-line fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.line') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="expenses" data-type="area">
                                <i class="fa-solid fa-chart-area fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.area') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="expenses" data-type="column">
                                <i class="fa-solid fa-chart-column fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.column') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="expenses" data-type="bar">
                                <i class="fa-solid fa-chart-bar fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.bar') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="expenses" data-type="donut">
                                <i class="fa-solid fa-chart-pie fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.donut') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="expenses" data-type="pie">
                                <i class="fa-solid fa-circle-half-stroke fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.pie') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="expenses" data-type="pieLabeled">
                                <i class="fa-solid fa-share-nodes fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.pieLabeled') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="expenses" data-type="radar">
                                <i class="fa-solid fa-spider fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.radar') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="expenses" data-type="treemap">
                                <i class="fa-solid fa-table-cells-large fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.treemap') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="expenses" data-type="polarArea">
                                <i class="fa-solid fa-bullseye fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.polarArea') }}</span>
                            </button>
                        </li>
                    </ul>
                </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="chartExpenses"></div>
            </div>
        </div>
    </div>

</div>

<div class="row g-4 mb-4">

    {{-- المبيعات حسب القناة --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold">
                    <i class="fa-solid fa-store text-info me-2"></i>
                    {{ __('dashboard.charts.sales_channels') }}
                    <x-inputs.help :text="__('dashboard.hints.sales_channels')" />
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-label-info" id="channelsCurrency">{{ __('dashboard.all_currencies') }}</span>
                <div class="dropdown">
                    <button class="btn btn-sm btn-label-info dropdown-toggle chart-type-btn"
                            type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                            data-target="channels"
                            aria-expanded="false"
                            title="{{ __('dashboard.chart_types.change') }}">
                        <i class="fa-solid fa-chart-simple"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><h6 class="dropdown-header">{{ __('dashboard.chart_types.change') }}</h6></li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="channels" data-type="line">
                                <i class="fa-solid fa-chart-line fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.line') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="channels" data-type="area">
                                <i class="fa-solid fa-chart-area fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.area') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="channels" data-type="column">
                                <i class="fa-solid fa-chart-column fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.column') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="channels" data-type="bar">
                                <i class="fa-solid fa-chart-bar fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.bar') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="channels" data-type="donut">
                                <i class="fa-solid fa-chart-pie fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.donut') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="channels" data-type="pie">
                                <i class="fa-solid fa-circle-half-stroke fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.pie') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="channels" data-type="pieLabeled">
                                <i class="fa-solid fa-share-nodes fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.pieLabeled') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="channels" data-type="radar">
                                <i class="fa-solid fa-spider fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.radar') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="channels" data-type="treemap">
                                <i class="fa-solid fa-table-cells-large fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.treemap') }}</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="channels" data-type="polarArea">
                                <i class="fa-solid fa-bullseye fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.polarArea') }}</span>
                            </button>
                        </li>
                    </ul>
                </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="chartChannels"></div>
            </div>
        </div>
    </div>

    {{-- أعلى العملاء --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0 fw-bold">
                    <i class="fa-solid fa-crown text-warning me-2"></i>
                    {{ __('dashboard.top_customers') }}
                </h6>
            </div>
            <div class="card-body">
                @forelse ($topCustomers as $i => $customer)
                    <div class="d-flex align-items-center gap-3 {{ !$loop->last ? 'mb-3 pb-3 border-bottom' : '' }}">
                        <span class="badge bg-label-{{ ['primary','info','success','warning','secondary'][$i] ?? 'secondary' }} rounded-circle"
                              style="width:32px;height:32px;line-height:22px;">{{ $i + 1 }}</span>

                        <div class="flex-grow-1">
                            <h6 class="mb-0 fw-semibold">{{ $customer['label'] }}</h6>
                            <small class="text-muted">{{ $customer['invoices'] }} {{ __('dashboard.invoice_count') }}</small>
                        </div>

                        <h6 class="mb-0 fw-bold text-success" dir="ltr">{{ number_format($customer['total'], 2) }}</h6>
                    </div>
                @empty
                    <p class="text-muted text-center small py-5 mb-0">{{ __('dashboard.no_data') }}</p>
                @endforelse
            </div>
        </div>
    </div>

</div>


{{-- ═══════════ الموارد البشرية ═══════════ --}}
<h5 class="mb-3 fw-bold text-dark">
    <i class="fa-solid fa-users text-primary me-2"></i> {{ __('dashboard.hr_summary') }}
</h5>

<div class="row g-4 mb-4">

    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h3 class="mb-0 fw-bold text-success">{{ $hr['active'] }}</h3>
                        <small class="text-muted">{{ __('dashboard.hr.active') }}</small>
                    </div>
                    <span class="avatar rounded bg-label-success d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-user-check"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h3 class="mb-0 fw-bold text-warning">{{ $hr['on_leave'] }}</h3>
                        <small class="text-muted">{{ __('dashboard.hr.on_leave') }}</small>
                    </div>
                    <span class="avatar rounded bg-label-warning d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-plane-departure"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h3 class="mb-0 fw-bold text-info">{{ $hr['new_hires'] }}</h3>
                        <small class="text-muted">{{ __('dashboard.hr.new_hires') }}</small>
                    </div>
                    <span class="avatar rounded bg-label-info d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-user-plus"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h3 class="mb-0 fw-bold text-primary">{{ $hr['total'] }}</h3>
                        <small class="text-muted">{{ __('dashboard.hr.total') }}</small>
                    </div>
                    <span class="avatar rounded bg-label-primary d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-users"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

</div>

@if (count($hr['by_branch']) > 1)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0 fw-bold">
                <i class="fa-solid fa-building text-primary me-2"></i>
                {{ __('dashboard.charts.employees_by_branch') }}
            </h6>
            <div class="dropdown">
                <button class="btn btn-sm btn-label-primary dropdown-toggle chart-type-btn"
                        type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                        data-target="branches" aria-expanded="false"
                        title="{{ __('dashboard.chart_types.change') }}">
                    <i class="fa-solid fa-chart-simple"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><h6 class="dropdown-header">{{ __('dashboard.chart_types.change') }}</h6></li>
                    <li>
                        <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                data-target="branches" data-type="line">
                            <i class="fa-solid fa-chart-line fa-fw"></i>
                            <span>{{ __('dashboard.chart_types.line') }}</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                data-target="branches" data-type="area">
                            <i class="fa-solid fa-chart-area fa-fw"></i>
                            <span>{{ __('dashboard.chart_types.area') }}</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                data-target="branches" data-type="column">
                            <i class="fa-solid fa-chart-column fa-fw"></i>
                            <span>{{ __('dashboard.chart_types.column') }}</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                data-target="branches" data-type="bar">
                            <i class="fa-solid fa-chart-bar fa-fw"></i>
                            <span>{{ __('dashboard.chart_types.bar') }}</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                data-target="branches" data-type="donut">
                            <i class="fa-solid fa-chart-pie fa-fw"></i>
                            <span>{{ __('dashboard.chart_types.donut') }}</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                data-target="branches" data-type="pie">
                            <i class="fa-solid fa-circle-half-stroke fa-fw"></i>
                            <span>{{ __('dashboard.chart_types.pie') }}</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                data-target="branches" data-type="pieLabeled">
                            <i class="fa-solid fa-share-nodes fa-fw"></i>
                            <span>{{ __('dashboard.chart_types.pieLabeled') }}</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                data-target="branches" data-type="radar">
                            <i class="fa-solid fa-spider fa-fw"></i>
                            <span>{{ __('dashboard.chart_types.radar') }}</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                data-target="branches" data-type="treemap">
                            <i class="fa-solid fa-table-cells-large fa-fw"></i>
                            <span>{{ __('dashboard.chart_types.treemap') }}</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                data-target="branches" data-type="polarArea">
                            <i class="fa-solid fa-bullseye fa-fw"></i>
                            <span>{{ __('dashboard.chart_types.polarArea') }}</span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
        <div class="card-body">
            <div id="chartBranches"></div>
        </div>
    </div>
@endif

@endsection


@push('js')
<script src="/admin/vendor/libs/apex-charts/apexcharts.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // الألوان من متغيرات الثيم عشان الرسوم تتماشى مع الوضع الليلي والنهاري
    var styles      = getComputedStyle(document.documentElement);
    var isDark      = document.documentElement.getAttribute('data-bs-theme') === 'dark';

    // النص الثانوي: تسميات المحاور والأسطورة
    var textMuted   = (styles.getPropertyValue('--bs-secondary-color') || '').trim()
        || (isDark ? '#a1a5b7' : '#8592a3');

    // النص الأساسي: الأرقام والأسماء اللي المستخدم بيقراها فعلاً.
    // كانت بلون النص الثانوي الباهت فكانت صعبة القراءة.
    var textStrong  = (styles.getPropertyValue('--bs-body-color') || '').trim()
        || (isDark ? '#e7e6f1' : '#5d596c');

    var borderColor = (styles.getPropertyValue('--bs-border-color') || '').trim()
        || (isDark ? '#44485e' : '#e6e6e8');

    // لون النص فوق الشرايح الملوّنة — أبيض في الوضعين لأن ألوان
    // اللوحة كلها داكنة بما يكفي.
    var textOnFill  = '#ffffff';

    // خلفية البطاقة: بتتستخدم كحد فاصل بين الشرايح المتقاربة.
    // --bs-card-bg معرّف جوه .card بس، فبنحدده صراحةً.
    var surfaceColor = isDark ? '#2f3349' : '#ffffff';

    var isRtl   = document.documentElement.getAttribute('dir') === 'rtl';
    var PALETTE = ['#7367f0', '#28c76f', '#ff9f43', '#ea5455', '#00cfe8', '#a8aaae'];

    var baseOptions = {
        chart: { toolbar: { show: false }, fontFamily: 'inherit' },
        grid:  { borderColor: borderColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        legend: { labels: { colors: textStrong } },
        tooltip: { rtl: isRtl },
    };

    var L = {
        revenue:  @json(__('dashboard.revenue')),
        expenses: @json(__('dashboard.expenses')),
        total:    @json(__('dashboard.total')),
        noData:   @json(__('dashboard.no_data')),
        net:      @json(__('dashboard.net')),
        due:      @json(__('dashboard.outstanding')),
        invoices: @json(__('dashboard.invoice_count')),
        error:    @json(__('dashboard.filters.load_error')),
        ofCurrencies: @json(__('dashboard.of_currencies')),
    };

    var nf  = new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    var nf0 = new Intl.NumberFormat('en');

    // ====================================================
    //  مدير الرسوم
    // ====================================================
    /**
     * ApexCharts بيربط yaxis بالسلاسل بالفهرس. لو حدّثنا الخيارات
     * والسلاسل في نداءين وعدد السلاسل اتغيّر، بيقرأ فهرس مش موجود
     * ويرمي "Cannot read properties of undefined".
     *
     * فبنعيد البناء لما العدد يتغيّر، ونحدّث بنداء واحد لما يفضل زيّه.
     */
    function Chart(selector, build, name, defaultType, labelsOf, options) {
        options = options || {};

        this.el          = document.querySelector(selector);
        this.build       = build;
        this.name        = name;
        this.defaultType = defaultType;
        this.labelsOf    = labelsOf || function () { return []; };
        this.currencyOf  = options.currencyOf || null;
        this.badge       = options.badge ? document.querySelector(options.badge) : null;
        this.api         = null;
        this.length      = -1;
        this.lastData    = null;
    }

    /** بيحدّث البادج بالعملات المعروضة فعلاً في الشكل الحالي. */
    Chart.prototype.updateBadge = function (data) {
        if (!this.badge || !this.currencyOf) return;

        this.badge.textContent = currencyLabel(this.currencyOf(data), this.type());
    };

    /** الشكل الحالي: اللي المستخدم اختاره أو الافتراضي. */
    Chart.prototype.type = function () {
        return chartTypes[this.name] || this.defaultType;
    };

    Chart.prototype.render = function (data) {
        if (!this.el) return;

        this.lastData = data;
        this.updateBadge(data);

        // في الأشكال اللي بتعرض عملة واحدة بنمرّرها عشان تظهر
        // جنب كل رقم. الأشكال متعددة العملات كل سلسلة اسمها
        // العملة بتاعتها فالتلميح بيوضّحها لوحده.
        var shown    = this.currencyOf ? this.currencyOf(data) : [];
        var currency = isSingleCurrency(this.type()) ? (shown[0] || '') : '';

        var options = applyType(this.build(data), this.type(), this.labelsOf(data), currency);
        var count   = (options.series || []).length;

        // مفيش بيانات — نفضي المكان بدل ما نرسم رسم فاضي
        if (!count) {
            this.destroy();
            this.el.innerHTML = '<p class="text-muted text-center small py-5 mb-0">' + L.noData + '</p>';
            this.length = 0;
            return;
        }

        var sameType = this.renderedType === this.type();

        if (this.api && this.length === count && sameType) {
            // نفس النوع ونفس عدد السلاسل — تحديث آمن بنداء واحد
            try {
                this.api.updateOptions(options, false, false);
                return;
            } catch (e) {
                // لو حصل أي تعارض بنرجع لإعادة البناء
            }
        }

        this.destroy();
        this.el.innerHTML = '';
        this.api = new ApexCharts(this.el, options);
        this.api.render();
        this.length       = count;
        this.renderedType = this.type();
    };

    /** إعادة الرسم بشكل جديد على نفس البيانات. */
    Chart.prototype.setType = function (type) {
        chartTypes[this.name] = type;
        saveType(this.name, type);

        if (this.lastData) {
            // render بيحدّث البادج كمان، فالوصف بيتغيّر مع الشكل
            this.render(this.lastData);
        }
    };

    Chart.prototype.destroy = function () {
        if (this.api) {
            try { this.api.destroy(); } catch (e) {}
            this.api = null;
        }
    };

    // ====================================================
    //  تحويل بيانات العملات لسلاسل
    // ====================================================

    /** بيجمع كل التسميات ويبني سلسلة لكل عملة. */
    function byCurrency(payload) {
        var groups = (payload && payload.groups) || [];
        var labels = [];

        groups.forEach(function (g) {
            (g.rows || []).forEach(function (r) {
                if (labels.indexOf(r.label) === -1) labels.push(r.label);
            });
        });

        var series = groups.map(function (g) {
            var byLabel = {};
            (g.rows || []).forEach(function (r) { byLabel[r.label] = parseFloat(r.total); });

            return {
                name: g.currency,
                data: labels.map(function (l) { return byLabel[l] || 0; })
            };
        });

        return { labels: labels, series: series };
    }

    /**
     * أعمدة أفقية والتسمية جوه العمود.
     *
     * الاسم جوه الرسم بدل محور جانبي، فالرسم بياخد العرض كله
     * والأسماء الطويلة مابتتقصّش.
     */
    function insideBarOptions(data) {
        return Object.assign({}, baseOptions, {
            chart:  Object.assign({}, baseOptions.chart, { type: 'bar', height: 320 }),
            colors: PALETTE,
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    barHeight: '70%',
                    dataLabels: { position: 'center' },
                },
            },
            dataLabels: {
                enabled: true,
                textAnchor: 'middle',
                style: { fontSize: '12px', colors: ['#fff'] },
                formatter: function (value, opt) {
                    if (!value) return '';
                    var name = data.labels[opt.dataPointIndex] || '';
                    return name + ' — ' + nf0.format(value);
                },
                dropShadow: { enabled: true, blur: 2, opacity: 0.4 },
            },
            series: data.series,
            legend: {
                show: true,
                position: 'top',
                horizontalAlign: isRtl ? 'right' : 'left',
                labels: { colors: textStrong },
            },
            xaxis: {
                categories: data.labels,
                labels: { style: { colors: textMuted }, formatter: function (v) { return nf0.format(v); } },
            },
            // المحور الجانبي مخفي لأن الاسم بقى جوه العمود
            yaxis: { labels: { show: false } },
            tooltip: { rtl: isRtl, y: { formatter: function (v) { return nf.format(v); } } },
        });
    }

    // ====================================================
    //  تبديل شكل الرسم
    // ====================================================

    /** الشكل المختار لكل رسم، محفوظ في المتصفح. */
    var CHART_TYPE_KEY = 'sbbs_chart_types';

    function loadTypes() {
        try {
            return JSON.parse(localStorage.getItem(CHART_TYPE_KEY)) || {};
        } catch (e) {
            return {};
        }
    }

    function saveType(target, type) {
        try {
            var all = loadTypes();
            all[target] = type;
            localStorage.setItem(CHART_TYPE_KEY, JSON.stringify(all));
        } catch (e) {
            // التخزين ممكن يكون مقفول (وضع خاص) — الشكل يفضل للجلسة بس
        }
    }

    var chartTypes = loadTypes();

    /** الأشكال اللي بتاخد قيمة واحدة لكل تسمية بدل سلاسل. */
    function isCircular(type) {
        return ['pie', 'pieLabeled', 'donut', 'polarArea', 'radialBar'].indexOf(type) !== -1;
    }

    /**
     * بيحوّل سلاسل متعددة لقيمة واحدة لكل تسمية + تفصيل بالعملة.
     *
     * الشكل الدائري محتاج رقم واحد لكل شريحة عشان يرسم حجمها،
     * فبناخد المجموع. لكن التفصيل بيتحفظ عشان التسمية والتلميح
     * يعرضوا كل عملة برقمها — جمع عملات في رقم واحد من غير تفصيل
     * بيبقى مضلّل.
     */
    function flattenSeries(series, labels) {
        if (!series.length) return { values: [], labels: labels, breakdown: [] };

        var values    = [];
        var breakdown = [];

        (labels || []).forEach(function (label, i) {
            var perCurrency = [];
            var sum = 0;

            series.forEach(function (serie) {
                var amount = Math.abs(parseFloat((serie.data || [])[i]) || 0);

                if (amount > 0) {
                    perCurrency.push({ currency: serie.name, amount: amount });
                    sum += amount;
                }
            });

            values.push(sum);
            breakdown.push(perCurrency);
        });

        return { values: values, labels: labels, breakdown: breakdown };
    }

    /**
     * سطر لكل عملة: "2,700.00 EGP".
     *
     * بيرجّع مصفوفة عشان ApexCharts بيعرض كل عنصر في سطر.
     */
    function currencyLines(entries) {
        return (entries || []).map(function (entry) {
            return nf.format(entry.amount) + ' ' + entry.currency;
        });
    }

    /**
     * الأشكال اللي بتعرض عملة واحدة بس.
     *
     * دي بتعرض قيمة واحدة لكل تسمية (نسبة من كلّ)، فجمع عملات
     * مختلفة فيها بيدي رقم مالوش معنى — بنعرض أول عملة بس.
     */
    function isSingleCurrency(type) {
        // الدائرية بقت تعرض كل العملات في التسمية، فالمتبقي
        // بعملة واحدة هو خريطة المساحات والرادار بس.
        return type === 'treemap' || type === 'radar';
    }

    /**
     * وصف العملات المعروضة فعلاً في الشكل الحالي.
     *
     * @param {Array}  currencies كل العملات المتاحة في البيانات
     * @param {string} type       الشكل المختار
     */
    function currencyLabel(currencies, type) {
        if (!currencies.length) {
            return L.noData;
        }

        if (isSingleCurrency(type)) {
            // عملة واحدة معروضة — بنقول اسمها وإن في غيرها
            return currencies.length > 1
                ? currencies[0] + ' ' + L.ofCurrencies.replace(':count', currencies.length)
                : currencies[0];
        }

        // كل العملات معروضة، كل واحدة سلسلة مستقلة
        return currencies.join(' · ');
    }

    /** الخيارات اللي بتخص شكل معيّن ولازم تتشال قبل التبديل. */
    var TYPE_SPECIFIC = [
        'plotOptions', 'dataLabels', 'labels', 'stroke', 'fill',
        'xaxis', 'yaxis', 'markers', 'grid', 'legend', 'colors',
    ];

    /**
     * بيطبّق الشكل المختار على خيارات الرسم.
     *
     * Object.assign بينسخ سطحي، فخيارات الشكل القديم كانت بتفضل
     * وتتعارض مع الجديد — الرادار مثلاً كان بياخد formatter بتاع
     * الأعمدة و dashArray بطول مش مطابق لعدد سلاسله فيطلع بايظ.
     * فبنبدأ من نسخة نضيفة وبنبني خيارات الشكل من الأول.
     */
    function applyType(options, type, labels, currency) {
        var out = {};

        // العملة بتتحط جنب كل رقم — من غيرها الرقم مالوش معنى
        // خصوصاً في الأشكال اللي بتعرض عملة واحدة من كذا.
        var money = function (v) {
            var amount = nf.format(parseFloat(v) || 0);

            return currency ? amount + ' ' + currency : amount;
        };

        // ناخد كل حاجة مش خاصة بشكل معيّن (series, tooltip, ...)
        Object.keys(options).forEach(function (key) {
            if (TYPE_SPECIFIC.indexOf(key) === -1) {
                out[key] = options[key];
            }
        });

        out.chart  = Object.assign({}, options.chart, { type: type, stacked: false });
        out.colors = PALETTE;
        out.grid   = { borderColor: borderColor, strokeDashArray: 4 };
        out.tooltip = {
            rtl: isRtl,
            y: {
                formatter: function (value, opt) {
                    if (currency) {
                        return money(value);
                    }

                    // الأشكال متعددة العملات: اسم السلسلة هو العملة
                    var unit = opt && opt.w && opt.w.globals.seriesNames
                        ? opt.w.globals.seriesNames[opt.seriesIndex]
                        : '';

                    // اسم السلسلة ممكن يكون "الإيرادات · EGP"
                    var code = unit ? String(unit).split('·').pop().trim() : '';

                    return nf.format(parseFloat(value) || 0) + (code ? ' ' + code : '');
                },
            },
        };

        // ---------- الأشكال الدائرية ----------
        if (isCircular(type)) {
            var flat = flattenSeries(options.series || [], labels || []);

            out.series = flat.values;
            out.labels = flat.labels;

            // التلميح بيعرض كل عملة في سطر بدل رقم مجمّع مالوش معنى
            out.tooltip = {
                rtl: isRtl,
                custom: function (ctx) {
                    var i     = ctx.seriesIndex;
                    var name  = flat.labels[i] || '';
                    var lines = currencyLines(flat.breakdown[i]);

                    return '<div class="px-2 py-1">'
                        + '<div class="fw-bold mb-1">' + name + '</div>'
                        + lines.map(function (line) {
                            return '<div dir="ltr" class="text-end">' + line + '</div>';
                        }).join('')
                        + '</div>';
                },
            };

            // الأسطورة بتعرض الاسم بس — التفاصيل على الرسم وفي التلميح
            out.legend = { show: true, position: 'bottom', labels: { colors: textStrong } };

            // الاسم في سطر وكل عملة في سطر تحته.
            // النص أبيض بظل خفيف عشان يبان فوق أي لون شريحة —
            // لون النص الثانوي كان باهت وصعب القراءة في الوضعين.
            out.dataLabels = {
                enabled: true,
                style: {
                    fontSize: '11px',
                    fontWeight: 600,
                    colors: [textOnFill],
                },
                dropShadow: { enabled: true, blur: 3, opacity: 0.5 },
                formatter: function (value, opt) {
                    var i = opt.seriesIndex;

                    return [flat.labels[i] || ''].concat(currencyLines(flat.breakdown[i]));
                },
            };

            // حد فاصل بلون الخلفية عشان الشرايح المتقاربة تتفصل
            out.stroke = { width: 2, colors: [surfaceColor] };

            out.plotOptions = {};

            // الشكل ده بيطلّع خط من كل قسم والتسمية في نهايته
            if (type === 'pieLabeled') {
                out.chart.type = 'pie';

                // offset سالب بيطلّع التسمية برّه الشريحة — الموجب
                // كان بيدخّلها جوه فتتداخل مع الألوان.
                out.plotOptions = {
                    pie: {
                        dataLabels: { offset: -18, minAngleToShowLabel: 12 },
                        expandOnClick: true,
                        customScale: 0.82,
                    },
                };

                // برّه الشريحة النص على خلفية البطاقة، فبياخد
                // لون النص الأساسي مش الأبيض.
                out.dataLabels = Object.assign({}, out.dataLabels, {
                    style: { fontSize: '11px', fontWeight: 600, colors: [textStrong] },
                    dropShadow: { enabled: false },
                });

                out.legend = { show: false };

                return out;
            }

            if (type === 'donut') {
                // جوه الدونات التسمية بتتزحم، فبنسيب الأرقام
                // للتلميح والإجمالي في النص.
                out.dataLabels = {
                    enabled: true,
                    style: { fontSize: '12px', fontWeight: 600, colors: [textOnFill] },
                    dropShadow: { enabled: true, blur: 2, opacity: 0.45 },
                    formatter: function (percent) {
                        return percent.toFixed(1) + '%';
                    },
                };

                out.plotOptions = {
                    pie: {
                        donut: {
                            labels: {
                                show: true,
                                name: { fontSize: '13px', color: textStrong },
                                value: {
                                    fontSize: '20px',
                                    fontWeight: 600,
                                    color: textStrong,
                                    formatter: function (value) {
                                        return nf.format(parseFloat(value) || 0);
                                    },
                                },
                                total: {
                                    show: true,
                                    label: L.total,
                                    color: textStrong,
                                    formatter: function (w) {
                                        var sum = (w.globals.seriesTotals || [])
                                            .reduce(function (a, b) { return a + b; }, 0);

                                        return nf.format(sum);
                                    },
                                },
                            },
                        },
                    },
                };
            }

            return out;
        }

        // ---------- خريطة المساحات ----------
        if (type === 'treemap') {
            var flatT = flattenSeries(options.series || [], labels || []);

            out.series = [{
                data: flatT.labels.map(function (label, i) {
                    return { x: label, y: flatT.values[i] || 0 };
                }),
            }];
            out.plotOptions = { treemap: { distributed: true, enableShades: false } };
            out.dataLabels  = {
                enabled: true,
                style: { fontSize: '12px', fontWeight: 600, colors: [textOnFill] },
                dropShadow: { enabled: true, blur: 2, opacity: 0.45 },
                formatter: function (text, opt) {
                    return [text, money(opt.value)];
                },
            };
            out.legend = { show: false };

            return out;
        }

        // ---------- الرادار ----------
        if (type === 'radar') {
            var radarSeries = options.series || [];

            // السلاسل اللي كلها أصفار بتطلع خط في المركز — بنشيلها.
            // ده بيحصل لما كل عملة يكون ليها تسميات مختلفة والباقي
            // بيتبطّن بأصفار.
            var meaningful = radarSeries.filter(function (serie) {
                return (serie.data || []).some(function (v) { return parseFloat(v) > 0; });
            });

            // عملة واحدة بس: مقارنة عملات مختلفة على نفس المقياس
            // بتدي شكل مضلّل (٢٧٠٠ جنيه جنب ٢٨٠ دولار).
            out.series = meaningful.length ? [meaningful[0]] : radarSeries.slice(0, 1);

            out.xaxis = {
                categories: labels || [],
                labels: { style: { colors: [textMuted], fontSize: '11px' } },
            };

            // المحور لازم يفضل شغّال — من غيره ApexCharts مابيعرفش
            // يحسب نصف قطر الشبكة وكل النقط بتقع في المركز.
            out.yaxis = { show: false, labels: { formatter: function (v) { return nf0.format(v); } } };

            out.dataLabels = { enabled: false };
            out.stroke  = { width: 2 };
            out.fill    = { opacity: 0.25 };
            out.markers = { size: 4, hover: { size: 6 } };

            out.plotOptions = {
                radar: {
                    polygons: {
                        strokeColors: borderColor,
                        connectorColors: borderColor,
                        fill: { colors: ['transparent'] },
                    },
                },
            };

            out.legend = {
                show: true, position: 'top',
                horizontalAlign: isRtl ? 'right' : 'left',
                labels: { colors: textStrong },
            };

            return out;
        }

        // ---------- الخط والمساحة ----------
        if (type === 'line' || type === 'area') {
            out.series = options.series || [];
            out.stroke = { curve: 'smooth', width: 2 };
            out.dataLabels = { enabled: false };
            out.plotOptions = {};
            out.markers = { size: 0, hover: { size: 5 } };

            if (type === 'area') {
                out.fill = { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } };
            }

            out.xaxis = {
                categories: labels || [],
                labels: { style: { colors: textMuted } },
                axisBorder: { show: false },
            };
            out.yaxis = {
                opposite: isRtl,
                labels: { style: { colors: textMuted }, formatter: function (v) { return nf0.format(v); } },
            };
            out.legend = {
                show: true, position: 'top',
                horizontalAlign: isRtl ? 'right' : 'left',
                labels: { colors: textStrong },
            };

            return out;
        }

        // ---------- الأعمدة ----------
        // bar = أفقية، column = رأسية — الاتنين نوعهم bar في
        // ApexCharts والفرق في horizontal.
        var horizontal = type === 'bar';

        out.chart.type = 'bar';
        out.series = options.series || [];
        out.plotOptions = {
            bar: { horizontal: horizontal, borderRadius: 4, barHeight: '70%', columnWidth: '55%' },
        };

        if (horizontal) {
            // الأفقي: الرقم جوه العمود والاسم على المحور الجانبي.
            //
            // كان الاتنين جوه العمود، فالعمود القصير نصه بيخرج
            // برّه ويتداخل مع اللي جنبه.
            out.plotOptions.bar.dataLabels = { position: 'center' };
            out.dataLabels = {
                enabled: true,
                textAnchor: 'middle',
                style: { fontSize: '11px', fontWeight: 600, colors: [textOnFill] },
                formatter: function (value, opt) {
                    if (!value) return '';

                    // النسبة من أكبر قيمة — العمود القصير مش هيسع
                    // غير رقم مختصر
                    var max = Math.max.apply(null, (opt.w.globals.series || [[]])
                        .reduce(function (all, serie) { return all.concat(serie); }, [0]));

                    var unit  = opt.w.globals.seriesNames[opt.seriesIndex] || '';
                    var ratio = max ? value / max : 0;

                    if (ratio < 0.2) {
                        return nf0.format(value);
                    }

                    return nf0.format(value) + (unit ? ' ' + unit : '');
                },
                dropShadow: { enabled: true, blur: 2, opacity: 0.45 },
            };

            // الاسم على المحور الجانبي بدل ما يكون جوه العمود
            out.yaxis = {
                labels: {
                    show: true,
                    maxWidth: 190,
                    style: { colors: [textStrong], fontSize: '11px' },
                },
            };
        } else {
            out.dataLabels = { enabled: false };
            out.yaxis = {
                opposite: isRtl,
                labels: { style: { colors: textMuted }, formatter: function (v) { return nf0.format(v); } },
            };
        }

        out.xaxis = {
            categories: labels || [],
            labels: { style: { colors: textMuted }, formatter: function (v) { return v; } },
        };
        out.legend = {
            show: true, position: 'top',
            horizontalAlign: isRtl ? 'right' : 'left',
            labels: { colors: textStrong },
        };

        return out;
    }

    // ====================================================
    //  اتجاه الإيرادات والمصروفات — كل العملات
    // ====================================================

    /** خط إيراد وخط مصروف لكل عملة. */
    function trendOptions(data) {
        var series = [];
        var colors = [];
        var dashes = [];

        (data.series || []).forEach(function (item, i) {
            var colour = PALETTE[i % PALETTE.length];

            series.push({ name: L.revenue  + ' · ' + item.currency, data: item.revenue });
            series.push({ name: L.expenses + ' · ' + item.currency, data: item.expenses });

            colors.push(colour, colour);
            dashes.push(0, 5);   // المصروف متقطّع بنفس اللون
        });

        return Object.assign({}, baseOptions, {
            chart:  Object.assign({}, baseOptions.chart, { type: 'line', height: 320 }),
            colors: colors,
            stroke: { curve: 'smooth', width: 2, dashArray: dashes },
            series: series,
            legend: {
                show: true,
                position: 'top',
                horizontalAlign: isRtl ? 'right' : 'left',
                labels: { colors: textStrong },
            },
            xaxis: {
                categories: data.labels || [],
                labels: { style: { colors: textMuted } },
                axisBorder: { show: false },
            },
            yaxis: {
                opposite: isRtl,
                labels: {
                    style: { colors: textMuted },
                    formatter: function (v) { return nf0.format(v); }
                }
            },
            tooltip: { rtl: isRtl, y: { formatter: function (v) { return nf.format(v); } } },
        });
    }

    // ====================================================
    //  بناء الرسوم
    // ====================================================
    var trendChart = new Chart(
        '#chartTrend', trendOptions, 'trend', 'line',
        function (d) { return (d && d.labels) || []; },
        {
            badge: '#trendCurrency',
            currencyOf: function (d) {
                return ((d && d.series) || []).map(function (x) { return x.currency; });
            },
        }
    );

    /** العملات الموجودة في بيانات التوزيع. */
    function currenciesOf(d) {
        return ((d && d.groups) || []).map(function (g) { return g.currency; });
    }

    var expenseChart = new Chart(
        '#chartExpenses',
        function (d) { return insideBarOptions(byCurrency(d)); },
        'expenses', 'bar',
        function (d) { return byCurrency(d).labels; },
        { badge: '#expenseCurrency', currencyOf: currenciesOf }
    );

    var channelChart = new Chart(
        '#chartChannels',
        function (d) { return insideBarOptions(byCurrency(d)); },
        'channels', 'bar',
        function (d) { return byCurrency(d).labels; },
        { badge: '#channelsCurrency', currencyOf: currenciesOf }
    );

    trendChart.render(@json($trend));
    expenseChart.render(@json($expenseSplit));
    channelChart.render(@json($salesChannels));

    // الموظفون حسب الفرع — ثابت، مش متأثر بالفلتر
    var branchData = {
        labels: @json(array_column($hr['by_branch'], 'label')),
        series: [
            { name: @json(__('dashboard.hr.active')),   data: @json(array_map('intval', array_column($hr['by_branch'], 'active'))) },
            { name: @json(__('dashboard.hr.on_leave')), data: @json(array_map('intval', array_column($hr['by_branch'], 'on_leave'))) },
        ],
    };

    function branchOptions(d) {
        return Object.assign({}, baseOptions, {
            chart:  Object.assign({}, baseOptions.chart, { type: 'bar', height: 260, stacked: true }),
            colors: ['#7367f0', '#ff9f43'],
            plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
            legend: { show: true, position: 'top', horizontalAlign: isRtl ? 'right' : 'left', labels: { colors: textStrong } },
            series: d.series,
            xaxis: {
                categories: d.labels,
                labels: { style: { colors: textMuted } },
            },
            yaxis: { opposite: isRtl, labels: { style: { colors: textMuted } } },
        });
    }

    var branchChart = new Chart(
        '#chartBranches', branchOptions, 'branches', 'column',
        function (d) { return d.labels; }
    );

    branchChart.render(branchData);

    // ====================================================
    //  الفلاتر — تحديث AJAX
    // ====================================================
    var form    = document.querySelector('#dashboardFilters');
    var spinner = document.querySelector('#dashboardSpinner');
    var preset  = document.querySelector('#filterPreset');
    var range   = document.querySelector('#customRange');

    var presetCol = document.querySelector('#presetCol');
    var branchCol = document.querySelector('#branchCol');

    function toggleCustomRange() {
        if (!preset || !range) return;

        var custom = preset.value === 'custom';

        range.classList.toggle('d-none', !custom);

        // مجموع الأعمدة يفضل 12 في الحالتين عشان الشريط
        // مايلفّش لسطر تاني
        if (presetCol) {
            presetCol.classList.toggle('col-md-3', custom);
            presetCol.classList.toggle('col-md-5', !custom);
        }

        if (branchCol) {
            branchCol.classList.toggle('col-md-4', custom);
            branchCol.classList.toggle('col-md-6', !custom);
        }
    }

    if (preset && range) {
        // select2 بيرفع الحدث عن طريق jQuery، والمستمع الأصلي مش
        // بيشوفه — فحقول الفترة المخصصة مكانتش بتظهر عند اختيارها.
        if (window.jQuery) {
            jQuery(preset).on('change', toggleCustomRange);
        } else {
            preset.addEventListener('change', toggleCustomRange);
        }

        toggleCustomRange();
    }

    function cell(label, value, cls) {
        return '<div class="col-md-2"><div class="small text-muted">' + label + '</div>'
             + '<h6 class="mb-0 fw-bold ' + cls + '" dir="ltr">' + value + '</h6></div>';
    }

    function renderFinancial(rows) {
        var box = document.querySelector('#financialSummary');
        if (!box) return;

        if (!rows.length) {
            box.innerHTML = '<div class="card border-0 shadow-sm mb-4"><div class="card-body text-center py-5">'
                          + '<h6 class="text-muted">' + L.noData + '</h6></div></div>';
            return;
        }

        box.innerHTML = rows.map(function (row) {
            var netClass = row.net >= 0 ? 'text-primary' : 'text-danger';
            var sign     = row.net >= 0 ? '+' : '';

            return '<div class="row g-4 mb-4"><div class="col-12">'
                 + '<div class="card border-0 shadow-sm"><div class="card-body">'
                 + '<div class="row g-3 align-items-center">'
                 +   '<div class="col-md-2 text-center border-end">'
                 +     '<span class="badge bg-label-primary fs-6 px-3 py-2">' + row.currency + '</span>'
                 +     '<div class="small text-muted mt-2"><span dir="ltr">' + row.invoices + '</span> ' + L.invoices + '</div>'
                 +   '</div>'
                 +   '<div class="col-md-3"><div class="small text-muted">' + L.revenue + '</div>'
                 +     '<h6 class="mb-0 fw-bold text-success" dir="ltr">' + nf.format(row.revenue) + '</h6></div>'
                 +   '<div class="col-md-3"><div class="small text-muted">' + L.expenses + '</div>'
                 +     '<h6 class="mb-0 fw-bold text-warning" dir="ltr">' + nf.format(row.expenses) + '</h6></div>'
                 +   cell(L.net, sign + nf.format(row.net), netClass)
                 +   cell(L.due, nf.format(row.due), 'text-danger')
                 + '</div></div></div></div></div>';
        }).join('');
    }

    /** الخطأ الحقيقي بيتعرض بدل رسالة عامة مابتقولش حاجة. */
    function showError(message) {
        if (window.Swal) {
            Swal.fire({ icon: 'error', title: L.error, text: message || '' });
        } else {
            alert(L.error + (message ? '\n' + message : ''));
        }
    }

    function refresh() {
        if (!form) return;

        var params = new URLSearchParams(new FormData(form)).toString();

        if (spinner) spinner.classList.remove('d-none');

        fetch(@json(route('admin.home.data')) + '?' + params, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin'
        })
        .then(function (r) {
            if (!r.ok) {
                throw new Error('HTTP ' + r.status + (r.status === 419 ? ' — انتهت الجلسة، حدّث الصفحة' : ''));
            }
            return r.json();
        })
        .then(function (data) {
            var label = document.querySelector('#periodLabel');
            if (label && data.filter) label.textContent = data.filter.label;

            renderFinancial(data.financial || []);

            trendChart.render(data.trend || { labels: [], series: [] });
            expenseChart.render(data.expenseSplit || { groups: [] });
            channelChart.render(data.salesChannels || { groups: [] });
        })
        .catch(function (err) {
            showError(err && err.message ? err.message : '');
        })
        .finally(function () {
            if (spinner) spinner.classList.add('d-none');
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) { e.preventDefault(); refresh(); });

        // select2 بيرفع الحدث عن طريق jQuery، والمستمع الأصلي مش
        // دايماً بيشوفه — فبنربط بالاتنين ونمنع التكرار.
        if (window.jQuery) {
            jQuery(form).on('change', 'select, input', refresh);
        } else {
            form.addEventListener('change', refresh);
        }
    }

    // ====================================================
    //  مبدّل شكل الرسم
    // ====================================================
    var chartsByName = {
        trend:    trendChart,
        expenses: expenseChart,
        channels: channelChart,
        branches: branchChart,
    };

    /** بيعلّم الشكل الشغّال في القائمة. */
    function markActiveType(target) {
        var chart = chartsByName[target];
        if (!chart) return;

        document.querySelectorAll('.chart-type-option[data-target="' + target + '"]')
            .forEach(function (item) {
                item.classList.toggle('active', item.dataset.type === chart.type());
            });
    }

    Object.keys(chartsByName).forEach(markActiveType);

    document.querySelectorAll('.chart-type-option').forEach(function (option) {
        option.addEventListener('click', function () {
            var target = this.dataset.target;
            var chart  = chartsByName[target];

            if (!chart) return;

            chart.setType(this.dataset.type);
            markActiveType(target);
        });
    });

    var resetBtn = document.querySelector('#resetDashboardFilters');
    if (resetBtn && form) {
        resetBtn.addEventListener('click', function () {
            form.reset();

            if (preset) {
                preset.value = 'this_month';
                // لازم نبلّغ select2 إن القيمة اتغيّرت عشان يحدّث شكله
                if (window.jQuery && jQuery(preset).hasClass('select2-hidden-accessible')) {
                    jQuery(preset).val('this_month').trigger('change.select2');
                }
            }

            var branch = form.querySelector('[name="branch_id"]');
            if (branch && window.jQuery && jQuery(branch).hasClass('select2-hidden-accessible')) {
                jQuery(branch).val('').trigger('change.select2');
            }

            toggleCustomRange();

            refresh();
        });
    }

});
</script>
@endpush
