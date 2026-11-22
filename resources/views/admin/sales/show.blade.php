@extends('layouts.admin.app')
@section('title', __('sale.details_title') . ' - ' . $sale->invoice_number)

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm invoice-preview-card">
            {{-- Action Header (Hidden during print) --}}
            <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 d-print-none">
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.sales.index') }}" class="btn btn-label-secondary waves-effect">
                        <i class="fa-solid fa-arrow-right icon-back me-1" aria-hidden="true"></i> {{ __('sale.actions.back_to_list') }}
                    </a>
                    <span class="badge bg-label-primary fs-6" dir="ltr">{{ $sale->invoice_number }}</span>
                </div>
                <div class="d-flex gap-2">
                    @if(admin_can('sale_update') && $sale->due_amount > 0)
                        <button type="button" class="btn btn-success waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                            <i class="fa-solid fa-money-bill-wave me-1"></i> {{ __('sale.payments.add_payment') }}
                        </button>
                    @endif
                    @if(admin_can('sale_update'))
                        <a href="{{ route('admin.sales.edit', $sale->id) }}" class="btn btn-label-primary waves-effect">
                            <i class="fa-solid fa-pen-to-square me-1"></i> {{ __('global.edit') }}
                        </a>
                    @endif
                    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="window.print()">
                        <i class="fa-solid fa-print me-1"></i> طباعة الفاتورة
                    </button>
                </div>
            </div>

            <div class="card-body p-4 p-md-5">
                {{-- Invoice Header / Company Info --}}
                <div class="d-flex justify-content-between flex-wrap gap-4 border-bottom pb-4 mb-4">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <img src="{{ setting('site_logo') }}" alt="Logo" class="rounded-circle" style="width: 48px; height: 48px; object-fit: contain;">
                            <h4 class="fw-bold mb-0 text-heading">{{ setting('site_name') }}</h4>
                        </div>
                        <p class="text-muted mb-1 small">{{ $sale->branch?->name }} - {{ $sale->branch?->address ?? 'المقر الرئيسي' }}</p>
                        <p class="text-muted mb-0 small">الرقم الضريبي: <span dir="ltr">{{ setting('tax_number') ?? '300000000000003' }}</span></p>
                    </div>

                    <div class="text-start">
                        <h3 class="fw-bold text-primary mb-2">فاتورة مبيعات</h3>
                        <p class="mb-1"><span class="text-muted">رقم الفاتورة:</span> <strong class="text-heading" dir="ltr">{{ $sale->invoice_number }}</strong></p>
                        <p class="mb-1"><span class="text-muted">تاريخ الإصدار:</span> <span dir="ltr">{{ $sale->sale_date?->format('Y-m-d') }}</span></p>
                        @php
                            $status = $sale->payment_status;
                            $badges = [
                                'paid'           => ['class' => 'bg-label-success', 'label' => __('sale.statuses.paid')],
                                'partially_paid' => ['class' => 'bg-label-warning', 'label' => __('sale.statuses.partially_paid')],
                                'unpaid'         => ['class' => 'bg-label-danger', 'label' => __('sale.statuses.unpaid')],
                            ];
                            $info = $badges[$status] ?? ['class' => 'bg-label-secondary', 'label' => $status];
                        @endphp
                        <p class="mb-0"><span class="text-muted">حالة السداد:</span> <span class="badge {{ $info['class'] }}">{{ $info['label'] }}</span></p>
                    </div>
                </div>

                {{-- Bill To & Channel Info --}}
                <div class="row g-4 border-bottom pb-4 mb-4">
                    <div class="col-sm-6">
                        <h6 class="fw-bold text-muted mb-2">فاتورة إلى (العميل):</h6>
                        <h5 class="fw-bold text-heading mb-1">{{ $sale->customer?->name ?? __('sale.direct_customer') }}</h5>
                        @if($sale->customer?->company_name)
                            <p class="text-muted mb-1 small">{{ $sale->customer->company_name }}</p>
                        @endif
                        @if($sale->customer?->phone)
                            <p class="text-muted mb-1 small">الهاتف: <span dir="ltr">{{ $sale->customer->phone }}</span></p>
                        @endif
                        @if($sale->customer?->email)
                            <p class="text-muted mb-0 small">البريد: <span dir="ltr">{{ $sale->customer->email }}</span></p>
                        @endif
                    </div>

                    <div class="col-sm-6 text-sm-start">
                        <h6 class="fw-bold text-muted mb-2">بيانات البيع والقناة:</h6>
                        <p class="mb-1"><span class="text-muted">نوع الإيراد:</span> <span class="badge bg-label-info">{{ __('sale.type_enums.' . $sale->sale_type) }}</span></p>
                        <p class="mb-1"><span class="text-muted">قناة البيع / المنصة:</span> <strong>{{ $sale->salesChannel?->name ?? ($sale->sales_channel ?? '-') }}</strong></p>
                        <p class="mb-0"><span class="text-muted">طريقة التحصيل:</span> <strong><span class="badge bg-label-info">{{ __('sale.payment_methods.' . $sale->payment_method) ?? $sale->payment_method }}</span></strong></p>
                    </div>
                </div>

                {{-- Invoice Items Table --}}
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('sale.fields.item_name') }}</th>
                                <th class="text-center" style="width: 120px;">{{ __('sale.fields.quantity') }}</th>
                                <th class="text-center" style="width: 160px;">{{ __('sale.fields.unit_price') }}</th>
                                <th class="text-center" style="width: 160px;">{{ __('sale.fields.total_price') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sale->items as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $item->item_name }}</strong></td>
                                    <td class="text-center"><span dir="ltr">{{ $item->quantity }}</span></td>
                                    <td class="text-center"><span dir="ltr">{{ number_format($item->unit_price, 2) }}</span> {{ $sale->currency }}</td>
                                    <td class="text-center fw-bold"><span dir="ltr">{{ number_format($item->total_price, 2) }}</span> {{ $sale->currency }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">لا توجد بنود مسجلة في الفاتورة</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Invoice Totals Summary --}}
                <div class="row justify-content-end">
                    <div class="col-md-5">
                        <div class="border rounded p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">{{ __('sale.fields.subtotal') }}:</span>
                                <span class="fw-bold" dir="ltr">{{ number_format($sale->subtotal, 2) }} {{ $sale->currency }}</span>
                            </div>
                            @if($sale->tax_amount > 0)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">{{ __('sale.fields.tax_amount') }}:</span>
                                    <span class="text-danger" dir="ltr">+{{ number_format($sale->tax_amount, 2) }} {{ $sale->currency }}</span>
                                </div>
                            @endif
                            @if($sale->discount_amount > 0)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">{{ __('sale.fields.discount_amount') }}:</span>
                                    <span class="text-success" dir="ltr">-{{ number_format($sale->discount_amount, 2) }} {{ $sale->currency }}</span>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top mb-3">
                                <span class="fw-bold fs-6 text-heading">{{ __('sale.fields.total_amount') }}:</span>
                                <h4 class="mb-0 text-primary fw-bold" dir="ltr">{{ number_format($sale->total_amount, 2) }} {{ $sale->currency }}</h4>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">{{ __('sale.fields.paid_amount') }}:</span>
                                <span class="fw-bold text-success" dir="ltr">{{ number_format($sale->paid_amount, 2) }} {{ $sale->currency }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="text-muted">{{ __('sale.fields.due_amount') }}:</span>
                                <span class="fw-bold {{ $sale->due_amount > 0 ? 'text-danger' : 'text-muted' }}" dir="ltr">
                                    {{ number_format($sale->due_amount, 2) }} {{ $sale->currency }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($sale->notes)
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="fw-bold text-muted mb-1">ملاحظات وشروط الفاتورة:</h6>
                        <p class="text-secondary small mb-0">{{ $sale->notes }}</p>
                    </div>
                @endif

                {{-- Payment Installments History Section --}}
                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-heading mb-0">
                            <i class="fa-solid fa-receipt text-primary me-2"></i> {{ __('sale.payments.title') }}
                        </h6>
                        @if(admin_can('sale_update') && $sale->due_amount > 0)
                            <button type="button" class="btn btn-sm btn-outline-success waves-effect" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                <i class="fa-solid fa-plus me-1"></i> {{ __('sale.payments.add_payment') }}
                            </button>
                        @endif
                    </div>

                    @if($sale->payments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>{{ __('sale.payments.payment_date') }}</th>
                                        <th>{{ __('sale.payments.amount') }}</th>
                                        <th>{{ __('sale.payments.payment_method') }}</th>
                                        <th>{{ __('sale.payments.reference_number') }}</th>
                                        <th>{{ __('sale.payments.created_by') }}</th>
                                        <th>{{ __('sale.payments.notes') }}</th>
                                        @if(admin_can('sale_update'))
                                            <th style="width: 80px;" class="text-center">{{ __('global.actions') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sale->payments as $payment)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td><span dir="ltr">{{ $payment->payment_date ? $payment->payment_date->format('Y-m-d') : '-' }}</span></td>
                                            <td>
                                                <span class="fw-bold text-success" dir="ltr">{{ number_format($payment->amount, 2) }}</span> {{ $sale->currency }}
                                            </td>
                                            <td>
                                                <span class="badge bg-label-info">{{ __('sale.payment_methods.' . $payment->payment_method) ?? $payment->payment_method }}</span>
                                            </td>
                                            <td>{{ $payment->reference_number ?: '-' }}</td>
                                            <td>{{ $payment->creator?->name ?? '-' }}</td>
                                            <td><span class="small text-muted">{{ $payment->notes ?: '-' }}</span></td>
                                            @if(admin_can('sale_update'))
                                                <td class="text-center">
                                                    <form action="{{ route('admin.sales.payments.destroy', [$sale->id, $payment->id]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-sm btn-icon btn-label-danger waves-effect btn-swal-confirm" 
                                                            data-title="{{ __('global.confirm_delete') }}" 
                                                            data-text="هل أنت متأكد من حذف هذه الدفعة؟ سيتم إعادة احتساب رصيد الفاتورة تلقائياً." 
                                                            data-icon="warning" 
                                                            data-btn-text="{{ __('global.delete') }}" 
                                                            data-btn-class="btn-danger" 
                                                            title="{{ __('global.delete') }}" aria-label="{{ __('global.confirm_delete') }}">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-secondary py-2 mb-0 small">
                            <i class="fa-solid fa-circle-info me-1"></i> {{ __('sale.payments.no_payments') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if(admin_can('sale_update') && $sale->due_amount > 0)
    {{-- Modal: Add Payment --}}
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.sales.payments.store', $sale->id) }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold">
                            <i class="fa-solid fa-money-bill-wave text-success me-2"></i> {{ __('sale.payments.add_payment') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="alert alert-info py-2 mb-3">
                            <div class="d-flex justify-content-between">
                                <span>{{ __('sale.payments.remaining_before') }}:</span>
                                <strong><span dir="ltr">{{ number_format($sale->due_amount, 2) }}</span> {{ $sale->currency }}</strong>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('sale.payments.payment_date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('sale.payments.amount') }} ({{ $sale->currency }}) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="{{ $sale->due_amount }}" value="{{ $sale->due_amount }}" required dir="ltr" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('sale.payments.payment_method') }} <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select select2" required>
                                    <option value="cash">{{ __('sale.payment_methods.cash') }}</option>
                                    <option value="bank_transfer" selected>{{ __('sale.payment_methods.bank_transfer') }}</option>
                                    <option value="card">{{ __('sale.payment_methods.card') }}</option>
                                    <option value="cheque">{{ __('sale.payment_methods.cheque') }}</option>
                                    <option value="pos">{{ __('sale.payment_methods.pos') }}</option>
                                    <option value="online">{{ __('sale.payment_methods.online') }}</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('sale.payments.reference_number') }}</label>
                                <input type="text" name="reference_number" class="form-control" placeholder="رقم الشيك أو الحوالة أو إيصال السداد..." />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('sale.payments.notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="ملاحظات حول الدفعة..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary waves-effect" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        <button type="submit" class="btn btn-success waves-effect waves-light">
                            <i class="fa-solid fa-check me-1"></i> تأكيد وحفظ الدفعة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
