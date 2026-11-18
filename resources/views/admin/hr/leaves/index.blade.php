@extends('layouts.admin.app')
@section('title', __('leave.title'))

@section('content')
{{-- فلترة بالموظف — نفس نمط شاشة الأذونات --}}
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.leaves.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-bold mb-1">
                    {{ __('leave.filters.employee') }}
                    <x-inputs.help :text="__('leave.hints.employee_filter')" />
                </label>
                <select name="employee_id" class="form-select select2" onchange="this.form.submit()">
                    <option value="">{{ __('leave.filters.all_employees') }}</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                            {{ $emp->name }} ({{ $emp->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold mb-1">{{ __('leave.fields.leave_type') }}</label>
                <select name="leave_type" class="form-select select2" onchange="this.form.submit()">
                    <option value="">{{ __('leave.filters.all_types') }}</option>
                    @foreach(\App\Enums\LeaveType::cases() as $type)
                        <option value="{{ $type->value }}" {{ request('leave_type') === $type->value ? 'selected' : '' }}>
                            {{ $type->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold mb-1">{{ __('leave.fields.status') }}</label>
                <select name="status" class="form-select select2" onchange="this.form.submit()">
                    <option value="">{{ __('leave.filters.all_statuses') }}</option>
                    @foreach(\App\Enums\LeaveStatus::cases() as $statusCase)
                        <option value="{{ $statusCase->value }}" {{ request('status') === $statusCase->value ? 'selected' : '' }}>
                            {{ $statusCase->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <a href="{{ route('admin.leaves.index') }}"
                   class="btn btn-label-secondary w-100 d-flex align-items-center justify-content-center"
                   style="height: 38px;">
                    <i class="fa-solid fa-rotate-left me-1"></i> {{ __('global.reset_filter') }}
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-calendar-days text-primary me-2"></i> {{ __('leave.title') }}
        </h5>
        <div class="d-flex gap-2">
            @if(admin_can('leave_trash'))
                <a href="{{ route('admin.leaves.trash') }}" class="btn btn-label-danger waves-effect">
                    <i class="fa-solid fa-trash me-1"></i> {{ __('leave.trash') }}
                    @if(isset($trashCount) && $trashCount > 0)
                        <span class="badge bg-danger ms-1">{{ $trashCount }}</span>
                    @endif
                </a>
            @endif

            @if(admin_can('leave_balance_manage') || admin_can('leave_show'))
                <a href="{{ route('admin.leaves.balances') }}" class="btn btn-outline-primary waves-effect">
                    <i class="fa-solid fa-scale-balanced me-1"></i> {{ __('leave.balances_title') }}
                </a>
            @endif
            @if(admin_can('leave_create'))
                <button type="button" class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#createLeaveModal">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('leave.actions.add_new') }}
                </button>
            @endif
        </div>
    </div>

    <div class="card-body pt-4">
        @php
            // قوائم البحث جوه الجدول — النوع والحالة قيم محددة
            // فالكتابة الحرة فيها مالهاش معنى.
            $typeOptions = collect(\App\Enums\LeaveType::cases())
                ->mapWithKeys(fn($case) => [$case->value => $case->label()])
                ->all();

            $statusOptions = collect(\App\Enums\LeaveStatus::cases())
                ->mapWithKeys(fn($case) => [$case->value => $case->label()])
                ->all();

            $columns = [
                ['data' => 'id', 'name' => 'id'],
                ['data' => 'employee_name', 'name' => 'employee.name'],
                ['data' => 'leave_type_badge', 'name' => 'leave_type', 'orderable' => false, 'searchType' => 'select', 'searchOptions' => $typeOptions],
                ['data' => 'formatted_start', 'name' => 'start_date', 'searchType' => 'date'],
                ['data' => 'formatted_end', 'name' => 'end_date', 'searchType' => 'date'],
                ['data' => 'days_count', 'name' => 'days_count'],
                ['data' => 'status_badge', 'name' => 'status', 'orderable' => false, 'searchType' => 'select', 'searchOptions' => $statusOptions],
                ['data' => 'approver_name', 'name' => 'approver.name'],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ];
        @endphp

        <x-dataTable.table
            :columns="[
                __('global.id'),
                __('leave.fields.employee'),
                __('leave.fields.leave_type'),
                __('leave.fields.start_date'),
                __('leave.fields.end_date'),
                __('leave.fields.days_count'),
                __('leave.fields.status'),
                __('leave.fields.approved_by'),
                __('global.actions'),
            ]"
            :fields="$columns"
            :url="route('admin.leaves.index', request()->query())"
        />
    </div>
</div>

{{-- Modal Create Leave --}}
@if(admin_can('leave_create'))
    <div class="modal fade" id="createLeaveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.leaves.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus text-primary me-2"></i> {{ __('leave.create_title') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">{{ __('leave.fields.employee') }} <span class="text-danger">*</span></label>
                                <select name="employee_id" class="form-select select2" required>
                                    <option value="">{{ __('global.choose') }}...</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->employee_code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('leave.fields.leave_type') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('leave.hints.leave_type')" /></label>
                                <select name="leave_type" class="form-select select2" required>
                                    <option value="annual">{{ __('leave.type_enums.annual') }}</option>
                                    <option value="casual">{{ __('leave.type_enums.casual') }}</option>
                                    <option value="sick">{{ __('leave.type_enums.sick') }}</option>
                                    <option value="unpaid">{{ __('leave.type_enums.unpaid') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('leave.fields.start_date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="leave_start_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('leave.fields.end_date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" id="leave_end_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('leave.fields.days_count') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('leave.hints.days_count')" /></label>
                                <input type="number" step="0.5" min="0.5" name="days_count" id="leave_days_count" class="form-control" value="1" required dir="ltr">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('leave.fields.reason') }}</label>
                                <textarea name="reason" class="form-control" rows="2" placeholder="سبب طلب الإجازة..."></textarea>
                            </div>
                            <div class="col-md-12">
                                <x-inputs.lfm name="attachment" label="{{ __('leave.fields.attachment') }}" :tooltip="__('leave.hints.attachment')" type="file" :showClear="true" />
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

{{-- Modal Reject Leave --}}
@if(admin_can('leave_reject'))
    <div class="modal fade" id="rejectLeaveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="rejectLeaveForm" action="" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-xmark me-2"></i> {{ __('leave.actions.reject') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="mb-3">
                            <label class="form-label">سبب الرفض <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="اكتب سبب رفض الإجازة بالتفصيل..."></textarea>
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
    // عدد الأيام بيتملّى تلقائياً من التاريخين (شاملين الاتنين) لما أي تاريخ يتغيّر،
    // ويفضل قابل للتعديل: الموارد البشرية ممكن تستثني الجمعة أو الإجازات الرسمية أو تكتب نص يوم.
    (function () {
        const start = document.getElementById('leave_start_date');
        const end = document.getElementById('leave_end_date');
        const days = document.getElementById('leave_days_count');
        if (!start || !end || !days) return;

        function fill() {
            if (!start.value || !end.value) return;
            const n = Math.round((new Date(end.value) - new Date(start.value)) / 86400000) + 1;
            if (n >= 1) days.value = n;
        }

        start.addEventListener('change', function () {
            end.min = start.value;
            if (!end.value || end.value < start.value) end.value = start.value;
            fill();
        });
        end.addEventListener('change', fill);
        end.min = start.value;
    })();
</script>
@endpush

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.reject-btn', function() {
        let leaveId = $(this).data('id');
        let baseUrl = "{{ url('admin/leaves') }}";
        $('#rejectLeaveForm').attr('action', baseUrl + '/' + leaveId + '/reject');
        $('#rejectLeaveModal').modal('show');
    });
});
</script>
@endpush
