@extends('layouts.admin.app')
@section('title', __('payroll.title'))

@section('content')
{{-- فلترة المسيرات --}}
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.payrolls.index') }}" method="GET" class="row g-3 align-items-end">

            <div class="col-md-3">
                <label class="form-label fw-bold mb-1">
                    {{ __('payroll.fields.branch') }}
                    <x-inputs.help :text="__('payroll.hints.branch_filter')" />
                </label>
                <select name="branch_id" class="form-select select2">
                    <option value="">{{ __('payroll.filters.all_branches') }}</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (int) request('branch_id') === (int) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold mb-1">{{ __('payroll.fields.status') }}</label>
                <select name="status" class="form-select select2">
                    <option value="">{{ __('payroll.filters.all_statuses') }}</option>
                    @foreach (['draft', 'approved', 'rejected', 'paid'] as $statusKey)
                        <option value="{{ $statusKey }}" {{ request('status') === $statusKey ? 'selected' : '' }}>
                            {{ __('payroll.enums.' . $statusKey) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold mb-1">
                    {{ __('payroll.filters.period_range') }}
                    <x-inputs.help :text="__('payroll.hints.period_range')" />
                </label>
                <div class="input-group">
                    <input type="date" name="date_from" class="form-control px-1" value="{{ request('date_from') }}">
                    <input type="date" name="date_to" class="form-control px-1" value="{{ request('date_to') }}">
                </div>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1" style="height: 38px;">
                    <i class="fa-solid fa-filter me-1"></i> {{ __('global.filter') }}
                </button>
                <a href="{{ route('admin.payrolls.index') }}" class="btn btn-label-secondary"
                   style="height: 38px;" title="{{ __('global.reset_filter') }}">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>

        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>{{ __('payroll.stats.total_paid') }}</span>
                        <div class="d-flex align-items-end mt-2">
                            <h4 class="mb-0 me-2" dir="ltr">{{ number_format($stats['total_paid'], 2) }}</h4>
                        </div>
                        <small class="text-muted">{{ __('payroll.stats.total_paid_hint') }}</small>
                    </div>
                    <span class="badge bg-label-success rounded p-2">
                        <i class="fa-solid fa-money-bill-wave fs-4"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>{{ __('payroll.stats.pending_draft') }}</span>
                        <div class="d-flex align-items-end mt-2">
                            <h4 class="mb-0 me-2">{{ $stats['draft_count'] }}</h4>
                        </div>
                        <small class="text-muted">{{ __('payroll.stats.pending_draft_hint') }}</small>
                    </div>
                    <span class="badge bg-label-warning rounded p-2">
                        <i class="fa-solid fa-clock-rotate-left fs-4"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>{{ __('payroll.stats.approved_count') }}</span>
                        <div class="d-flex align-items-end mt-2">
                            <h4 class="mb-0 me-2">{{ $stats['approved_count'] }}</h4>
                        </div>
                        <small class="text-muted">{{ __('payroll.stats.approved_count_hint') }}</small>
                    </div>
                    <span class="badge bg-label-info rounded p-2">
                        <i class="fa-solid fa-check-double fs-4"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-money-check-dollar text-primary me-2"></i> {{ __('payroll.title') }}
        </h5>
        @if(admin_can('payroll_create'))
            <a href="{{ route('admin.payrolls.create') }}" class="btn btn-primary waves-effect waves-light">
                <i class="fa-solid fa-plus me-1"></i> {{ __('payroll.actions.generate') }}
            </a>
        @endif
    </div>

    <div class="card-body pt-4">
        @php
            $payrollStatusOptions = collect(['draft', 'approved', 'rejected', 'paid'])
                ->mapWithKeys(fn($st) => [$st => __('payroll.enums.' . $st)])->all();
            $columns = [
                ['data' => 'id', 'name' => 'id'],
                ['data' => 'title_link', 'name' => 'title'],
                ['data' => 'period', 'name' => 'month'],
                ['data' => 'branch_name', 'name' => 'branch.name'],
                ['data' => 'total_basic', 'name' => 'total_basic_salary'],
                ['data' => 'total_net', 'name' => 'total_net_salary'],
                ['data' => 'status_badge', 'name' => 'status', 'orderable' => false, 'searchType' => 'select', 'searchOptions' => $payrollStatusOptions],
                ['data' => 'creator_name', 'name' => 'creator.name'],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ];
        @endphp

        <x-dataTable.table
            :columns="[
                __('global.id'),
                __('payroll.fields.title'),
                __('payroll.fields.month'),
                __('payroll.fields.branch'),
                __('payroll.fields.total_basic_salary'),
                __('payroll.fields.total_net_salary'),
                __('payroll.fields.status'),
                __('payroll.fields.created_by'),
                __('global.actions'),
            ]"
            :fields="$columns"
            :url="route('admin.payrolls.index', request()->query())"
        />
    </div>
</div>
@endsection
