@extends('layouts.admin.app')
@section('title', __('permission.title'))

@section('content')
{{-- Filter & Employee Stats Card --}}
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.permissions.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-bold mb-1">فلترة حسب موظف معين لمعاينة رصيد الساعات</label>
                <select name="employee_id" class="form-select select2" onchange="this.form.submit()">
                    <option value="">-- جميع الموظفين --</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ $selectedEmployeeId == $emp->id ? 'selected' : '' }}>
                            {{ $emp->name }} ({{ $emp->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold mb-1">الشهر</label>
                <select name="month" class="form-select select2" onchange="this.form.submit()">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>شهر {{ $m }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold mb-1">السنة</label>
                <select name="year" class="form-select select2" onchange="this.form.submit()">
                    @for($y = date('Y') + 1; $y >= date('Y') - 3; $y--)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.permissions.index') }}" class="btn btn-label-secondary w-100 d-flex align-items-center justify-content-center" style="height: 38px;">
                    <i class="fa-solid fa-rotate-left me-1"></i> إعادة ضبط
                </a>
            </div>
        </form>

        @if($selectedEmployee && $employeeStats)
            <div class="row g-3 mt-2 pt-3 border-top">
                <div class="col-md-3">
                    <div class="p-3 rounded border text-center">
                        <small class="text-muted d-block mb-1">المخصص الشهري المسموح</small>
                        <h4 class="mb-0 fw-bold text-primary">{{ $employeeStats['monthly_limit'] }} <small class="fs-6">ساعة</small></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded border text-center">
                        <small class="text-muted d-block mb-1">إجمالي الساعات المعتمدة</small>
                        <h4 class="mb-0 fw-bold text-info">{{ $employeeStats['approved_hours'] }} <small class="fs-6">ساعة</small></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded border text-center">
                        <small class="text-muted d-block mb-1">الساعات المتبقية</small>
                        <h4 class="mb-0 fw-bold text-success">{{ $employeeStats['remaining_hours'] }} <small class="fs-6">ساعة</small></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded border text-center {{ $employeeStats['is_exceeded'] ? 'border-danger' : 'border-success' }}">
                        <small class="text-muted d-block mb-1">حالة الرصيد</small>
                        @if($employeeStats['is_exceeded'])
                            <span class="badge bg-danger fs-6 py-1 px-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> تجاوز الحد بـ {{ $employeeStats['exceeded_hours'] }} س</span>
                        @else
                            <span class="badge bg-success fs-6 py-1 px-2"><i class="fa-solid fa-check me-1"></i> ضمن الحد المسموح به</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-clock text-primary me-2"></i> {{ __('permission.title') }}
            @if($selectedEmployee)
                <span class="text-muted fs-6">({{ $selectedEmployee->name }})</span>
            @endif
        </h5>
        <div class="d-flex gap-2">
            @if(admin_can('permission_trash'))
                <a href="{{ route('admin.permissions.trash') }}" class="btn btn-label-danger waves-effect">
                    <i class="fa-solid fa-trash me-1"></i> {{ __('permission.trash') }}
                    @if(isset($trashCount) && $trashCount > 0)
                        <span class="badge bg-danger ms-1">{{ $trashCount }}</span>
                    @endif
                </a>
            @endif
            @if(admin_can('permission_create'))
                <button type="button" class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('permission.actions.add_new') }}
                </button>
            @endif
        </div>
    </div>

    <div class="card-body pt-4">
        @php
            $columns = [
                ['data' => 'id', 'name' => 'id'],
                ['data' => 'employee_name', 'name' => 'employee.name'],
                ['data' => 'formatted_date', 'name' => 'permission_date', 'searchType' => 'date'],
                ['data' => 'time_range', 'name' => 'start_time', 'orderable' => false],
                ['data' => 'hours_count', 'name' => 'hours_count'],
                ['data' => 'status_badge', 'name' => 'status', 'orderable' => false, 'searchType' => 'select', 'searchOptions' => __('permission.status_enums')],
                ['data' => 'approver_name', 'name' => 'approver.name'],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ];
        @endphp

        <x-dataTable.table
            :columns="[
                __('global.id'),
                __('permission.fields.employee'),
                __('permission.fields.permission_date'),
                'توقيت الاستئذان',
                __('permission.fields.hours_count'),
                __('permission.fields.status'),
                __('permission.fields.approved_by'),
                __('global.actions'),
            ]"
            :fields="$columns"
            :url="route('admin.permissions.index', request()->query())"
        />
    </div>
</div>

{{-- Modal Create Permission --}}
@if(admin_can('permission_create'))
    <div class="modal fade" id="createPermissionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.permissions.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus text-primary me-2"></i> {{ __('permission.create_title') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">{{ __('permission.fields.employee') }} <span class="text-danger">*</span></label>
                                <select name="employee_id" class="form-select select2" required>
                                    <option value="">{{ __('global.choose') }}...</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->employee_code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('permission.fields.permission_date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="permission_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('permission.fields.start_time') }} <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('permission.fields.end_time') }} <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" class="form-control" required />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('permission.fields.hours_count') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.1" max="12" name="hours_count" class="form-control" value="1" required dir="ltr" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('permission.fields.reason') }}</label>
                                <textarea name="reason" class="form-control" rows="2" placeholder="سبب طلب الاستئذان..."></textarea>
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

{{-- Modal Reject Permission --}}
@if(admin_can('permission_reject'))
    <div class="modal fade" id="rejectPermissionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="rejectPermissionForm" action="" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-xmark me-2"></i> {{ __('permission.actions.reject') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="mb-3">
                            <label class="form-label">سبب الرفض <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="اكتب سبب رفض طلب الاستئذان..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary waves-effect" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        <button type="submit" class="btn btn-danger waves-effect waves-light"><i class="fa-solid fa-check me-1"></i> تأكيد الرفض</button>
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
    $(document).on('click', '.reject-btn', function() {
        let permId = $(this).data('id');
        let baseUrl = "{{ url('admin/permissions') }}";
        $('#rejectPermissionForm').attr('action', baseUrl + '/' + permId + '/reject');
        $('#rejectPermissionModal').modal('show');
    });

    // Auto-calculate hours count from start_time and end_time
    $(document).on('change input', '#createPermissionModal input[name="start_time"], #createPermissionModal input[name="end_time"]', function() {
        let start = $('#createPermissionModal input[name="start_time"]').val();
        let end = $('#createPermissionModal input[name="end_time"]').val();
        if (start && end) {
            let [sH, sM] = start.split(':').map(Number);
            let [eH, eM] = end.split(':').map(Number);
            let diffMinutes = (eH * 60 + eM) - (sH * 60 + sM);
            if (diffMinutes > 0) {
                let hours = parseFloat((diffMinutes / 60).toFixed(2));
                $('#createPermissionModal input[name="hours_count"]').val(hours);
            }
        }
    });
});
</script>
@endpush
