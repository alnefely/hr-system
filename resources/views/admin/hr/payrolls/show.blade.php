@extends('layouts.admin.app')
@section('title', __('payroll.details_title', ['title' => $payroll->title]))

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 border-bottom pb-3">
                    <div>
                        <h4 class="mb-1 fw-bold">{{ $payroll->title }}</h4>
                        <div class="text-muted d-flex align-items-center gap-2">
                            <span><i class="fa-solid fa-building me-1"></i>{{ $payroll->branch?->name ?? __('payroll.fields.all_branches') }}</span>
                            <span>•</span>
                            <span><i class="fa-solid fa-calendar me-1"></i>{{ __('payroll.fields.month') }} {{ $payroll->month }} / {{ $payroll->year }}</span>
                            <span>•</span>
                            @php
                                $statusBadges = [
                                    'draft' => 'bg-label-warning',
                                    'approved' => 'bg-label-info',
                                    'rejected' => 'bg-label-danger',
                                    'paid' => 'bg-label-success',
                                ];
                            @endphp
                            <span class="badge {{ $statusBadges[$payroll->status] ?? 'bg-label-secondary' }}">
                                {{ __('payroll.enums.' . $payroll->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        {{-- Bulk Print Buttons --}}
                        <a href="{{ route('admin.payrolls.bulk-payslips', $payroll->id) }}" target="_blank" class="btn btn-label-primary">
                            <i class="fa-solid fa-print me-1"></i> {{ __('payroll.actions.print_all', ['count' => $payroll->items->count()]) }}
                        </a>
                        <button type="button" class="btn btn-outline-primary" id="btn_print_selected">
                            <i class="fa-solid fa-print me-1"></i> {!! __('payroll.actions.print_selected', ['count' => '<span id="selected_count">0</span>']) !!}
                        </button>

                        @if($payroll->status == 'approved' && admin_can('payroll_pay'))
                            <button type="button" class="btn btn-outline-success" id="btn_pay_selected">
                                <i class="fa-solid fa-check-double me-1"></i> {{ __('payroll.actions.pay_selected') }}
                            </button>
                        @endif

                        @if($payroll->status == 'draft' && admin_can('payroll_approve'))
                            <form action="{{ route('admin.payrolls.approve', $payroll->id) }}" method="POST">
                                @csrf
                                <button type="button" class="btn btn-info btn-swal-confirm"
                                    data-title="{{ __('payroll.actions.approve') }}"
                                    data-text="{{ __('payroll.messages.confirm_approve') }}"
                                    data-icon="question"
                                    data-btn-text="{{ __('payroll.actions.approve') }}"
                                    data-btn-class="btn-info">
                                    <i class="fa-solid fa-check me-1"></i> {{ __('payroll.actions.approve') }}
                                </button>
                            </form>

                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectPayrollModal">
                                <i class="fa-solid fa-xmark me-1"></i> {{ __('payroll.actions.reject') }}
                            </button>
                        @endif

                        @if($payroll->status == 'approved' && admin_can('payroll_approve'))
                            <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#unapprovePayrollModal">
                                <i class="fa-solid fa-rotate-left me-1"></i> {{ __('payroll.actions.unapprove') }}
                            </button>
                        @endif

                        @if($payroll->status == 'rejected' && admin_can('payroll_create'))
                            <form action="{{ route('admin.payrolls.resubmit', $payroll->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-warning">
                                    <i class="fa-solid fa-rotate-left me-1"></i> {{ __('payroll.actions.resubmit') }}
                                </button>
                            </form>
                        @endif

                        @if($payroll->status == 'approved' && admin_can('payroll_pay'))
                            <form action="{{ route('admin.payrolls.pay', $payroll->id) }}" method="POST">
                                @csrf
                                <button type="button" class="btn btn-success btn-swal-confirm"
                                    data-title="{{ __('payroll.actions.pay_all') }}"
                                    data-text="{{ __('payroll.messages.confirm_pay_all') }}"
                                    data-icon="warning"
                                    data-btn-text="{{ __('payroll.actions.pay_all') }}"
                                    data-btn-class="btn-success">
                                    <i class="fa-solid fa-money-bill-wave me-1"></i> {{ __('payroll.actions.pay_all') }}
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('admin.payrolls.index') }}" class="btn btn-label-secondary">
                            <i class="fa-solid fa-arrow-right me-1"></i> {{ __('payroll.actions.back_to_list') }}
                        </a>
                    </div>
                </div>

                @if($payroll->status == 'rejected' && $payroll->rejection_reason)
                    <div class="alert alert-danger d-flex align-items-start gap-3 mb-4" role="alert">
                        <i class="fa-solid fa-triangle-exclamation fs-3 mt-1"></i>
                        <div>
                            <h6 class="alert-heading fw-bold mb-1">
                                {{ __('payroll.messages.rejected_alert', [
                                    'name' => $payroll->rejector?->name ?? '-',
                                    'date' => $payroll->rejected_at?->format('Y-m-d H:i') ?? '-'
                                ]) }}
                            </h6>
                            <p class="mb-0 fs-6">{{ $payroll->rejection_reason }}</p>
                        </div>
                    </div>
                @endif

                {{-- Totals Summary Grid (No bg-light used) --}}
                <div class="row text-center g-3">
                    <div class="col-md-3">
                        <div class="border rounded p-3 bg-transparent">
                            <small class="text-muted d-block mb-1">{{ __('payroll.fields.total_basic_salary') }}</small>
                            <strong class="fs-5 text-dark"><span dir="ltr">{{ number_format($payroll->total_basic_salary, 2) }}</span></strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 bg-transparent">
                            <small class="text-muted d-block mb-1">{{ __('payroll.fields.total_allowances') }}</small>
                            <strong class="fs-5 text-success">+ <span dir="ltr">{{ number_format($payroll->total_allowances, 2) }}</span></strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 bg-transparent">
                            <small class="text-muted d-block mb-1">{{ __('payroll.fields.total_deductions') }}</small>
                            <strong class="fs-5 text-danger">- <span dir="ltr">{{ number_format($payroll->total_deductions, 2) }}</span></strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 bg-primary text-white">
                            <small class="d-block mb-1 opacity-75">{{ __('payroll.fields.total_net_salary') }}</small>
                            <strong class="fs-4"><span dir="ltr">{{ number_format($payroll->total_net_salary, 2) }}</span> {{ $payroll->branch?->currency ?? 'EGP' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="bulk_print_form" action="{{ route('admin.payrolls.bulk-payslips', $payroll->id) }}" method="POST" target="_blank" data-no-confirm>
    @csrf
    <input type="hidden" name="item_ids" id="bulk_item_ids" value="">
</form>

<form id="bulk_pay_form" action="{{ route('admin.payrolls.pay-selected', $payroll->id) }}" method="POST">
    @csrf
    <input type="hidden" name="item_ids[]" class="bulk-pay-hidden-ids">
</form>

<div class="card">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between py-3">
        <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-users text-primary me-2"></i> {{ __('payroll.fields.items_count', ['count' => $payroll->items->count()]) }}</h5>
    </div>

    <div class="card-body pt-3">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 3%" class="text-center">
                            <input type="checkbox" id="check_all_items" class="form-check-input" />
                        </th>
                        <th style="width: 5%">{{ __('payroll.fields.employee_code') }}</th>
                        <th style="width: 18%">{{ __('payroll.fields.employee_and_job') }}</th>
                        <th style="width: 10%">{{ __('payroll.fields.basic_salary') }}</th>
                        <th style="width: 12%">{{ __('payroll.fields.allowances_bonuses') }}</th>
                        <th style="width: 12%">{{ __('payroll.fields.deductions_penalties') }}</th>
                        <th style="width: 11%">{{ __('payroll.fields.net_payable') }}</th>
                        <th style="width: 10%">{{ __('payroll.fields.payout_status') }}</th>
                        <th style="width: 10%">{{ __('payroll.fields.payment_method') }}</th>
                        <th style="width: 11%" class="text-center">{{ __('payroll.fields.actions_payslip') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payroll->items as $item)
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input item-checkbox" value="{{ $item->id }}" />
                            </td>
                            <td>
                                <span class="badge bg-label-secondary">{{ $item->employee?->employee_code }}</span>
                            </td>
                            <td>
                                <a href="{{ route('admin.employees.show', $item->employee_id) }}" class="fw-bold d-block text-dark" target="_blank">
                                    {{ $item->employee?->name }}
                                </a>
                                <small class="text-muted">{{ $item->employee?->jobTitle?->title ?? '-' }} • {{ $item->employee?->branch?->name }}</small>
                            </td>
                            <td class="fw-bold">
                                <span dir="ltr">{{ number_format($item->basic_salary, 2) }}</span>
                            </td>
                            <td>
                                <span class="text-success fw-bold">+ <span dir="ltr">{{ number_format($item->allowances, 2) }}</span></span>
                                @if(is_array($item->allowance_details) && count($item->allowance_details) > 0)
                                    <a href="javascript:void(0);" class="d-block small text-success mt-1 fw-semibold" data-bs-toggle="modal" data-bs-target="#editItemDetailsModal_{{ $item->id }}">
                                        <i class="fa-solid fa-list-check me-1"></i> ({{ count($item->allowance_details) }} بند)
                                    </a>
                                @elseif($payroll->status == 'draft' || $payroll->status == 'rejected')
                                    <a href="javascript:void(0);" class="d-block small text-muted mt-1" data-bs-toggle="modal" data-bs-target="#editItemDetailsModal_{{ $item->id }}">
                                        <i class="fa-solid fa-plus me-1"></i> إضافة بدل
                                    </a>
                                @endif
                            </td>
                            <td>
                                <span class="text-danger fw-bold">- <span dir="ltr">{{ number_format($item->deductions, 2) }}</span></span>
                                @if(is_array($item->deduction_details) && count($item->deduction_details) > 0)
                                    <a href="javascript:void(0);" class="d-block small text-danger mt-1 fw-semibold" data-bs-toggle="modal" data-bs-target="#editItemDetailsModal_{{ $item->id }}">
                                        <i class="fa-solid fa-list-check me-1"></i> ({{ count($item->deduction_details) }} بند)
                                    </a>
                                @elseif($payroll->status == 'draft' || $payroll->status == 'rejected')
                                    <a href="javascript:void(0);" class="d-block small text-muted mt-1" data-bs-toggle="modal" data-bs-target="#editItemDetailsModal_{{ $item->id }}">
                                        <i class="fa-solid fa-plus me-1"></i> إضافة خصم
                                    </a>
                                @endif
                            </td>
                            <td>
                                <strong class="fs-6 text-primary" id="net_{{ $item->id }}"><span dir="ltr">{{ number_format($item->net_salary, 2) }}</span></strong>
                            </td>
                            <td>
                                @if($item->status == 'paid')
                                    <span class="badge bg-label-success" title="{{ __('payroll.fields.paid_by') }}: {{ $item->payer?->name ?? '-' }}">
                                        <i class="fa-solid fa-check me-1"></i> {{ __('payroll.enums.paid') }}
                                    </span>
                                    @if($item->paid_at)
                                        <small class="d-block text-muted" style="font-size: 0.75rem;">{{ $item->paid_at->format('Y-m-d') }}</small>
                                    @endif
                                @else
                                    <span class="badge bg-label-warning">{{ __('payroll.enums.pending') }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-label-info">{{ __('payroll.enums.' . $item->payment_method) }}</span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-label-warning me-1 mb-1" data-bs-toggle="modal" data-bs-target="#editItemDetailsModal_{{ $item->id }}" title="{{ __('payroll.actions.details') }}">
                                    <i class="fa-solid fa-list-check"></i>
                                </button>

                                @if($payroll->status == 'approved' && $item->status != 'paid' && admin_can('payroll_pay'))
                                    <button type="button" class="btn btn-sm btn-label-success me-1 mb-1" data-bs-toggle="modal" data-bs-target="#paySingleModal_{{ $item->id }}" title="{{ __('payroll.actions.pay') }}">
                                        <i class="fa-solid fa-hand-holding-dollar"></i>
                                    </button>
                                @endif

                                <a href="{{ route('admin.payrolls.payslip', $item->id) }}" target="_blank" class="btn btn-sm btn-label-secondary mb-1" title="{{ __('payroll.actions.print_payslip') }}">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">{{ __('payroll.fields.no_items') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Timeline Audit Log Card at the bottom of the page --}}
<div class="card mt-4">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> {{ __('payroll.fields.audit_log') }}
        </h5>
        <span class="badge bg-label-secondary"><i class="fa-solid fa-shield-halved me-1"></i> سجل الاعتمادات والحركات الإدارية</span>
    </div>
    <div class="card-body pt-4">
        @php
            $timelineEvents = collect();

            if ($payroll->created_at) {
                $timelineEvents->push([
                    'type' => 'created',
                    'title' => __('payroll.fields.created_by'),
                    'user' => $payroll->creator?->name ?? '-',
                    'date' => $payroll->created_at,
                    'badge_bg' => 'bg-primary',
                    'border_color' => 'border-primary',
                    'text_color' => 'text-primary',
                    'icon' => 'fa-file-circle-plus',
                    'desc' => 'تم توليد وإصدار كشف الرواتب واستدعاء استحقاقات الموظفين النشطين في الفرع.',
                ]);
            }

            if ($payroll->approved_at) {
                $timelineEvents->push([
                    'type' => 'approved',
                    'title' => __('payroll.fields.approved_by'),
                    'user' => $payroll->approver?->name ?? '-',
                    'date' => $payroll->approved_at,
                    'badge_bg' => 'bg-info',
                    'border_color' => 'border-info',
                    'text_color' => 'text-info',
                    'icon' => 'fa-circle-check',
                    'desc' => 'تمت مراجعة الكشف واعتماده رسمياً من الإدارة وتفعيله لعملية الصرف.',
                ]);
            }

            if ($payroll->rejected_at) {
                $timelineEvents->push([
                    'type' => 'rejected',
                    'title' => __('payroll.fields.rejected_by'),
                    'user' => $payroll->rejector?->name ?? '-',
                    'date' => $payroll->rejected_at,
                    'badge_bg' => 'bg-danger',
                    'border_color' => 'border-danger',
                    'text_color' => 'text-danger',
                    'icon' => 'fa-circle-xmark',
                    'reason' => $payroll->rejection_reason,
                    'reason_label' => __('payroll.fields.rejection_reason'),
                ]);
            }

            if ($payroll->unapproved_at) {
                $timelineEvents->push([
                    'type' => 'unapproved',
                    'title' => __('payroll.fields.unapproved_by'),
                    'user' => $payroll->unapprover?->name ?? '-',
                    'date' => $payroll->unapproved_at,
                    'badge_bg' => 'bg-warning',
                    'border_color' => 'border-warning',
                    'text_color' => 'text-warning',
                    'icon' => 'fa-rotate-left',
                    'reason' => $payroll->unapprove_reason,
                    'reason_label' => __('payroll.fields.unapprove_reason'),
                ]);
            }

            if ($payroll->paid_at) {
                $timelineEvents->push([
                    'type' => 'paid',
                    'title' => __('payroll.fields.paid_by'),
                    'user' => $payroll->payer?->name ?? '-',
                    'date' => $payroll->paid_at,
                    'badge_bg' => 'bg-success',
                    'border_color' => 'border-success',
                    'text_color' => 'text-success',
                    'icon' => 'fa-money-bill-wave',
                    'desc' => 'تم تأكيد وتوثيق صرف وتكلفة الرواتب بالكامل وإقفال العملية.',
                ]);
            }

            $timelineEvents = $timelineEvents->sortByDesc('date');
        @endphp

        <div class="timeline-container px-2">
            <ul class="list-unstyled position-relative border-start border-2 ps-4 ms-2 mb-0">
                @foreach($timelineEvents as $event)
                    <li class="position-relative {{ $loop->last ? 'mb-0' : 'mb-4' }}">
                        <span class="position-absolute translate-middle-x {{ $event['badge_bg'] }} text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; right: -29px; top: 0;">
                            <i class="fa-solid {{ $event['icon'] }} fs-7"></i>
                        </span>
                        <div class="p-3 rounded border {{ $event['border_color'] }}">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="mb-0 fw-bold {{ $event['text_color'] }}">{{ $event['title'] }}: <span class="text-dark">{{ $event['user'] }}</span></h6>
                                <small class="text-muted"><i class="fa-solid fa-clock me-1"></i>{{ $event['date']?->format('Y-m-d H:i A') }}</small>
                            </div>
                            @if(isset($event['desc']))
                                <p class="text-muted mb-0 small">{{ $event['desc'] }}</p>
                            @endif
                            @if(isset($event['reason']))
                                <div class="alert alert-{{ $event['type'] == 'rejected' ? 'danger' : 'warning' }} p-2 mb-0 mt-2 small">
                                    <strong>{{ $event['reason_label'] }}:</strong> {{ $event['reason'] }}
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

{{-- Modal for Rejecting Payroll --}}
@if($payroll->status == 'draft' && admin_can('payroll_approve'))
    <div class="modal fade" id="rejectPayrollModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.payrolls.reject', $payroll->id) }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-danger">
                            <i class="fa-solid fa-circle-xmark me-2"></i> {{ __('payroll.actions.confirm_reject') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="mb-3">
                            <label class="form-label fw-bold">{{ __('payroll.fields.rejection_reason') }} <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" class="form-control" rows="4" required placeholder="{{ __('payroll.fields.rejection_reason_hint') }}"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        <button type="submit" class="btn btn-danger"><i class="fa-solid fa-xmark me-1"></i> {{ __('payroll.actions.reject') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- Modal for Un-approving Payroll --}}
@if($payroll->status == 'approved' && admin_can('payroll_approve'))
    <div class="modal fade" id="unapprovePayrollModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.payrolls.unapprove', $payroll->id) }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-warning">
                            <i class="fa-solid fa-rotate-left me-2"></i> {{ __('payroll.actions.confirm_unapprove') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="mb-3">
                            <label class="form-label fw-bold">{{ __('payroll.fields.unapprove_reason') }} <span class="text-danger">*</span></label>
                            <textarea name="unapprove_reason" class="form-control" rows="4" required placeholder="{{ __('payroll.fields.unapprove_reason_hint') }}"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        <button type="submit" class="btn btn-warning"><i class="fa-solid fa-rotate-left me-1"></i> {{ __('payroll.actions.unapprove') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- Modals for Single Employee Payout Confirmation --}}
@if($payroll->status == 'approved' && admin_can('payroll_pay'))
    @foreach($payroll->items as $item)
        @if($item->status != 'paid')
            <div class="modal fade" id="paySingleModal_{{ $item->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="{{ route('admin.payrolls.pay-item', $item->id) }}" method="POST">
                            @csrf
                            <div class="modal-header border-bottom">
                                <h5 class="modal-title fw-bold">
                                    <i class="fa-solid fa-hand-holding-dollar me-2 text-success"></i> {{ __('payroll.actions.pay') }}: {{ $item->employee?->name }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body pt-3">
                                <div class="alert alert-info py-2 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>{{ __('payroll.fields.net_payable') }}:</span>
                                        <strong class="fs-6 text-primary"><span dir="ltr">{{ number_format($item->net_salary, 2) }}</span> {{ $payroll->branch?->currency ?? 'EGP' }}</strong>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ __('payroll.fields.payment_method') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('payroll.hints.payment_method')" /></label>
                                    <select name="payment_method" class="form-select select2" required>
                                        <option value="bank_transfer" {{ $item->payment_method == 'bank_transfer' ? 'selected' : '' }}>{{ __('payroll.enums.bank_transfer') }}</option>
                                        <option value="cash" {{ $item->payment_method == 'cash' ? 'selected' : '' }}>{{ __('payroll.enums.cash') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                                <button type="submit" class="btn btn-success"><i class="fa-solid fa-check me-1"></i> {{ __('payroll.actions.confirm_pay_single') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endif

{{-- Modals for Detailed Allowances & Deductions with Reasons --}}
@foreach($payroll->items as $item)
    <div class="modal fade" id="editItemDetailsModal_{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.payrolls.update-item-details', $item->id) }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold">
                            <i class="fa-solid fa-list-check me-2"></i> {{ __('payroll.actions.details') }}: {{ $item->employee?->name }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        
                        {{-- Allowances & Bonuses Section --}}
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-success mb-0"><i class="fa-solid fa-plus-circle me-1"></i> {{ __('payroll.fields.allowances_bonuses') }} <x-inputs.tip :text="__('payroll.hints.manual_allowances')" /></h6>
                            @if($payroll->status == 'draft' || $payroll->status == 'rejected')
                                <button type="button" class="btn btn-sm btn-outline-success add-allowance-row-btn" data-item="{{ $item->id }}">
                                    <i class="fa-solid fa-plus me-1"></i> {{ __('payroll.fields.add_allowance_row') }}
                                </button>
                            @endif
                        </div>
                        <table class="table table-sm table-bordered mb-4" id="allowances_table_{{ $item->id }}">
                            <thead>
                                <tr>
                                    <th>{{ __('payroll.fields.allowance_title') }}</th>
                                    <th style="width: 30%">{{ __('payroll.fields.allowance_amount') }}</th>
                                    @if($payroll->status == 'draft' || $payroll->status == 'rejected')
                                        <th style="width: 10%" class="text-center">{{ __('global.delete') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @if(is_array($item->allowance_details) && count($item->allowance_details) > 0)
                                    @foreach($item->allowance_details as $alIdx => $al)
                                        <tr>
                                            <td>
                                                <input type="text" name="allowance_details[{{ $alIdx }}][title]" value="{{ $al['title'] ?? $al['name'] ?? '' }}" class="form-control form-control-sm" {{ ($payroll->status != 'draft' && $payroll->status != 'rejected') ? 'readonly' : 'required' }} />
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="allowance_details[{{ $alIdx }}][amount]" value="{{ $al['amount'] ?? 0 }}" class="form-control form-control-sm text-end" {{ ($payroll->status != 'draft' && $payroll->status != 'rejected') ? 'readonly' : 'required' }} />
                                            </td>
                                            @if($payroll->status == 'draft' || $payroll->status == 'rejected')
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-label-danger remove-detail-row"><i class="fa-solid fa-trash"></i></button>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-2">لا يوجد بدل إضافي مخصص.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>

                        {{-- Deductions Section --}}
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-danger mb-0"><i class="fa-solid fa-minus-circle me-1"></i> {{ __('payroll.fields.deductions_penalties') }} <x-inputs.tip :text="__('payroll.hints.manual_deductions')" /></h6>
                            @if($payroll->status == 'draft' || $payroll->status == 'rejected')
                                <button type="button" class="btn btn-sm btn-outline-danger add-deduction-row-btn" data-item="{{ $item->id }}">
                                    <i class="fa-solid fa-plus me-1"></i> {{ __('payroll.fields.add_deduction_row') }}
                                </button>
                            @endif
                        </div>
                        <table class="table table-sm table-bordered mb-4" id="deductions_table_{{ $item->id }}">
                            <thead>
                                <tr>
                                    <th>{{ __('payroll.fields.deduction_title') }}</th>
                                    <th style="width: 30%">{{ __('payroll.fields.allowance_amount') }}</th>
                                    @if($payroll->status == 'draft' || $payroll->status == 'rejected')
                                        <th style="width: 10%" class="text-center">{{ __('global.delete') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @if(is_array($item->deduction_details) && count($item->deduction_details) > 0)
                                    @foreach($item->deduction_details as $dedIdx => $ded)
                                        <tr>
                                            <td>
                                                <input type="text" name="deduction_details[{{ $dedIdx }}][title]" value="{{ $ded['title'] ?? $ded['name'] ?? '' }}" class="form-control form-control-sm" {{ ($payroll->status != 'draft' && $payroll->status != 'rejected') ? 'readonly' : 'required' }} />
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="deduction_details[{{ $dedIdx }}][amount]" value="{{ $ded['amount'] ?? 0 }}" class="form-control form-control-sm text-end" {{ ($payroll->status != 'draft' && $payroll->status != 'rejected') ? 'readonly' : 'required' }} />
                                            </td>
                                            @if($payroll->status == 'draft' || $payroll->status == 'rejected')
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-label-danger remove-detail-row"><i class="fa-solid fa-trash"></i></button>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-2">لا يوجد خصومات أو جزاءات مسجلة بهذا الشهر.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('payroll.fields.payment_method') }} <x-inputs.tip :text="__('payroll.hints.payment_method')" /></label>
                                <select name="payment_method" class="form-select select2" {{ ($payroll->status != 'draft' && $payroll->status != 'rejected') ? 'disabled' : '' }}>
                                    <option value="bank_transfer" {{ $item->payment_method == 'bank_transfer' ? 'selected' : '' }}>{{ __('payroll.enums.bank_transfer') }}</option>
                                    <option value="cash" {{ $item->payment_method == 'cash' ? 'selected' : '' }}>{{ __('payroll.enums.cash') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <x-inputs.field name="notes" label="{{ __('payroll.fields.notes') }}" :tooltip="__('payroll.hints.item_notes')" :value="$item->notes" :readonly="$payroll->status != 'draft' && $payroll->status != 'rejected'" />
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        @if($payroll->status == 'draft' || $payroll->status == 'rejected')
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> {{ __('global.save') }}</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

@endsection

@push('js')
<script>
$(document).ready(function() {
    // Select All Checkbox logic
    $('#check_all_items').on('change', function() {
        $('.item-checkbox').prop('checked', $(this).is(':checked'));
        updateSelectedCount();
    });

    $(document).on('change', '.item-checkbox', function() {
        updateSelectedCount();
    });

    function updateSelectedCount() {
        let count = $('.item-checkbox:checked').length;
        $('#selected_count').text(count);
    }

    // Print Selected Payslips Button
    $('#btn_print_selected').on('click', function() {
        let selected = [];
        $('.item-checkbox:checked').each(function() {
            selected.push($(this).val());
        });

        if (selected.length === 0) {
            alert('{{ __('payroll.messages.select_one_for_print') }}');
            return;
        }

        $('#bulk_item_ids').val(selected.join(','));
        $('#bulk_print_form').submit();
    });

    // Pay Selected Employees Button
    $('#btn_pay_selected').on('click', function() {
        let selected = [];
        $('.item-checkbox:checked').each(function() {
            selected.push($(this).val());
        });

        if (selected.length === 0) {
            alert('{{ __('payroll.messages.select_one_for_pay') }}');
            return;
        }

        // نفس شكل التأكيد في باقي النظام (بدل confirm() المدمج في المتصفح)
        Swal.fire({
            title: @json(__('confirm.title')),
            text: @json(__('payroll.messages.confirm_pay_selected')),
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: @json(__('confirm.button')),
            cancelButtonText: @json(__('confirm.cancel')),
            customClass: { confirmButton: 'btn btn-primary me-2', cancelButton: 'btn btn-label-secondary' },
            buttonsStyling: false,
        }).then(function (result) {
            if (!result.isConfirmed) return;

            let form = $('#bulk_pay_form');
            form.find('.bulk-pay-hidden-ids').remove();
            selected.forEach(function(id) {
                form.append(`<input type="hidden" name="item_ids[]" class="bulk-pay-hidden-ids" value="${id}">`);
            });
            form.submit();   // submit() مابيطلقش التأكيد العام تاني
        });
    });

    // Add Allowance Row inside Modal
    $(document).on('click', '.add-allowance-row-btn', function() {
        let itemId = $(this).data('item');
        let idx = $('#allowances_table_' + itemId + ' tbody tr').length;
        let html = `
            <tr>
                <td>
                    <input type="text" name="allowance_details[${idx}][title]" class="form-control form-control-sm" required />
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="allowance_details[${idx}][amount]" value="0" class="form-control form-control-sm text-end" required />
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-label-danger remove-detail-row"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>
        `;
        $('#allowances_table_' + itemId + ' tbody').append(html);
    });

    // Add Deduction Row inside Modal
    $(document).on('click', '.add-deduction-row-btn', function() {
        let itemId = $(this).data('item');
        let idx = $('#deductions_table_' + itemId + ' tbody tr').length;
        let html = `
            <tr>
                <td>
                    <input type="text" name="deduction_details[${idx}][title]" class="form-control form-control-sm" required />
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="deduction_details[${idx}][amount]" value="0" class="form-control form-control-sm text-end" required />
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-label-danger remove-detail-row"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>
        `;
        $('#deductions_table_' + itemId + ' tbody').append(html);
    });

    // Remove detail row in modal
    $(document).on('click', '.remove-detail-row', function() {
        $(this).closest('tr').remove();
    });
});
</script>
@endpush
