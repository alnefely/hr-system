{{--
    المؤشرات الرئيسية كأرقام (مش رسوم) — $summary من TaskMetricsService::summary.
    كل رقم معاه شرح قصير عشان التعريف يبقى واضح.
--}}
@php
    $pct = fn ($v) => $v === null ? '—' : $v . '%';
    $tiles = [
        ['completed', 'fa-circle-check', 'success', $summary['completed'], __('task.reports.created') . ': ' . $summary['created']],
        ['open', 'fa-list-check', 'primary', $summary['open'], __('task.reports.in_review') . ': ' . $summary['in_review']],
        ['overdue', 'fa-triangle-exclamation', 'danger', $summary['overdue'], null],
        ['on_time', 'fa-stopwatch', 'info', $pct($summary['on_time_rate']), __('task.reports.on_time_hint', ['count' => $summary['on_time_base']])],
        ['avg_cycle', 'fa-hourglass-half', 'secondary', $summary['avg_cycle_minutes'] === null ? '—' : \App\Support\Duration::format($summary['avg_cycle_minutes']), __('task.reports.avg_cycle_hint')],
        ['estimate', 'fa-scale-balanced', 'warning', $pct($summary['estimate_ratio']), __('task.reports.estimate_hint')],
        ['rework', 'fa-rotate-left', 'warning', $pct($summary['rework_rate']), __('task.reports.rework_hint')],
        ['time_logged', 'fa-clock', 'info', \App\Support\Duration::format($summary['time_logged']), null],
    ];
@endphp
<div class="row g-3 mb-4">
    @foreach ($tiles as [$key, $icon, $tone, $value, $hint])
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-label-{{ $tone }} rounded p-2"><i class="fa-solid {{ $icon }}"></i></span>
                        <span class="small text-body-secondary">{{ __('task.reports.' . $key) }}</span>
                    </div>
                    <div class="h3 mb-0">{{ $value }}</div>
                    @if ($hint)<div class="small text-body-secondary mt-1">{{ $hint }}</div>@endif
                </div>
            </div>
        </div>
    @endforeach
</div>
