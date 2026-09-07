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

            <div class="col-md-4">
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

            <div class="col-md-3 {{ $filter['preset'] === 'custom' ? '' : 'd-none' }}" id="customRange">
                <label class="form-label small mb-1">
                    {{ __('dashboard.filters.range_label') }}
                    <x-inputs.help :text="__('dashboard.hints.range')" />
                </label>
                <div class="input-group input-group-sm">
                    <input type="date" name="date_from" class="form-control px-1" value="{{ $filter['date_from'] }}">
                    <input type="date" name="date_to" class="form-control px-1" value="{{ $filter['date_to'] }}">
                </div>
            </div>

            <div class="col-md-4">
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
                <button type="button" id="resetDashboardFilters" class="btn btn-sm btn-label-secondary"
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
                <span class="badge bg-label-secondary" data-bs-toggle="tooltip"
                      title="{{ __('dashboard.hints.trend_currency') }}">
                    {{ __('dashboard.all_currencies') }}
                </span>
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
                <span class="badge bg-label-warning" id="expenseCurrency">{{ __('dashboard.all_currencies') }}</span>
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
                <span class="badge bg-label-info" id="channelsCurrency">{{ __('dashboard.all_currencies') }}</span>
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
        <div class="card-header border-bottom">
            <h6 class="card-title mb-0 fw-bold">
                <i class="fa-solid fa-building text-primary me-2"></i>
                {{ __('dashboard.charts.employees_by_branch') }}
            </h6>
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
    var textMuted   = styles.getPropertyValue('--bs-secondary-color') || '#8592a3';
    var borderColor = styles.getPropertyValue('--bs-border-color') || '#e6e6e8';

    var isRtl   = document.documentElement.getAttribute('dir') === 'rtl';
    var PALETTE = ['#7367f0', '#28c76f', '#ff9f43', '#ea5455', '#00cfe8', '#a8aaae'];

    var baseOptions = {
        chart: { toolbar: { show: false }, fontFamily: 'inherit' },
        grid:  { borderColor: borderColor, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        legend: { labels: { colors: textMuted } },
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
    function Chart(selector, build) {
        this.el     = document.querySelector(selector);
        this.build  = build;
        this.api    = null;
        this.length = -1;
    }

    Chart.prototype.render = function (data) {
        if (!this.el) return;

        var options = this.build(data);
        var count   = (options.series || []).length;

        // مفيش بيانات — نفضي المكان بدل ما نرسم رسم فاضي
        if (!count) {
            this.destroy();
            this.el.innerHTML = '<p class="text-muted text-center small py-5 mb-0">' + L.noData + '</p>';
            this.length = 0;
            return;
        }

        if (this.api && this.length === count) {
            // نفس عدد السلاسل — تحديث آمن بنداء واحد
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
        this.length = count;
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
                labels: { colors: textMuted },
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
                labels: { colors: textMuted },
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
    var trendChart    = new Chart('#chartTrend',    trendOptions);
    var expenseChart  = new Chart('#chartExpenses', function (d) { return insideBarOptions(byCurrency(d)); });
    var channelChart  = new Chart('#chartChannels', function (d) { return insideBarOptions(byCurrency(d)); });

    trendChart.render(@json($trend));
    expenseChart.render(@json($expenseSplit));
    channelChart.render(@json($salesChannels));

    // الموظفون حسب الفرع — ثابت، مش متأثر بالفلتر
    var branchesEl = document.querySelector('#chartBranches');
    if (branchesEl) {
        new ApexCharts(branchesEl, Object.assign({}, baseOptions, {
            chart:  Object.assign({}, baseOptions.chart, { type: 'bar', height: 260, stacked: true }),
            colors: ['#7367f0', '#ff9f43'],
            plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
            legend: { show: true, position: 'top', horizontalAlign: isRtl ? 'right' : 'left', labels: { colors: textMuted } },
            series: [
                { name: @json(__('dashboard.hr.active')),   data: @json(array_map('intval', array_column($hr['by_branch'], 'active'))) },
                { name: @json(__('dashboard.hr.on_leave')), data: @json(array_map('intval', array_column($hr['by_branch'], 'on_leave'))) },
            ],
            xaxis: {
                categories: @json(array_column($hr['by_branch'], 'label')),
                labels: { style: { colors: textMuted } },
            },
            yaxis: { opposite: isRtl, labels: { style: { colors: textMuted } } },
        })).render();
    }

    // ====================================================
    //  الفلاتر — تحديث AJAX
    // ====================================================
    var form    = document.querySelector('#dashboardFilters');
    var spinner = document.querySelector('#dashboardSpinner');
    var preset  = document.querySelector('#filterPreset');
    var range   = document.querySelector('#customRange');

    if (preset && range) {
        preset.addEventListener('change', function () {
            range.classList.toggle('d-none', this.value !== 'custom');
        });
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

            if (range) range.classList.add('d-none');

            refresh();
        });
    }

});
</script>
@endpush
