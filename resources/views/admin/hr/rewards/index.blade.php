@extends('layouts.admin.app')
@section('title', __('reward.title'))

@section('content')
{{-- Filter & Employee Stats Card --}}
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.rewards.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label for="admin_hr_rewards_f_employee_id" class="form-label fw-bold mb-1">فلترة حسب موظف معين لمعاينة سجل المكافآت والبدلات</label>
                <select id="admin_hr_rewards_f_employee_id" name="employee_id" class="form-select select2" onchange="this.form.submit()">
                    <option value="">-- جميع الموظفين --</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ $selectedEmployeeId == $emp->id ? 'selected' : '' }}>
                            {{ $emp->name }} ({{ $emp->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <a href="{{ route('admin.rewards.index') }}" class="btn btn-label-secondary w-100 d-flex align-items-center justify-content-center h-38">
                    <i class="fa-solid fa-rotate-left me-1"></i> عرض كافة الموظفين
                </a>
            </div>
        </form>

        @if($selectedEmployee && $rewardStats)
            <div class="row g-3 mt-2 pt-3 border-top">
                <div class="col-md-6">
                    <div class="p-3 rounded border text-center">
                        <small class="text-muted d-block mb-1">إجمالي عدد المكافآت والبدلات</small>
                        <h4 class="mb-0 fw-bold text-success">{{ $rewardStats['total_rewards'] }} <small class="fs-6">مكافأة / بدل</small></h4>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded border text-center">
                        <small class="text-muted d-block mb-1">إجمالي المبالغ المستحقة</small>
                        <h4 class="mb-0 fw-bold text-success"><span dir="ltr">+{{ number_format($rewardStats['total_amount'], 2) }}</span> <small class="fs-6">{{ $rewardStats['currency'] }}</small></h4>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-award text-success me-2"></i> {{ __('reward.title') }}
            @if($selectedEmployee)
                <span class="text-muted fs-6">({{ $selectedEmployee->name }})</span>
            @endif
        </h5>
        <div class="d-flex gap-2">
            @if (admin_can('reward_trash'))
                <a href="{{ route('admin.rewards.trash') }}" class="btn btn-label-danger waves-effect">
                    <i class="fa-solid fa-trash me-1"></i> {{ __('global.trash') }}
                    @if(isset($trashCount) && $trashCount > 0)
                        <span class="badge bg-danger rounded-pill ms-1">{{ $trashCount }}</span>
                    @endif
                </a>
            @endif
            <a href="{{ route('admin.rewards.index', array_merge(request()->all(), ['export' => 'excel'])) }}" class="btn btn-label-success waves-effect">
                <i class="fa-solid fa-file-excel me-1"></i> تصدير Excel
            </a>
            @if(admin_can('reward_create'))
                <button type="button" class="btn btn-success waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#createRewardModal">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('reward.add_new') }}
                </button>
            @endif
        </div>
    </div>

    <div class="card-body pt-4">
        @php
            $columns = [
                ['data' => 'id', 'name' => 'id'],
                ['data' => 'employee_name', 'name' => 'employee.name'],
                ['data' => 'type_badge', 'name' => 'reward_type', 'orderable' => false, 'searchType' => 'select', 'searchOptions' => __('reward.type_enums')],
                ['data' => 'title', 'name' => 'title'],
                ['data' => 'formatted_amount', 'name' => 'amount'],
                ['data' => 'formatted_date', 'name' => 'reward_date', 'searchType' => 'date'],
                ['data' => 'status_badge', 'name' => 'status', 'orderable' => false, 'searchType' => 'select', 'searchOptions' => __('reward.status_enums')],
                ['data' => 'creator_name', 'name' => 'creator.name'],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ];
        @endphp

        <x-dataTable.table
            :columns="[
                __('global.id'),
                __('reward.fields.employee'),
                __('reward.fields.reward_type'),
                __('reward.fields.title'),
                __('reward.fields.amount'),
                __('reward.fields.reward_date'),
                __('reward.fields.status'),
                __('reward.fields.created_by'),
                __('global.actions'),
            ]"
            :fields="$columns"
            :url="route('admin.rewards.index', request()->query())"
        />
    </div>
</div>

{{-- Modal Create Reward --}}
@if(admin_can('reward_create'))
    <div class="modal fade" id="createRewardModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.rewards.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-success"><i class="fa-solid fa-award me-2"></i> {{ __('reward.create_title') }} <x-inputs.tip :text="__('reward.hints.approval')" /></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">{{ __('reward.fields.employee') }} <span class="text-danger">*</span></label>
                                <select name="employee_id" class="form-select select2" required>
                                    <option value="">{{ __('global.choose') }}...</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}" {{ $selectedEmployeeId == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->name }} ({{ $emp->employee_code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('reward.fields.reward_type') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('reward.hints.reward_type')" /></label>
                                <select name="reward_type" class="form-select select2" required>
                                    <option value="bonus">{{ __('reward.type_enums.bonus') }}</option>
                                    <option value="incentive">{{ __('reward.type_enums.incentive') }}</option>
                                    <option value="allowance">{{ __('reward.type_enums.allowance') }}</option>
                                    <option value="appreciation">{{ __('reward.type_enums.appreciation') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('reward.fields.amount') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('reward.hints.amount')" /></label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="0.00" required dir="ltr" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('reward.fields.title') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('reward.hints.title')" /></label>
                                <input type="text" name="title" class="form-control" placeholder="مثال: مكافأة إتمام مشروع الربع الأول / بدل سفر استثنائي" required />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('reward.fields.reward_date') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('reward.hints.reward_date')" /></label>
                                <input type="date" name="reward_date" class="form-control" value="{{ date('Y-m-d') }}" required />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('reward.fields.reason') }}</label>
                                <textarea name="reason" class="form-control" rows="2" placeholder="السبب ومناسبة صرف المكافأة..."></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('reward.fields.notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="ملاحظات إضافية اختيارية..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary waves-effect" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        <button type="submit" class="btn btn-success waves-effect waves-light"><i class="fa-solid fa-check me-1"></i> {{ __('global.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- نافذة الرفض: السبب إجباري عشان القرار يفضل موثّق --}}
<div class="modal fade" id="rejectRewardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="rejectRewardForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-circle-xmark text-danger me-2"></i>
                        {{ __('reward.actions.reject') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning small">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        {{ __('reward.messages.reject_hint') }}
                    </div>

                    <label class="form-label" for="reject_reason_reward">
                        {{ __('global.reason') }} <span class="text-danger">*</span>
                    </label>
                    <textarea name="rejection_reason"
                              id="reject_reason_reward"
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
document.addEventListener('DOMContentLoaded', function () {
    // زرار الرفض بيتولد جوّه DataTables، فبنستخدم delegation
    $(document).on('click', '.reject-btn', function () {
        var id = $(this).data('id');
        $('#rejectRewardForm').attr('action', "{{ url('admin/rewards') }}" + '/' + id + '/reject');
        $('#rejectRewardModal').modal('show');
    });
});
</script>
@endpush
