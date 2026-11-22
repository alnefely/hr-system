{{--
    المكتمل أسبوعياً — سلسلة واحدة بلون النظام الأساسي (من غير legend)،
    tooltip عند المرور، وجدول بديل للقراءة بدون رسم. $trend, $chartId
--}}
<div class="card h-100">
    <div class="card-header">
        <h6 class="mb-0 fw-bold">{{ __('task.reports.trend') }}</h6>
        <small class="text-body-secondary">{{ __('task.reports.trend_hint') }}</small>
    </div>
    <div class="card-body">
        <div id="{{ $chartId }}" style="min-height: 240px" role="img" aria-label="{{ __('task.reports.trend') }}"></div>
        <details class="mt-2 small">
            <summary class="text-body-secondary" style="cursor: pointer">{{ __('task.reports.table_view') }}</summary>
            <table class="table table-sm mt-2 mb-0">
                <tbody>
                    @foreach ($trend['labels'] as $i => $label)
                        <tr><td dir="ltr">{{ $label }}</td><td class="text-end">{{ $trend['values'][$i] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </details>
    </div>
</div>
@push('js')
<script src="/admin/vendor/libs/apex-charts/apexcharts.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') return;
    const css = getComputedStyle(document.documentElement);
    const v = (name, fallback) => (css.getPropertyValue(name) || '').trim() || fallback;
    const isRtl = document.documentElement.getAttribute('dir') === 'rtl';
    const labels = @json($trend['labels']);
    const weekOf = @json(__('task.reports.week_of', ['date' => '__D__']));
    const unit = @json(__('task.reports.tasks_unit'));

    new ApexCharts(document.getElementById(@json($chartId)), {
        chart: { type: 'bar', height: 240, toolbar: { show: false }, fontFamily: 'inherit', parentHeightOffset: 0 },
        series: [{ name: @json(__('task.reports.completed')), data: @json($trend['values']) }],
        colors: [v('--bs-primary', '#7367f0')],
        plotOptions: { bar: { columnWidth: '45%', borderRadius: 4, borderRadiusApplication: 'end' } },
        dataLabels: { enabled: false },
        legend: { show: false },
        grid: { borderColor: v('--bs-border-color', '#e6e6e8'), strokeDashArray: 3, padding: { left: 8, right: 8 } },
        xaxis: {
            categories: labels,
            labels: { style: { colors: v('--bs-secondary-color', '#8592a3'), fontSize: '11px' } },
            axisBorder: { show: false }, axisTicks: { show: false },
        },
        yaxis: { opposite: isRtl, forceNiceScale: true, labels: { style: { colors: v('--bs-secondary-color', '#8592a3') }, formatter: x => Math.round(x) } },
        tooltip: {
            theme: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light',
            x: { formatter: (val, o) => weekOf.replace('__D__', labels[o.dataPointIndex]) },
            y: { formatter: x => x + ' ' + unit },
        },
        states: { hover: { filter: { type: 'darken', value: 0.85 } } },
    }).render();
});
</script>
@endpush
