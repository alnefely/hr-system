@extends('layouts.admin.app')
@section('title', __('vendor_subscription.title'))

@section('content')
{{-- Analytics Widgets (No bg-light) --}}
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card h-100 border-start border-primary border-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="avatar avatar-md rounded bg-label-primary d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-file-contract fs-4"></i>
                    </div>
                    <span class="badge bg-label-primary" data-bs-toggle="tooltip" title="عدد الاشتراكات والعقود المفعلة حالياً">{{ $activeSubscriptionsCount }} اشتراك نشط</span>
                </div>
                <h5 class="mb-1 text-primary fw-bold">{{ __('vendor_subscription.stats.active_total') }}</h5>
                <p class="mb-2 text-muted small fw-semibold" data-bs-toggle="tooltip" title="{{ __('vendor_subscription.stats.active_total_hint') }}">
                    تكلفة الاشتراكات النشطة مفصلة بكل عملة:
                </p>
                <div class="d-flex flex-wrap gap-2 pt-1">
            @if(admin_can('vendor_subscription_trash'))
                <a href="{{ route('admin.vendor-subscriptions.trash') }}" class="btn btn-label-danger waves-effect">
                    <i class="fa-solid fa-trash me-1"></i> {{ __('vendor_subscription.trash') }}
                    @if(isset($trashCount) && $trashCount > 0)
                        <span class="badge bg-danger ms-1">{{ $trashCount }}</span>
                    @endif
                </a>
            @endif

                    @forelse($activeCostsByCurrency as $curr => $totalCost)
                        <span class="badge bg-label-secondary" data-bs-toggle="tooltip" title="إجمالي تكلفة الاشتراكات النشطة بعملة {{ $curr }}">
                            {{ $curr }}: <strong class="text-dark" dir="ltr">{{ number_format($totalCost, 2) }}</strong>
                        </span>
                    @empty
                        <span class="text-muted small">لا توجد اشتراكات نشطة مسجلة</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-4">
        <div class="card h-100 border-start border-warning border-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="avatar avatar-md rounded bg-label-warning d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-clock-rotate-left fs-4"></i>
                    </div>
                    <span class="badge bg-label-warning" data-bs-toggle="tooltip" title="خلال الـ 30 يوماً القادمة">30 يوماً</span>
                </div>
                <h4 class="mb-1 text-warning fw-bold"><span dir="ltr">{{ $upcomingRenewalsCount }}</span></h4>
                <p class="mb-0 text-muted small fw-semibold" data-bs-toggle="tooltip" title="اشتراكات نشطة يحين موعد سدادها وتجديدها في غضون شهر">
                    {{ __('vendor_subscription.stats.upcoming_renewals') }}
                </p>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-4">
        <div class="card h-100 border-start border-danger border-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="avatar avatar-md rounded bg-label-danger d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-triangle-exclamation fs-4"></i>
                    </div>
                    <span class="badge bg-label-danger" data-bs-toggle="tooltip" title="اشتراكات تجاوزت موعد الاستحقاق أو انتهت صلاحيتها">تنبيه فوري</span>
                </div>
                <h4 class="mb-1 text-danger fw-bold"><span dir="ltr">{{ $expiredCount }}</span></h4>
                <p class="mb-0 text-muted small fw-semibold" data-bs-toggle="tooltip" title="اشتراكات منتهية أو متأخرة عن موعد السداد المحدد">
                    {{ __('vendor_subscription.stats.expired_subscriptions') }}
                </p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-cloud text-primary me-2"></i> {{ __('vendor_subscription.title') }}
        </h5>
        @if(admin_can('vendor_subscription_create'))
            <a href="{{ route('admin.vendor-subscriptions.create') }}" class="btn btn-primary waves-effect waves-light">
                <i class="fa-solid fa-plus me-1"></i> {{ __('vendor_subscription.actions.add_new') }}
            </a>
        @endif
    </div>

    {{-- Filter Bar (No bg-light) --}}
    <div class="card-body py-3 border-bottom bg-transparent">
        <form action="{{ route('admin.vendor-subscriptions.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label text-muted small mb-1">{{ __('vendor_subscription.fields.vendor') }}</label>
                <select name="vendor_id" class="form-select select2">
                    <option value="">{{ __('global.all') }}</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->id }}" {{ request('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small mb-1">{{ __('vendor_subscription.fields.branch') }}</label>
                <select name="branch_id" class="form-select select2">
                    <option value="">{{ __('vendor_subscription.fields.all_branches') }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small mb-1">{{ __('vendor_subscription.fields.status') }}</label>
                <select name="status" class="form-select select2">
                    <option value="">{{ __('global.all') }}</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('vendor_subscription.statuses.active') }}</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('vendor_subscription.statuses.pending') }}</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>{{ __('vendor_subscription.statuses.expired') }}</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>{{ __('vendor_subscription.statuses.cancelled') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small mb-1">{{ __('vendor_subscription.fields.type') }}</label>
                <select name="type" class="form-select select2">
                    <option value="">{{ __('global.all') }}</option>
                    <option value="subscription" {{ request('type') == 'subscription' ? 'selected' : '' }}>{{ __('vendor_subscription.type_enums.subscription') }}</option>
                    <option value="contract" {{ request('type') == 'contract' ? 'selected' : '' }}>{{ __('vendor_subscription.type_enums.contract') }}</option>
                    <option value="one_time" {{ request('type') == 'one_time' ? 'selected' : '' }}>{{ __('vendor_subscription.type_enums.one_time') }}</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 waves-effect waves-light">
                    <i class="fa-solid fa-filter me-1"></i> {{ __('global.filter') }}
                </button>
                <a href="{{ route('admin.vendor-subscriptions.index') }}" class="btn btn-label-secondary waves-effect" data-bs-toggle="tooltip" title="{{ __('global.reset_filter') }}">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="card-body pt-4">
        @php
            $columns = [
                __('global.id'),
                __('vendor_subscription.fields.title'),
                __('vendor_subscription.fields.vendor'),
                __('vendor_subscription.fields.branch'),
                __('vendor_subscription.fields.cost'),
                __('vendor_subscription.fields.billing_cycle'),
                __('vendor_subscription.fields.next_billing_date'),
                __('vendor_subscription.fields.status'),
                __('global.actions'),
            ];

            $fields = [
                'id',
                'title',
                ['data' => 'vendor_name', 'name' => 'vendor.name'],
                ['data' => 'branch_name', 'name' => 'branch.name'],
                ['data' => 'formatted_cost', 'name' => 'cost'],
                ['data' => 'cycle_badge', 'name' => 'billing_cycle'],
                ['data' => 'formatted_next_billing', 'name' => 'next_billing_date', 'searchType' => 'date'],
                ['data' => 'status_badge', 'name' => 'status', 'searchType' => 'select', 'searchOptions' => __('vendor_subscription.statuses')],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ];
        @endphp

        <x-dataTable.table
            :columns="$columns"
            :fields="$fields"
            :url="route('admin.vendor-subscriptions.index', request()->query())"
        />
    </div>
</div>
@endsection
