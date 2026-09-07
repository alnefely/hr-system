@extends('layouts.admin.app')
@section('title', __('leave.balances_title'))

@section('content')
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-scale-balanced text-primary me-2"></i> {{ __('leave.balances_title') }} لعام {{ $year }}
        </h5>
        <div class="d-flex gap-2 align-items-center">
            <form action="{{ route('admin.leaves.balances') }}" method="GET" class="d-flex gap-2 align-items-center">
                <select name="year" class="form-select form-select-sm select2" onchange="this.form.submit()">
                    @for($y = date('Y') + 1; $y >= date('Y') - 3; $y--)
                        <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                    @endfor
                </select>
            </form>
            <a href="{{ route('admin.leaves.index') }}" class="btn btn-label-secondary waves-effect">
                <i class="fa-solid fa-arrow-right me-1"></i> {{ __('leave.title') }}
            </a>
        </div>
    </div>

    <div class="card-body pt-4">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('leave.fields.employee') }}</th>
                        <th class="text-center">الإجازات السنوية (المستحق / المستهلك)</th>
                        <th class="text-center">المتبقي سنوي</th>
                        <th class="text-center">الإجازات العارضة (المستحق / المستهلك)</th>
                        <th class="text-center">المتبقي عارضة</th>
                        <th class="text-center">الإجازات المرضية (المستحق / المستهلك)</th>
                        <th class="text-center">المتبقي مرضي</th>
                        @if(admin_can('leave_balance_manage'))
                            <th class="text-center">{{ __('global.actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($balances as $bal)
                        <tr>
                            <td>
                                <strong class="d-block text-dark">{{ $bal->employee?->name }}</strong>
                                <small class="text-muted">{{ $bal->employee?->employee_code }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-label-secondary">{{ $bal->annual_balance }} يوم</span> / 
                                <span class="badge bg-label-warning">{{ $bal->used_annual }} يوم</span>
                            </td>
                            <td class="text-center"><strong class="text-success fs-6">{{ $bal->remaining_annual }} يوم</strong></td>
                            <td class="text-center">
                                <span class="badge bg-label-secondary">{{ $bal->casual_balance }} يوم</span> / 
                                <span class="badge bg-label-warning">{{ $bal->used_casual }} يوم</span>
                            </td>
                            <td class="text-center"><strong class="text-success fs-6">{{ $bal->remaining_casual }} يوم</strong></td>
                            <td class="text-center">
                                <span class="badge bg-label-secondary">{{ $bal->sick_balance }} يوم</span> / 
                                <span class="badge bg-label-warning">{{ $bal->used_sick }} يوم</span>
                            </td>
                            <td class="text-center"><strong class="text-success fs-6">{{ $bal->remaining_sick }} يوم</strong></td>
                            @if(admin_can('leave_balance_manage'))
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary waves-effect edit-balance-btn"
                                        data-id="{{ $bal->id }}"
                                        data-name="{{ $bal->employee?->name }}"
                                        data-annual="{{ $bal->annual_balance }}"
                                        data-used-annual="{{ $bal->used_annual }}"
                                        data-casual="{{ $bal->casual_balance }}"
                                        data-used-casual="{{ $bal->used_casual }}"
                                        data-sick="{{ $bal->sick_balance }}"
                                        data-used-sick="{{ $bal->used_sick }}">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> تعديل الرصيد
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">لا توجد سجلات رصيد إجازات مسجلة لعام {{ $year }}.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Edit Balance Modal --}}
@if(admin_can('leave_balance_manage'))
    <div class="modal fade" id="editBalanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editBalanceForm" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square text-primary me-2"></i> تعديل رصيد الإجازات: <span id="modal_employee_name" class="text-primary"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="alert alert-info py-2 px-3 mb-3 small d-flex align-items-center">
                            <i class="fa-solid fa-circle-info me-2 fs-6"></i>
                            <div>الرصيد المستهلك يُحسب تلقائياً وبدقة من سجل طلبات الإجازات المعتمدة، ويُتاح هنا فقط تعديل الرصيد السنوي المستحق.</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">الرصيد السنوي المستحق <span class="text-danger">*</span></label>
                                <input type="number" step="0.5" min="0" name="annual_balance" id="modal_annual_balance" class="form-control" required dir="ltr" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">السنوي المستهلك (محسوب آلياً)</label>
                                <input type="text" id="modal_used_annual" class="form-control bg-transparent text-muted" readonly dir="ltr" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">الرصيد العارض المستحق <span class="text-danger">*</span></label>
                                <input type="number" step="0.5" min="0" name="casual_balance" id="modal_casual_balance" class="form-control" required dir="ltr" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">العارض المستهلك (محسوب آلياً)</label>
                                <input type="text" id="modal_used_casual" class="form-control bg-transparent text-muted" readonly dir="ltr" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">الرصيد المرضي المستحق <span class="text-danger">*</span></label>
                                <input type="number" step="0.5" min="0" name="sick_balance" id="modal_sick_balance" class="form-control" required dir="ltr" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">المرضي المستهلك (محسوب آلياً)</label>
                                <input type="text" id="modal_used_sick" class="form-control bg-transparent text-muted" readonly dir="ltr" />
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
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.edit-balance-btn', function() {
        let btn = $(this);
        let id = btn.data('id');
        let baseUrl = "{{ url('admin/leaves/balances') }}";
        $('#editBalanceForm').attr('action', baseUrl + '/' + id);
        $('#modal_employee_name').text(btn.data('name'));
        $('#modal_annual_balance').val(btn.data('annual'));
        $('#modal_used_annual').val(btn.data('used-annual'));
        $('#modal_casual_balance').val(btn.data('casual'));
        $('#modal_used_casual').val(btn.data('used-casual'));
        $('#modal_sick_balance').val(btn.data('sick'));
        $('#modal_used_sick').val(btn.data('used-sick'));
        $('#editBalanceModal').modal('show');
    });
});
</script>
@endpush
