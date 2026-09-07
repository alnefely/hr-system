@extends('layouts.admin.app')
@section('title', __('sale.title'))

@section('content')
{{-- Analytics Widgets (No bg-light) --}}
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card h-100 border-start border-primary border-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="avatar avatar-md rounded bg-label-primary d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-file-invoice-dollar fs-4"></i>
                    </div>
                    <span class="badge bg-label-primary" data-bs-toggle="tooltip"
                        title="{{ __('sale.stats.total_invoices_hint', ['period' => $statsPeriodLabel]) }}">
                        <span dir="ltr">{{ $totalInvoicesCount }}</span> {{ __('sale.stats.invoice_unit') }}
                    </span>
                </div>
                <h5 class="mb-1 text-primary fw-bold">{{ __('sale.stats.total_revenue') }}</h5>
                <p class="mb-2 text-muted small fw-semibold" data-bs-toggle="tooltip" title="{{ __('sale.stats.total_revenue_hint') }}">
                    {{ __('sale.stats.revenue_by_currency', ['period' => $statsPeriodLabel]) }}
                </p>
                <div class="d-flex flex-wrap gap-2 pt-1">
            @if(admin_can('sale_trash'))
                <a href="{{ route('admin.sales.trash') }}" class="btn btn-label-danger waves-effect">
                    <i class="fa-solid fa-trash me-1"></i> {{ __('sale.trash') }}
                    @if(isset($trashCount) && $trashCount > 0)
                        <span class="badge bg-danger ms-1">{{ $trashCount }}</span>
                    @endif
                </a>
            @endif

                    @forelse($revenuesByCurrency as $curr => $totalRev)
                        <span class="badge bg-label-secondary" data-bs-toggle="tooltip" title="إجمالي المبيعات المحصلة بعملة {{ $curr }}">
                            {{ $curr }}: <strong class="text-dark" dir="ltr">{{ number_format($totalRev, 2) }}</strong>
                        </span>
                    @empty
                        <span class="text-muted small">{{ __('sale.stats.no_sales_in_period', ['period' => $statsPeriodLabel]) }}</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-4">
        <div class="card h-100 border-start border-success border-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="avatar avatar-md rounded bg-label-success d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-circle-check fs-4"></i>
                    </div>
                    <span class="badge bg-label-success" data-bs-toggle="tooltip" title="الفواتير المحصلة بالكامل بدون أي مستحقات متبقية">مسددة</span>
                </div>
                <h4 class="mb-1 text-success fw-bold"><span dir="ltr">{{ $paidSalesCount }}</span></h4>
                <p class="mb-0 text-muted small fw-semibold" data-bs-toggle="tooltip" title="فواتير مبيعات تم تحصيل قيمتها بالكامل">
                    {{ __('sale.stats.paid_sales') }}
                </p>
                <p class="mb-0 text-muted" style="font-size: .72rem;">{{ $statsPeriodLabel }}</p>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-4">
        <div class="card h-100 border-start border-danger border-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="avatar avatar-md rounded bg-label-danger d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-hourglass-half fs-4"></i>
                    </div>
                    <span class="badge bg-label-danger" data-bs-toggle="tooltip" title="فواتير غير مسددة أو مسددة جزئياً تتطلب تحصيل">مستحقة</span>
                </div>
                <h4 class="mb-1 text-danger fw-bold"><span dir="ltr">{{ $unpaidSalesCount }}</span></h4>
                <p class="mb-0 text-muted small fw-semibold" data-bs-toggle="tooltip" title="فواتير غير مدفوعة أو عليها مبالغ آجلة قيد التحصيل">
                    {{ __('sale.stats.unpaid_sales') }}
                </p>
                <p class="mb-0 text-muted" style="font-size: .72rem;">{{ $statsPeriodLabel }}</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-cart-shopping text-primary me-2"></i> {{ __('sale.title') }}
        </h5>
        @if(admin_can('sale_create'))
            <a href="{{ route('admin.sales.create') }}" class="btn btn-primary waves-effect waves-light">
                <i class="fa-solid fa-plus me-1"></i> {{ __('sale.actions.add_new') }}
            </a>
        @endif
    </div>

    {{-- Filter Bar (No bg-light) --}}
    <div class="card-body py-3 border-bottom bg-transparent">
        <form action="{{ route('admin.sales.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label text-muted small mb-1">{{ __('sale.fields.branch') }}</label>
                <select name="branch_id" class="form-select select2">
                    <option value="">{{ __('global.all') }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label text-muted small mb-1">{{ __('sale.fields.customer') }}</label>
                <select name="customer_id" class="form-select select2">
                    <option value="">{{ __('global.all') }}</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label text-muted small mb-1">{{ __('sale.fields.sales_channel') }}</label>
                <select name="sales_channel_id" class="form-select select2">
                    <option value="">{{ __('global.all') }}</option>
                    @foreach($salesChannels as $ch)
                        <option value="{{ $ch->id }}" {{ request('sales_channel_id') == $ch->id ? 'selected' : '' }}>{{ $ch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label text-muted small mb-1">{{ __('sale.fields.payment_status') }}</label>
                <select name="payment_status" class="form-select select2">
                    <option value="">{{ __('global.all') }}</option>
                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>{{ __('sale.statuses.paid') }}</option>
                    <option value="partially_paid" {{ request('payment_status') == 'partially_paid' ? 'selected' : '' }}>{{ __('sale.statuses.partially_paid') }}</option>
                    <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>{{ __('sale.statuses.unpaid') }}</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label text-muted small mb-1">نطاق التواريخ</label>
                <div class="input-group">
                    <input type="date" name="date_from" class="form-control px-1" value="{{ request('date_from') }}" />
                    <input type="date" name="date_to" class="form-control px-1" value="{{ request('date_to') }}" />
                </div>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 waves-effect waves-light">
                    <i class="fa-solid fa-filter me-1"></i> {{ __('global.filter') }}
                </button>
                <a href="{{ route('admin.sales.index') }}" class="btn btn-label-secondary waves-effect" data-bs-toggle="tooltip" title="{{ __('global.reset_filter') }}">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="card-body pt-4">
        @php
            $columns = [
                __('global.id'),
                __('sale.fields.invoice_number'),
                __('sale.fields.customer'),
                __('sale.fields.branch'),
                __('sale.fields.sales_channel'),
                __('sale.fields.total_amount'),
                __('sale.fields.payment_status'),
                __('sale.fields.sale_date'),
                __('global.actions'),
            ];

            $fields = [
                'id',
                ['data' => 'invoice_link', 'name' => 'invoice_number'],
                ['data' => 'customer_name', 'name' => 'customer.name'],
                ['data' => 'branch_name', 'name' => 'branch.name'],
                ['data' => 'channel_name', 'name' => 'salesChannel.name'],
                ['data' => 'formatted_total', 'name' => 'total_amount'],
                ['data' => 'payment_badge', 'name' => 'payment_status', 'searchType' => 'select', 'searchOptions' => __('sale.statuses')],
                ['data' => 'formatted_date', 'name' => 'sale_date', 'searchType' => 'date'],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ];
        @endphp

        <x-dataTable.table
            :columns="$columns"
            :fields="$fields"
            :url="route('admin.sales.index', request()->query())"
        />
    </div>
</div>
@endsection
