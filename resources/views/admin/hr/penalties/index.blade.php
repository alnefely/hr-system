@extends('layouts.admin.app')
@section('title', __('penalty.title'))

@section('content')
{{-- Filter & Employee Stats Card --}}
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.penalties.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label fw-bold mb-1">فلترة حسب موظف معين لمعاينة سجل الجزاءات والخصومات</label>
                <select name="employee_id" class="form-select select2" onchange="this.form.submit()">
                    <option value="">-- جميع الموظفين --</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ $selectedEmployeeId == $emp->id ? 'selected' : '' }}>
                            {{ $emp->name }} ({{ $emp->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <a href="{{ route('admin.penalties.index') }}" class="btn btn-label-secondary w-100 d-flex align-items-center justify-content-center" style="height: 38px;">
                    <i class="fa-solid fa-rotate-left me-1"></i> عرض كافة الموظفين
                </a>
            </div>
        </form>

        @if($selectedEmployee && $penaltyStats)
            <div class="row g-3 mt-2 pt-3 border-top">
                <div class="col-md-4">
                    <div class="p-3 rounded border text-center">
                        <small class="text-muted d-block mb-1">إجمالي عدد الجزاءات</small>
                        <h4 class="mb-0 fw-bold text-danger">{{ $penaltyStats['total_penalties'] }} <small class="fs-6">جزاء</small></h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded border text-center">
                        <small class="text-muted d-block mb-1">إجمالي الأيام المخصومة</small>
                        <h4 class="mb-0 fw-bold text-warning">{{ $penaltyStats['total_days'] }} <small class="fs-6">يوم</small></h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded border text-center">
                        <small class="text-muted d-block mb-1">إجمالي المبالغ المخصومة</small>
                        <h4 class="mb-0 fw-bold text-danger"><span dir="ltr">{{ number_format($penaltyStats['total_amount'], 2) }}</span> <small class="fs-6">{{ $penaltyStats['currency'] }}</small></h4>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-gavel text-danger me-2"></i> {{ __('penalty.title') }}
            @if($selectedEmployee)
                <span class="text-muted fs-6">({{ $selectedEmployee->name }})</span>
            @endif
        </h5>
        <div class="d-flex gap-2">
            @if (admin_can('penalty_trash'))
                <a href="{{ route('admin.penalties.trash') }}" class="btn btn-label-danger waves-effect">
                    <i class="fa-solid fa-trash me-1"></i> {{ __('global.trash') }}
                    @if(isset($trashCount) && $trashCount > 0)
                        <span class="badge bg-danger rounded-pill ms-1">{{ $trashCount }}</span>
                    @endif
                </a>
            @endif
            <a href="{{ route('admin.penalties.index', array_merge(request()->all(), ['export' => 'excel'])) }}" class="btn btn-label-success waves-effect">
                <i class="fa-solid fa-file-excel me-1"></i> تصدير Excel
            </a>
            @if(admin_can('penalty_create'))
                <button type="button" class="btn btn-danger waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#createPenaltyModal">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('penalty.actions.add_new') }}
                </button>
            @endif
        </div>
    </div>

    <div class="card-body pt-4">
        @php
            // المصدر الوحيد للأنواع: type_enums المطابق لـ enum الجدول
            $penaltyTypeOptions = __('penalty.type_enums');

            $columns = [
                ['data' => 'id', 'name' => 'id'],
                ['data' => 'employee_name', 'name' => 'employee.name'],
                ['data' => 'type_badge', 'name' => 'penalty_type', 'orderable' => false, 'searchType' => 'select', 'searchOptions' => $penaltyTypeOptions],
                ['data' => 'deduction_summary', 'name' => 'deduction_amount', 'orderable' => false],
                ['data' => 'formatted_date', 'name' => 'penalty_date', 'searchType' => 'date'],
                ['data' => 'reason', 'name' => 'reason'],
                ['data' => 'creator_name', 'name' => 'creator.name'],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ];
        @endphp

        <x-dataTable.table
            :columns="[
                __('global.id'),
                __('penalty.fields.employee'),
                __('penalty.fields.penalty_type'),
                'قيمة الخصم / الجزاء',
                __('penalty.fields.penalty_date'),
                __('penalty.fields.reason'),
                __('penalty.fields.created_by'),
                __('global.actions'),
            ]"
            :fields="$columns"
            :url="route('admin.penalties.index', request()->query())"
        />
    </div>
</div>

{{-- Modal Create Penalty --}}
@if(admin_can('penalty_create'))
    <div class="modal fade" id="createPenaltyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.penalties.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-gavel me-2"></i> {{ __('penalty.create_title') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">{{ __('penalty.fields.employee') }} <span class="text-danger">*</span></label>
                                <select name="employee_id" id="penalty_employee_select" class="form-select select2" required>
                                    <option value="">{{ __('global.choose') }}...</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}" 
                                            data-daily-rate="{{ $emp->daily_rate }}"
                                            data-currency="{{ $emp->branch?->currency ?? 'EGP' }}"
                                            {{ $selectedEmployeeId == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->name }} ({{ $emp->employee_code }}) - راتب: {{ number_format($emp->basic_salary, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('penalty.fields.penalty_type') }} <span class="text-danger">*</span></label>
                                <select name="penalty_type" id="penalty_type_select" class="form-select select2" required>
                                    @foreach (__('penalty.type_enums') as $typeKey => $typeLabel)
                                        <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6" id="days_count_wrapper">
                                <label class="form-label">{{ __('penalty.fields.days_count') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.5" min="0.5" name="days_count" id="penalty_days_count" class="form-control" value="1" dir="ltr" />
                                <div id="penalty_calc_preview" class="small text-danger fw-semibold mt-1"></div>
                            </div>
                            <div class="col-md-6 d-none" id="deduction_amount_wrapper">
                                <label class="form-label">{{ __('penalty.fields.deduction_amount') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="deduction_amount" id="penalty_deduction_amount" class="form-control" placeholder="0.00" disabled dir="ltr" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('penalty.fields.penalty_date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="penalty_date" class="form-control" value="{{ date('Y-m-d') }}" required />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('penalty.fields.reason') }} <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control" rows="2" required placeholder="سبب الجزاء أو المخالفة بالتفصيل..."></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('penalty.fields.notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="ملاحظات إدارية اختيارية..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary waves-effect" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        <button type="submit" class="btn btn-danger waves-effect waves-light"><i class="fa-solid fa-check me-1"></i> {{ __('global.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- نافذة الرفض: السبب إجباري عشان القرار يفضل موثّق --}}
<div class="modal fade" id="rejectPenaltyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="rejectPenaltyForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-circle-xmark text-danger me-2"></i>
                        {{ __('penalty.actions.reject') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning small">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        {{ __('penalty.messages.reject_hint') }}
                    </div>

                    <label class="form-label" for="reject_reason_penalty">
                        {{ __('global.reason') }} <span class="text-danger">*</span>
                    </label>
                    <textarea name="rejection_reason"
                              id="reject_reason_penalty"
                              class="form-control"
                              rows="3"
                              minlength="3"
                              maxlength="500"
                              required></textarea>
                </div>

                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        {{ __('global.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-xmark me-1"></i> {{ __('global.reject') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('js')
<script>
$(document).ready(function() {
    function calculateDayDeduction() {
        var opt = $('#penalty_employee_select option:selected');
        var dailyRate = parseFloat(opt.data('daily-rate')) || 0;
        var currency = opt.data('currency') || 'EGP';
        var days = parseFloat($('#penalty_days_count').val()) || 0;
        var penaltyType = $('#penalty_type_select').val();

        if (penaltyType === 'days_deduction' && dailyRate > 0 && days > 0) {
            var totalDeduction = (dailyRate * days).toFixed(2);
            $('#penalty_calc_preview').html('<i class="fa-solid fa-calculator me-1"></i> سعر اليوم: ' + dailyRate.toFixed(2) + ' ' + currency + ' | الإجمالي المخصوم: ' + totalDeduction + ' ' + currency);
        } else {
            $('#penalty_calc_preview').html('');
        }
    }

    function togglePenaltyInputs() {
        var val = $('#penalty_type_select').val();
        var $daysWrapper = $('#days_count_wrapper');
        var $amountWrapper = $('#deduction_amount_wrapper');
        var $daysInput = $('#penalty_days_count');
        var $amountInput = $('#penalty_deduction_amount');

        if (val === 'days_deduction') {
            $daysWrapper.removeClass('d-none');
            $daysInput.prop('disabled', false).attr('required', true);

            $amountWrapper.addClass('d-none');
            $amountInput.prop('disabled', true).removeAttr('required');
            calculateDayDeduction();
        } else if (val === 'amount_deduction') {
            $daysWrapper.addClass('d-none');
            $daysInput.prop('disabled', true).removeAttr('required');

            $amountWrapper.removeClass('d-none');
            $amountInput.prop('disabled', false).attr('required', true);
            $('#penalty_calc_preview').html('');
        } else {
            $daysWrapper.addClass('d-none');
            $daysInput.prop('disabled', true).removeAttr('required');

            $amountWrapper.addClass('d-none');
            $amountInput.prop('disabled', true).removeAttr('required');
            $('#penalty_calc_preview').html('');
        }
    }

    $('#penalty_type_select').on('change', togglePenaltyInputs);
    $('#penalty_employee_select').on('change', calculateDayDeduction);
    $('#penalty_days_count').on('input change', calculateDayDeduction);

    togglePenaltyInputs();
});
</script>
@endpush

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // زرار الرفض بيتولد جوّه DataTables، فبنستخدم delegation
    $(document).on('click', '.reject-btn', function () {
        var id = $(this).data('id');
        $('#rejectPenaltyForm').attr('action', "{{ url('admin/penalties') }}" + '/' + id + '/reject');
        $('#rejectPenaltyModal').modal('show');
    });
});
</script>
@endpush
