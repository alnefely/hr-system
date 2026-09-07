@extends('layouts.admin.app')
@section('title', __('report.financial_pl'))

@section('content')
<div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2 d-print-none">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-chart-line text-primary me-2"></i> {{ __('report.financial_pl') }}
        </h5>
        <button type="button" onclick="window.print()" class="btn btn-primary waves-effect waves-light">
            <i class="fa-solid fa-print me-1"></i> {{ __('report.filters.print') }}
        </button>
    </div>

    {{-- Filter Bar (No bg-light) --}}
    <div class="card-body py-3 border-bottom bg-transparent d-print-none">
        <form action="{{ route('admin.reports.financial-pl') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label text-muted small mb-1">{{ __('report.filters.date_from') }}</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control" required />
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small mb-1">{{ __('report.filters.date_to') }}</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control" required />
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted small mb-1">{{ __('report.filters.branch') }}</label>
                <select name="branch_id" class="form-select select2">
                    <option value="">جميع الفروع والمقرات</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->currency }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 waves-effect waves-light">
                    <i class="fa-solid fa-filter me-1"></i> عرض التقرير
                </button>
            </div>
        </form>
    </div>

    <div class="card-body pt-4">
        {{-- Printable Header (Shows ONLY when printing) --}}
        <div class="d-none d-print-block mb-4 border-bottom pb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold text-primary mb-1">{{ setting('site_name') }}</h3>
                    <h5 class="fw-bold text-dark mb-0">{{ __('report.financial_pl') }}</h5>
                </div>
                <div class="text-end small text-muted">
                    <div><strong>الفترة:</strong> من <span dir="ltr">{{ $dateFrom }}</span> إلى <span dir="ltr">{{ $dateTo }}</span></div>
                    <div><strong>الفرع:</strong> {{ $branchId ? ($branches->firstWhere('id', $branchId)?->name ?? 'محدد') : 'جميع الفروع' }}</div>
                    <div><strong>تاريخ الاستخراج:</strong> <span dir="ltr">{{ now()->format('Y-m-d H:i') }}</span></div>
                </div>
            </div>
        </div>

        {{-- Multi-Currency Net Profit / Loss Highlight Cards --}}
        <div class="row g-4 mb-4">
            @foreach($currencyPnL as $curr => $pnl)
                @php
                    $isProfit = $pnl['net_profit_loss'] >= 0;
                    $borderClass = $isProfit ? 'border-success' : 'border-danger';
                    $bgBadgeClass = $isProfit ? 'bg-label-success text-success' : 'bg-label-danger text-danger';
                    $textClass = $isProfit ? 'text-success' : 'text-danger';
                    $icon = $isProfit ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';
                @endphp
                <div class="col-md-{{ count($currencyPnL) == 1 ? '12' : (count($currencyPnL) == 2 ? '6' : '4') }}">
                    <div class="card border border-2 {{ $borderClass }} shadow-none h-100">
                        <div class="card-body py-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge {{ $bgBadgeClass }} fs-6 px-3 py-2">
                                    عملة: <strong>{{ $curr }}</strong>
                                </span>
                                <span class="badge {{ $isProfit ? 'bg-success' : 'bg-danger' }}" data-bs-toggle="tooltip" title="معدل هامش الربح الصافي المحقق مقارنة بإجمالي الإيرادات">
                                    <i class="fa-solid {{ $icon }} me-1"></i>
                                    {{ $isProfit ? __('report.pl.status_profit') : __('report.pl.status_loss') }}
                                    (<span dir="ltr">{{ number_format($pnl['profit_margin'], 1) }}%</span>)
                                </span>
                            </div>

                            <h6 class="text-muted fw-semibold mb-1" data-bs-toggle="tooltip" title="صافي النتيجة المالية = إجمالي المبيعات - إجمالي التكاليف والمصروفات">
                                {{ $isProfit ? __('report.pl.net_profit') : __('report.pl.net_loss') }}
                            </h6>
                            <h2 class="fw-bold mb-3 {{ $textClass }}">
                                <span dir="ltr">{{ number_format($pnl['net_profit_loss'], 2) }}</span> <small class="fs-6">{{ $curr }}</small>
                            </h2>

                            <div class="pt-3 border-top small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted"><i class="fa-solid fa-arrow-down text-success me-1"></i> إجمالي الإيرادات:</span>
                                    <strong class="text-success"><span dir="ltr">{{ number_format($pnl['revenue'], 2) }}</span> {{ $curr }}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted"><i class="fa-solid fa-arrow-up text-danger me-1"></i> إجمالي التكاليف:</span>
                                    <strong class="text-danger"><span dir="ltr">{{ number_format($pnl['total_costs'], 2) }}</span> {{ $curr }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Multi-Currency Comprehensive Table --}}
        <div class="card border shadow-none mb-4">
            <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center bg-transparent">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="fa-solid fa-table-columns text-primary me-2"></i> جدول الأرباح والخسائر الشامل مفصل بالعملات
                </h5>
                <span class="badge bg-label-info" data-bs-toggle="tooltip" title="يتم احتساب كل عملة بشكل مستقل لتفادي الخلط بين أسعار الصرف">
                    حسابات مستقلة لكل عملة
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 text-center">
                    <thead>
                        <tr>
                            <th class="text-start">العملة</th>
                            <th data-bs-toggle="tooltip" title="إجمالي مبيعات الفواتير الصادرة خلال الفترة">إجمالي المبيعات (Revenue)</th>
                            <th data-bs-toggle="tooltip" title="صافي الرواتب والأجور المدفوعة للموظفين">الرواتب والأجور (Payroll)</th>
                            <th data-bs-toggle="tooltip" title="مصاريف العهدة النقدية والمصاريف الإدارية">العهدة والمصروفات (Expenses)</th>
                            <th data-bs-toggle="tooltip" title="مدفوعات اشتراكات الموردين والخدمات الخارجية">اشتراكات الموردين (Vendors)</th>
                            <th data-bs-toggle="tooltip" title="مجموع (الرواتب + العهدة + الاشتراكات)">إجمالي التكاليف (Total Costs)</th>
                            <th data-bs-toggle="tooltip" title="معادلة الحساب: المبيعات - إجمالي التكاليف">صافي الربح / الخسارة (Net P&L)</th>
                            <th data-bs-toggle="tooltip" title="معادلة الحساب: (صافي الربح ÷ المبيعات) × 100">هامش الربح</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($currencyPnL as $curr => $pnl)
                            @php
                                $isProfit = $pnl['net_profit_loss'] >= 0;
                            @endphp
                            <tr>
                                <td class="text-start fw-bold fs-6">
                                    <span class="badge bg-label-primary px-3 py-1">{{ $curr }}</span>
                                </td>
                                <td class="text-success fw-bold">
                                    <span dir="ltr">{{ number_format($pnl['revenue'], 2) }}</span> {{ $curr }}
                                </td>
                                <td class="text-muted">
                                    <span dir="ltr">{{ number_format($pnl['payroll'], 2) }}</span> {{ $curr }}
                                </td>
                                <td class="text-muted">
                                    <span dir="ltr">{{ number_format($pnl['expenses'], 2) }}</span> {{ $curr }}
                                </td>
                                <td class="text-muted">
                                    <span dir="ltr">{{ number_format($pnl['vendor_payments'], 2) }}</span> {{ $curr }}
                                </td>
                                <td class="text-danger fw-bold">
                                    <span dir="ltr">{{ number_format($pnl['total_costs'], 2) }}</span> {{ $curr }}
                                </td>
                                <td class="fw-bold {{ $isProfit ? 'text-success' : 'text-danger' }}">
                                    <span dir="ltr">{{ number_format($pnl['net_profit_loss'], 2) }}</span> {{ $curr }}
                                </td>
                                <td>
                                    <span class="badge {{ $isProfit ? 'bg-label-success' : 'bg-label-danger' }}">
                                        <span dir="ltr">{{ number_format($pnl['profit_margin'], 1) }}%</span>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Detailed Recent Audits (Sales & Expenses in Period) --}}
        <div class="row g-4 d-print-none">
            {{-- Recent Sales in Period --}}
            <div class="col-md-6">
                <div class="card border shadow-none h-100">
                    <div class="card-header border-bottom py-2 bg-transparent d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-success">
                            <i class="fa-solid fa-cart-shopping me-1"></i> أحدث فواتير المبيعات بالفترة ({{ count($recentSales) }})
                        </h6>
                        <a href="{{ route('admin.sales.index', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="small text-primary">عرض الكل</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>رقم الفاتورة</th>
                                    <th>العميل</th>
                                    <th>المبلغ</th>
                                    <th>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSales as $s)
                                    <tr>
                                        <td><a href="{{ route('admin.sales.show', $s->id) }}" class="fw-bold text-primary" dir="ltr">{{ $s->invoice_number }}</a></td>
                                        <td>{{ $s->customer?->name ?? 'عميل مباشر' }}</td>
                                        <td><strong class="text-success" dir="ltr">{{ number_format($s->total_amount, 2) }}</strong> {{ $s->currency }}</td>
                                        <td><span dir="ltr" class="text-muted small">{{ $s->sale_date?->format('Y-m-d') }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">لا توجد مبيعات في هذه الفترة</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Recent Expenses in Period --}}
            <div class="col-md-6">
                <div class="card border shadow-none h-100">
                    <div class="card-header border-bottom py-2 bg-transparent d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-danger">
                            <i class="fa-solid fa-receipt me-1"></i> أحدث المصروفات التشغيلية بالفترة ({{ count($recentExpenses) }})
                        </h6>
                        <a href="{{ route('admin.expenses.index', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="small text-primary">عرض الكل</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>البند</th>
                                    <th>التصنيف</th>
                                    <th>المبلغ</th>
                                    <th>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentExpenses as $e)
                                    <tr>
                                        <td>{{ $e->title }}</td>
                                        <td><span class="badge bg-label-secondary">{{ $e->category?->name ?? '-' }}</span></td>
                                        <td><strong class="text-danger" dir="ltr">{{ number_format($e->amount, 2) }}</strong> {{ $e->currency ?? $e->branch?->currency ?? 'EGP' }}</td>
                                        <td><span dir="ltr" class="text-muted small">{{ $e->expense_date?->format('Y-m-d') }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">لا توجد مصروفات في هذه الفترة</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
