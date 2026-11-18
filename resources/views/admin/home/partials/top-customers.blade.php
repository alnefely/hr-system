{{--
    أعلى العملاء — بيترندر عند فتح الصفحة وعند تغيير الفلتر
    (topCustomersHtml في home.data). قبل كده كانت القائمة مابتتحدّثش مع الفلتر.
--}}
@forelse ($topCustomers as $i => $customer)
    <div class="d-flex align-items-center gap-3 py-2 {{ $loop->last ? '' : 'border-bottom' }}">
        <span class="dash-rank">{{ $i + 1 }}</span>
        <div class="flex-grow-1 min-w-0">
            <div class="fw-semibold text-truncate">{{ $customer['label'] }}</div>
            <small class="text-muted"><span dir="ltr">{{ $customer['invoices'] }}</span> {{ __('dashboard.invoice_count') }}</small>
        </div>
        <span class="fw-bold text-nowrap" dir="ltr">{{ number_format($customer['total'], 2) }}</span>
    </div>
@empty
    <p class="text-muted text-center small py-5 mb-0">{{ __('dashboard.no_data') }}</p>
@endforelse
