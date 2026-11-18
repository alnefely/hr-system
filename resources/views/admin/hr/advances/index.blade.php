@extends('layouts.admin.app')
@section('title', __('advance.title'))

@section('content')
{{-- Filter & Employee Stats Card --}}
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.advances.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold mb-1">{{ __('advance.fields.employee') }}</label>
                <select name="employee_id" class="form-select select2">
                    <option value="">{{ __('advance.filters.all_employees') }}</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ $selectedEmployeeId == $emp->id ? 'selected' : '' }}>
                            {{ $emp->name }} ({{ $emp->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold mb-1">{{ __('advance.filters.from_date') }}</label>
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold mb-1">{{ __('advance.filters.to_date') }}</label>
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold mb-1">{{ __('advance.fields.status') }}</label>
                <select name="status" class="form-select select2">
                    <option value="">{{ __('advance.filters.all_statuses') }}</option>
                    @foreach (__('advance.status_enums') as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}" {{ request('status') === $statusKey ? 'selected' : '' }}>
                            {{ $statusLabel }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1" style="height: 38px;">
                    <i class="fa-solid fa-filter me-1"></i> {{ __('global.filter') }}
                </button>
                <a href="{{ route('admin.advances.index') }}" class="btn btn-label-secondary" style="height: 38px;" title="{{ __('global.reset_filter') }}">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>

        @if($selectedEmployee && $advanceStats)
            <div class="row g-3 mt-2 pt-3 border-top">
                <div class="col-md-3">
                    <div class="p-3 rounded border text-center">
                        <small class="text-muted d-block mb-1">إجمالي السلف المعتمدة</small>
                        <h4 class="mb-0 fw-bold text-primary"><span dir="ltr">{{ number_format($advanceStats['total_advances'], 2) }}</span> <small class="fs-6">{{ $advanceStats['currency'] }}</small></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded border text-center">
                        <small class="text-muted d-block mb-1">إجمالي المبلغ المسدد</small>
                        <h4 class="mb-0 fw-bold text-success"><span dir="ltr">{{ number_format($advanceStats['total_paid'], 2) }}</span> <small class="fs-6">{{ $advanceStats['currency'] }}</small></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded border text-center">
                        <small class="text-muted d-block mb-1">إجمالي المسقط / المعفى</small>
                        <h4 class="mb-0 fw-bold text-info"><span dir="ltr">{{ number_format($advanceStats['total_waived'], 2) }}</span> <small class="fs-6">{{ $advanceStats['currency'] }}</small></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded border text-center border-danger">
                        <small class="text-muted d-block mb-1">المتبقي المطلوب سداده</small>
                        <h4 class="mb-0 fw-bold text-danger"><span dir="ltr">{{ number_format($advanceStats['total_remaining'], 2) }}</span> <small class="fs-6">{{ $advanceStats['currency'] }}</small></h4>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i> {{ __('advance.title') }}
            @if($selectedEmployee)
                <span class="text-muted fs-6">({{ $selectedEmployee->name }})</span>
            @endif
        </h5>
        <div class="d-flex gap-2">
            @if (admin_can('advance_trash'))
                <a href="{{ route('admin.advances.trash') }}" class="btn btn-label-danger waves-effect">
                    <i class="fa-solid fa-trash me-1"></i> {{ __('global.trash') }}
                    @if(isset($trashCount) && $trashCount > 0)
                        <span class="badge bg-danger rounded-pill ms-1">{{ $trashCount }}</span>
                    @endif
                </a>
            @endif
            <a href="{{ route('admin.advances.index', array_merge(request()->all(), ['export' => 'excel'])) }}" class="btn btn-label-success waves-effect">
                <i class="fa-solid fa-file-excel me-1"></i> تصدير Excel
            </a>
            @if (admin_can('advance_create'))
                <button type="button" class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#createAdvanceModal">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('advance.add_new') }}
                </button>
            @endif
        </div>
    </div>

    <div class="card-body pt-4">
        @php
            $columns = [
                ['data' => 'id', 'name' => 'id'],
                ['data' => 'employee_code', 'name' => 'employee.employee_code'],
                ['data' => 'employee_name', 'name' => 'employee.name'],
                ['data' => 'formatted_amount', 'name' => 'amount'],
                ['data' => 'formatted_installment', 'name' => 'monthly_installment'],
                ['data' => 'formatted_remaining', 'name' => 'remaining_amount'],
                ['data' => 'formatted_advance_date', 'name' => 'advance_date', 'searchType' => 'date'],
                ['data' => 'formatted_start_date', 'name' => 'start_date', 'searchType' => 'date'],
                ['data' => 'status_badge', 'name' => 'status', 'orderable' => false, 'searchType' => 'select', 'searchOptions' => __('advance.status_enums')],
                ['data' => 'approver_name', 'name' => 'approver.name'],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ];
        @endphp

        <x-dataTable.table
            :columns="[
                __('global.id'),
                __('employee.fields.code'),
                __('advance.employee'),
                __('advance.amount'),
                __('advance.monthly_installment'),
                __('advance.remaining_amount'),
                __('advance.advance_date'),
                __('advance.start_date'),
                __('advance.status'),
                __('leave.fields.approved_by'),
                __('global.actions'),
            ]"
            :fields="$columns"
            :url="route('admin.advances.index', request()->query())"
        />
    </div>
</div>

{{-- Create Advance Modal --}}
@if (admin_can('advance_create'))
    <div class="modal fade" id="createAdvanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.advances.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i> {{ __('advance.add_new') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">{{ __('advance.employee') }} <span class="text-danger">*</span></label>
                                <select name="employee_id" class="form-select select2" required>
                                    <option value="">{{ __('global.choose') }}...</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->employee_code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('advance.amount') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('advance.hints.amount')" /></label>
                                <input type="number" step="0.01" min="1" name="amount" id="advance_amount_input" class="form-control" required dir="ltr" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('advance.installments_count') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('advance.hints.installments_count')" /></label>
                                <input type="number" min="1" max="60" name="installments_count" id="advance_installments_input" class="form-control" value="1" required dir="ltr" />
                            </div>
                            <div class="col-md-12">
                                <div class="p-2 border rounded text-center">
                                    <span class="text-muted small">{{ __('advance.monthly_installment') }}:</span>
                                    <h5 class="fw-bold mb-0 text-primary" id="advance_calculated_installment">0.00</h5>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('advance.advance_date') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('advance.hints.advance_date')" /></label>
                                <input type="date" name="advance_date" class="form-control" value="{{ date('Y-m-d') }}" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('advance.start_date') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('advance.hints.start_date')" /></label>
                                <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}" required />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('advance.reason') }}</label>
                                <textarea name="reason" class="form-control" rows="2" placeholder="سبب طلب السلفة..."></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('advance.notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="ملاحظات إضافية..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary waves-effect" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        <button type="submit" class="btn btn-primary waves-effect waves-light"><i class="fa-solid fa-check me-1"></i> {{ __('global.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- Repayments History & Record Payment Modal --}}
<div class="modal fade" id="repaymentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-receipt text-info me-2"></i> سجل دفعات وسداد السلفة: <span id="rep_employee_name" class="text-primary"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="row g-2 mb-3">
                    <div class="col-3 text-center p-2 rounded border">
                        <small class="text-muted d-block">إجمالي السلفة</small>
                        <strong class="text-primary" id="rep_total_amount">0.00</strong>
                    </div>
                    <div class="col-3 text-center p-2 rounded border">
                        <small class="text-muted d-block">المسدد فعلياً</small>
                        <strong class="text-success" id="rep_paid_amount">0.00</strong>
                    </div>
                    <div class="col-3 text-center p-2 rounded border">
                        <small class="text-muted d-block">المعفى / المسقط</small>
                        <strong class="text-info" id="rep_waived_amount">0.00</strong>
                    </div>
                    <div class="col-3 text-center p-2 rounded border border-danger">
                        <small class="text-muted d-block">الرصيد المتبقي</small>
                        <strong class="text-danger" id="rep_remaining_amount">0.00</strong>
                    </div>
                </div>

                <h6 class="fw-bold border-bottom pb-2 mb-3"><i class="fa-solid fa-clock-rotate-left me-1"></i> سجل الدفعات السابقة:</h6>
                <div class="table-responsive mb-4" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-sm table-bordered text-center align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>المبلغ</th>
                                <th>تاريخ السداد</th>
                                <th>نوع الحركة</th>
                                <th>البيان / الملاحظات</th>
                                <th>بواسطة</th>
                            </tr>
                        </thead>
                        <tbody id="repayments_table_body">
                            <tr><td colspan="6" class="text-muted py-3">جاري التحميل...</td></tr>
                        </tbody>
                    </table>
                </div>

                {{-- Form to record a new payment --}}
                <div class="border-top pt-3" id="record_payment_section">
                    <h6 class="fw-bold text-success mb-2"><i class="fa-solid fa-money-bill-wave me-1"></i> تسجيل دفعة سداد جديدة:</h6>
                    <form id="recordPaymentForm" action="" method="POST">
                        @csrf
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">مبلغ الدفعة <span class="text-danger">*</span> <x-inputs.tip :text="__('advance.hints.payment_amount')" /></label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="rep_input_amount" class="form-control form-control-sm" required dir="ltr" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">تاريخ السداد <span class="text-danger">*</span></label>
                                <input type="date" name="repayment_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">ملاحظات</label>
                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="سداد نقدي / إيداع بنكي..." />
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm btn-success w-100">
                                    <i class="fa-solid fa-check me-1"></i> تسجيل
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Manager Waiver Modal --}}
<div class="modal fade" id="waiveAdvanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="waiveAdvanceForm" action="" method="POST">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold text-warning">
                        <i class="fa-solid fa-hand-holding-heart me-2"></i> إسقاط / إعفاء سلفة: <span id="waive_employee_name" class="text-primary"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="alert alert-warning py-2 px-3 small mb-3">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        يمكن للمدير إسقاط جزء من السلفة أو الرصيد المتبقي بالكامل كإعفاء أو مكافأة للموظف دون المطالبة به في مسير الرواتب.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">المبلغ المراد إسقاطه / إعفاؤه <span class="text-danger">*</span> <x-inputs.tip :text="__('advance.hints.waive_amount')" /></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0.01" name="waived_amount" id="waive_amount_input" class="form-control" required dir="ltr" />
                                <button type="button" class="btn btn-outline-warning" id="waive_all_btn">إسقاط كامل المتبقي</button>
                            </div>
                            <small class="text-muted mt-1 d-block">الرصيد المتبقي الحالي: <strong class="text-danger" id="waive_remaining_display">0.00</strong></small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">سبب الإعفاء / الإسقاط <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control" rows="2" required placeholder="مثال: قرار إدارة بمناسبة الأعياد / إعفاء بسبب ظروف خاصة..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-label-secondary waves-effect" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                    <button type="submit" class="btn btn-warning waves-effect waves-light"><i class="fa-solid fa-check me-1"></i> تأكيد الإسقاط وتخفيض السلفة</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('advance_amount_input');
    const installmentsInput = document.getElementById('advance_installments_input');
    const display = document.getElementById('advance_calculated_installment');

    function calc() {
        let amount = parseFloat(amountInput?.value) || 0;
        let count = parseInt(installmentsInput?.value) || 1;
        if (count < 1) count = 1;
        let perMonth = (amount / count).toFixed(2);
        if (display) display.textContent = perMonth;
    }

    if (amountInput && installmentsInput) {
        amountInput.addEventListener('input', calc);
        installmentsInput.addEventListener('input', calc);
    }


    // View Repayments Modal
    $(document).on('click', '.view-repayments-btn', function() {
        let btn = $(this);
        let id = btn.data('id');
        let baseUrl = "{{ url('admin/advances') }}";
        let curr = btn.data('currency');

        $('#rep_employee_name').text(btn.data('employee'));
        $('#rep_total_amount').text(parseFloat(btn.data('total')).toFixed(2) + ' ' + curr);
        $('#rep_paid_amount').text(parseFloat(btn.data('paid')).toFixed(2) + ' ' + curr);
        $('#rep_waived_amount').text(parseFloat(btn.data('waived')).toFixed(2) + ' ' + curr);
        $('#rep_remaining_amount').text(parseFloat(btn.data('remaining')).toFixed(2) + ' ' + curr);
        $('#rep_input_amount').attr('max', btn.data('remaining')).val(btn.data('remaining') > 0 ? btn.data('remaining') : '');

        $('#recordPaymentForm').attr('action', baseUrl + '/' + id + '/record-payment');

        if (parseFloat(btn.data('remaining')) <= 0) {
            $('#record_payment_section').hide();
        } else {
            $('#record_payment_section').show();
        }

        $('#repayments_table_body').html('<tr><td colspan="6" class="text-muted py-3">جاري التحميل...</td></tr>');
        $('#repaymentsModal').modal('show');

        $.get(baseUrl + '/' + id + '/repayments', function(res) {
            let html = '';
            if (res.repayments && res.repayments.length > 0) {
                res.repayments.forEach(function(r, index) {
                    let typeBadge = '';
                    if (r.type === 'payroll_deduction') {
                        typeBadge = '<span class="badge bg-label-primary">استقطاع مسير راتب</span>';
                    } else if (r.type === 'manager_waiver') {
                        typeBadge = '<span class="badge bg-label-warning">إسقاط / إعفاء إداري</span>';
                    } else {
                        typeBadge = '<span class="badge bg-label-success">سداد نقدي مباشر</span>';
                    }
                    html += '<tr>' +
                        '<td>' + (index + 1) + '</td>' +
                        '<td><strong class="text-dark" dir="ltr">' + parseFloat(r.amount).toFixed(2) + '</strong> ' + curr + '</td>' +
                        '<td>' + (r.repayment_date ? r.repayment_date.substring(0, 10) : '-') + '</td>' +
                        '<td>' + typeBadge + '</td>' +
                        '<td>' + (r.notes || '-') + '</td>' +
                        '<td>' + (r.creator ? r.creator.name : 'النظام') + '</td>' +
                    '</tr>';
                });
            } else {
                html = '<tr><td colspan="6" class="text-muted py-3">لا توجد حركات سداد مسجلة لهذه السلفة حتى الآن.</td></tr>';
            }
            $('#repayments_table_body').html(html);
        });
    });

    // Waive Advance Modal
    $(document).on('click', '.waive-advance-btn', function() {
        let btn = $(this);
        let id = btn.data('id');
        let baseUrl = "{{ url('admin/advances') }}";
        let remaining = parseFloat(btn.data('remaining'));
        let curr = btn.data('currency');

        $('#waive_employee_name').text(btn.data('employee'));
        $('#waive_remaining_display').text(remaining.toFixed(2) + ' ' + curr);
        $('#waive_amount_input').attr('max', remaining).val(remaining);
        $('#waiveAdvanceForm').attr('action', baseUrl + '/' + id + '/waive');

        $('#waive_all_btn').off('click').on('click', function() {
            $('#waive_amount_input').val(remaining);
        });

        $('#waiveAdvanceModal').modal('show');
    });
});
</script>
@endpush
