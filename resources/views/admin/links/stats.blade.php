@extends('layouts.admin.app')
@section('title', __('link.stats_screen.title'))

@section('content')

{{-- ترويسة الرابط --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h5 class="fw-bold mb-1">
                <i class="fa-solid fa-chart-line text-primary me-2"></i>
                {{ $link->title ?: $link->short_code }}
            </h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <code dir="ltr" class="text-primary">{{ $link->short_url }}</code>
                <a href="{{ $link->original_url }}" target="_blank" rel="noopener noreferrer"
                   class="text-muted small text-truncate" dir="ltr" style="max-width: 320px;">
                    <i class="fa-solid fa-arrow-right-long mx-1"></i>{{ $link->original_url }}
                </a>
            </div>
        </div>

        <a href="{{ route('admin.links.index') }}" class="btn btn-label-secondary waves-effect">
            <i class="fa-solid fa-arrow-right me-1"></i> {{ __('link.back') }}
        </a>
    </div>
</div>

{{-- بطاقات الملخّص --}}
<div class="row g-4 mb-4">
    @php
        $cards = [
            ['label' => __('link.stats_screen.period_clicks'), 'value' => $summary['period_clicks'], 'colour' => 'primary', 'icon' => 'fa-arrow-pointer', 'hint' => null],
            ['label' => __('link.stats_screen.period_unique'), 'value' => $summary['period_unique'], 'colour' => 'success', 'icon' => 'fa-user-check', 'hint' => null],
            ['label' => __('link.stats_screen.daily_average'), 'value' => $summary['daily_average'], 'colour' => 'info',    'icon' => 'fa-chart-simple', 'hint' => null],
            ['label' => __('link.stats_screen.bot_clicks'),    'value' => $summary['bot_clicks'],    'colour' => 'secondary','icon' => 'fa-robot', 'hint' => __('link.stats_screen.bot_hint')],
        ];
    @endphp

    @foreach ($cards as $card)
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-start border-{{ $card['colour'] }} border-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="avatar avatar-md rounded bg-label-{{ $card['colour'] }} d-flex align-items-center justify-content-center">
                            <i class="fa-solid {{ $card['icon'] }} fs-4"></i>
                        </span>
                        <h3 class="mb-0 fw-bold text-{{ $card['colour'] }}" dir="ltr">
                            {{ is_float($card['value']) ? number_format($card['value'], 1) : number_format($card['value']) }}
                        </h3>
                    </div>
                    <p class="mb-0 text-muted small">
                        {{ $card['label'] }}
                        @if ($card['hint'])
                            <x-inputs.help :text="$card['hint']" />
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- الفلتر --}}
<div class="card border-0 shadow-sm mb-4 d-print-none">
    <div class="card-body">
        <form action="{{ route('admin.links.stats', $link->id) }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label text-muted small mb-1">{{ __('link.stats_screen.period') }}</label>
                <div class="input-group">
                    <input type="date" name="date_from" class="form-control px-1" value="{{ $from }}" />
                    <input type="date" name="date_to" class="form-control px-1" value="{{ $to }}" />
                </div>
            </div>

            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="fa-solid fa-filter me-1"></i> {{ __('global.search') }}
                </button>
                <a href="{{ route('admin.links.stats', $link->id) }}" class="btn btn-outline-secondary"
                   data-bs-toggle="tooltip" title="{{ __('global.reset_filter') }}">
                    <i class="fa-solid fa-rotate"></i>
                </a>
            </div>

            <div class="col-md-4 text-md-end">
                <span class="badge bg-label-primary">
                    {{ __('link.stats_screen.total_clicks') }}:
                    <span dir="ltr">{{ number_format($summary['total_clicks']) }}</span>
                </span>
                @if ($summary['best_day'])
                    <span class="badge bg-label-success">
                        {{ __('link.stats_screen.best_day') }}:
                        <span dir="ltr">{{ $summary['best_day'] }}</span>
                    </span>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- الشارت الزمني --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header border-bottom">
        <h6 class="fw-bold mb-0">
            <i class="fa-solid fa-chart-area text-primary me-2"></i>
            {{ __('link.stats_screen.timeline') }}
        </h6>
    </div>
    <div class="card-body">
        @if (array_sum($timeline['clicks']) === 0)
            <div class="text-center py-5">
                <i class="fa-solid fa-chart-line text-muted mb-2" style="font-size: 2rem; opacity: .3;"></i>
                <p class="text-muted mb-0">{{ __('link.stats_screen.no_data') }}</p>
                <small class="text-muted">{{ __('link.stats_screen.pending_hint') }}</small>
            </div>
        @else
            <div id="linkTimelineChart"></div>
        @endif
    </div>
</div>

{{-- التوزيعات --}}
<div class="row g-4">
    @php
        $breakdowns = [
            ['title' => __('link.stats_screen.countries'), 'icon' => 'fa-earth-africa', 'data' => $countries],
            ['title' => __('link.stats_screen.devices'),   'icon' => 'fa-mobile-screen', 'data' => $devices],
            ['title' => __('link.stats_screen.browsers'),  'icon' => 'fa-window-maximize', 'data' => $browsers],
            ['title' => __('link.stats_screen.referrers'), 'icon' => 'fa-share-nodes', 'data' => $referrers],
        ];
    @endphp

    @foreach ($breakdowns as $box)
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header border-bottom">
                    <h6 class="fw-bold mb-0">
                        <i class="fa-solid {{ $box['icon'] }} text-primary me-2"></i> {{ $box['title'] }}
                    </h6>
                </div>
                <div class="card-body">
                    @if (empty($box['data']))
                        <p class="text-muted small mb-0 text-center py-3">{{ __('link.stats_screen.no_data') }}</p>
                    @else
                        @php $max = max($box['data']); @endphp
                        @foreach ($box['data'] as $label => $count)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-semibold text-truncate" style="max-width: 70%;" dir="auto">
                                        {{ $label }}
                                    </span>
                                    <span class="small text-muted" dir="ltr">{{ number_format($count) }}</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" role="progressbar"
                                         style="width: {{ $max > 0 ? round($count / $max * 100) : 0 }}%"
                                         aria-valuenow="{{ $count }}" aria-valuemin="0" aria-valuemax="{{ $max }}"></div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

@endsection

@push('js')
@if (array_sum($timeline['clicks']) > 0)
<script>
document.addEventListener('DOMContentLoaded', function () {
    // نفس منهج ألوان لوحة التحكم: المتغيّرات من الثيم عشان
    // الشارت يتماشى مع الدارك والـ لايت.
    var styles = getComputedStyle(document.documentElement);
    var isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';

    var textMuted = (styles.getPropertyValue('--bs-secondary-color') || '').trim()
        || (isDark ? '#a1a5b7' : '#8592a3');

    var textStrong = (styles.getPropertyValue('--bs-body-color') || '').trim()
        || (isDark ? '#e7e6f1' : '#5d596c');

    var borderColor = (styles.getPropertyValue('--bs-border-color') || '').trim()
        || (isDark ? '#44485e' : '#e6e6e8');

    var isRtl = document.documentElement.getAttribute('dir') === 'rtl';
    var nf0 = new Intl.NumberFormat('en');

    var options = {
        chart: {
            type: 'area',
            height: 320,
            toolbar: { show: false },
            fontFamily: 'inherit',
            parentHeightOffset: 0,
        },
        colors: ['#7367f0', '#28c76f'],
        series: [
            { name: @json(__('link.stats_screen.clicks_label')), data: @json($timeline['clicks']) },
            { name: @json(__('link.stats_screen.unique_label')), data: @json($timeline['unique']) },
        ],
        xaxis: {
            categories: @json($timeline['labels']),
            labels: { style: { colors: textMuted, fontSize: '11px' }, rotate: -45, rotateAlways: false },
            axisBorder: { show: false },
            tickAmount: 10,
        },
        yaxis: {
            labels: {
                style: { colors: textMuted },
                formatter: function (v) { return nf0.format(Math.round(v)); },
            },
        },
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
        dataLabels: { enabled: false },
        grid: { borderColor: borderColor, strokeDashArray: 4 },
        legend: { position: 'top', horizontalAlign: 'left', labels: { colors: textStrong } },
        tooltip: { rtl: isRtl, theme: isDark ? 'dark' : 'light' },
    };

    new ApexCharts(document.querySelector('#linkTimelineChart'), options).render();
});
</script>
@endif
@endpush
