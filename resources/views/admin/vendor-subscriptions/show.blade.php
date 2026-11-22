@extends('layouts.admin.app')
@section('title', __('vendor_subscription.details_title'))

@section('content')
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-file-contract text-primary me-2"></i> {{ __('vendor_subscription.details_title') }}
                </h5>
                @php
                    $status = $vendorSubscription->computed_status;
                    $statusBadges = [
                        'active'    => 'bg-label-success',
                        'pending'   => 'bg-label-warning',
                        'expired'   => 'bg-label-danger',
                        'cancelled' => 'bg-label-secondary',
                    ];
                @endphp
                <span class="badge {{ $statusBadges[$status] ?? 'bg-label-secondary' }}">
                    {{ __('vendor_subscription.statuses.' . $status) }}
                </span>
            </div>
            <div class="card-body pt-4">
                <h4 class="fw-bold text-heading mb-1">{{ $vendorSubscription->title }}</h4>
                <p class="text-muted small mb-4">
                    <i class="fa-solid fa-handshake me-1"></i> {{ $vendorSubscription->vendor?->name }}
                    @if($vendorSubscription->vendor?->company_name)
                        ({{ $vendorSubscription->vendor->company_name }})
                    @endif
                </p>

                <ul class="list-group list-group-flush mb-4">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">{{ __('vendor_subscription.fields.type') }}</span>
                        <span class="badge bg-label-info">{{ __('vendor_subscription.type_enums.' . $vendorSubscription->type) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">{{ __('vendor_subscription.fields.branch') }}</span>
                        <span class="badge bg-label-secondary">{{ $vendorSubscription->branch?->name ?? __('vendor_subscription.fields.all_branches') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">{{ __('vendor_subscription.fields.billing_cycle') }}</span>
                        <span class="badge bg-label-primary">{{ __('vendor_subscription.cycles.' . $vendorSubscription->billing_cycle) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">{{ __('vendor_subscription.fields.cost') }}</span>
                        <strong class="text-primary fs-5"><span dir="ltr">{{ number_format($vendorSubscription->cost, 2) }}</span> {{ $vendorSubscription->currency }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">{{ __('vendor_subscription.fields.start_date') }}</span>
                        <span dir="ltr">{{ $vendorSubscription->start_date?->format('Y-m-d') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">{{ __('vendor_subscription.fields.next_billing_date') }}</span>
                        <div>
                            <strong class="text-danger me-1" dir="ltr">{{ $vendorSubscription->next_billing_date?->format('Y-m-d') ?? '-' }}</strong>
                            @if($vendorSubscription->remaining_days !== null)
                                @if($vendorSubscription->remaining_days > 0)
                                    <span class="badge bg-label-warning">متبقي {{ $vendorSubscription->remaining_days }} يوم</span>
                                @elseif($vendorSubscription->remaining_days === 0)
                                    <span class="badge bg-label-danger">مستحق اليوم</span>
                                @else
                                    <span class="badge bg-label-danger">متأخر {{ abs($vendorSubscription->remaining_days) }} يوم</span>
                                @endif
                            @endif
                        </div>
                    </li>
                </ul>

                @if($vendorSubscription->attachment)
                    <div class="mb-3">
                        <label class="form-label d-block text-muted small">{{ __('vendor_subscription.fields.attachment') }}</label>
                        <a href="{{ str_starts_with($vendorSubscription->attachment, 'http') ? $vendorSubscription->attachment : asset('storage/' . $vendorSubscription->attachment) }}" target="_blank" class="btn btn-label-secondary w-100">
                            <i class="fa-solid fa-paperclip me-1"></i> معاينة وثيقة العقد / الفاتورة
                        </a>
                    </div>
                @endif

                @if($vendorSubscription->notes)
                    <div class="p-3 border rounded text-secondary small">
                        <strong>ملاحظات:</strong> {{ $vendorSubscription->notes }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card h-100 shadow-sm">
            <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-receipt text-success me-2"></i> سجل التحويلات والدفعات المادية
                </h5>
                @if(admin_can('vendor_subscription_update'))
                    <button type="button" class="btn btn-success waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                        <i class="fa-solid fa-plus me-1"></i> {{ __('vendor_subscription.actions.record_payment') }}
                    </button>
                @endif
            </div>

            <div class="card-body pt-3">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('vendor_subscription.fields.payment_date') }}</th>
                                <th>{{ __('vendor_subscription.fields.paid_amount') }}</th>
                                <th>{{ __('vendor_subscription.fields.payment_method') }}</th>
                                <th>{{ __('vendor_subscription.fields.transaction_reference') }}</th>
                                <th>{{ __('vendor_subscription.fields.attachment') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vendorSubscription->payments as $payment)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><span dir="ltr">{{ $payment->payment_date?->format('Y-m-d') }}</span></td>
                                    <td><strong class="text-success" dir="ltr">{{ number_format($payment->amount, 2) }}</strong> {{ $vendorSubscription->currency }}</td>
                                    <td><span class="badge bg-label-info">{{ $payment->payment_method }}</span></td>
                                    <td><span dir="ltr">{{ $payment->transaction_reference ?? '-' }}</span></td>
                                    <td>
                                        @if($payment->attachment)
                                            <a href="{{ asset('storage/' . $payment->attachment) }}" target="_blank" class="btn btn-sm btn-icon btn-label-secondary" title="معاينة المرفق" aria-label="معاينة المرفق">
                                                <i class="fa-solid fa-paperclip"></i>
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-receipt fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                        لم يتم تسجيل أي دفعات أو تحويلات مادية لهذا الاشتراك بعد
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Record Payment --}}
@if(admin_can('vendor_subscription_update'))
    <div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.vendor-subscriptions.record-payment', $vendorSubscription->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-success">
                            <i class="fa-solid fa-receipt me-2"></i> {{ __('vendor_subscription.actions.record_payment') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vendor_subscription.fields.paid_amount') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required value="{{ $vendorSubscription->cost }}" dir="ltr" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vendor_subscription.fields.payment_date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control" required value="{{ date('Y-m-d') }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vendor_subscription.fields.payment_method') }} <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select select2" required>
                                    @foreach (__('vendor_subscription.payment_methods') as $methodKey => $methodLabel)
                                        <option value="{{ $methodKey }}">{{ $methodLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vendor_subscription.fields.transaction_reference') }}</label>
                                <input type="text" name="transaction_reference" class="form-control" placeholder="رقم الإيصال / الحوالة..." dir="ltr" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('vendor_subscription.fields.renew_next_date') }}</label>
                                <input type="date" name="renew_next_date" class="form-control" placeholder="اتركه فارغاً للتمديد التلقائي حسب دورة الفوترة" />
                                <small class="text-muted">إذا تُرِك فارغاً، سيتم تمديد تاريخ الاستحقاق القادم تلقائياً حسب الدورة ({{ __('vendor_subscription.cycles.' . $vendorSubscription->billing_cycle) }}).</small>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('vendor_subscription.fields.attachment') }}</label>
                                <input type="file" name="attachment_file" class="form-control" accept=".pdf,.png,.jpg,.jpeg" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('vendor_subscription.fields.notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="ملاحظات السداد..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary waves-effect" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        <button type="submit" class="btn btn-success waves-effect waves-light">
                            <i class="fa-solid fa-check me-1"></i> حفظ وتأكيد الدفعة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
