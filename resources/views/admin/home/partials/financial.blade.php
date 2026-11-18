{{--
    الملخص المالي لكل عملة — بيترندر هنا عند فتح الصفحة، وبيترندر نفسه من
    السيرفر عند تغيير الفلتر (financialHtml في home.data). مصدر واحد للشكل
    بدل نسخة HTML مكتوبة يدوي في الـ JS.
--}}
@forelse ($financial as $row)
    <div class="dash-currency-row {{ $loop->last ? '' : 'mb-4' }}">
        @if (count($financial) > 1)
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-label-primary">{{ $row['currency'] }}</span>
                <small class="text-muted"><span dir="ltr">{{ $row['invoices'] }}</span> {{ __('dashboard.invoice_count') }}</small>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-sm-6 col-xl-3">
                @include('admin.home.partials.stat', [
                    'label' => __('dashboard.revenue'), 'hint' => __('dashboard.hints.revenue'),
                    'value' => number_format($row['revenue'], 2), 'suffix' => $row['currency'],
                    'icon' => 'fa-arrow-trend-up', 'color' => 'success',
                ])
            </div>
            <div class="col-sm-6 col-xl-3">
                @include('admin.home.partials.stat', [
                    'label' => __('dashboard.expenses'), 'hint' => __('dashboard.hints.expenses'),
                    'value' => number_format($row['expenses'], 2), 'suffix' => $row['currency'],
                    'icon' => 'fa-arrow-trend-down', 'color' => 'danger',
                ])
            </div>
            <div class="col-sm-6 col-xl-3">
                @include('admin.home.partials.stat', [
                    'label' => __('dashboard.net'), 'hint' => __('dashboard.hints.net'),
                    'value' => ($row['net'] >= 0 ? '+' : '') . number_format($row['net'], 2), 'suffix' => $row['currency'],
                    'icon' => 'fa-scale-balanced', 'color' => $row['net'] >= 0 ? 'primary' : 'danger',
                    'valueClass' => $row['net'] >= 0 ? '' : 'text-danger',
                ])
            </div>
            <div class="col-sm-6 col-xl-3">
                @include('admin.home.partials.stat', [
                    'label' => __('dashboard.outstanding'), 'hint' => __('dashboard.hints.outstanding'),
                    'value' => number_format($row['due'], 2), 'suffix' => $row['currency'],
                    'icon' => 'fa-hourglass-half', 'color' => 'warning',
                ])
            </div>
        </div>
    </div>
@empty
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fa-solid fa-chart-simple text-muted mb-3" style="font-size: 2.5rem; opacity: .25;"></i>
            <h6 class="text-muted">{{ __('dashboard.no_financial_data') }}</h6>
            <p class="small text-muted mb-0">{{ __('dashboard.no_financial_hint') }}</p>
        </div>
    </div>
@endforelse
