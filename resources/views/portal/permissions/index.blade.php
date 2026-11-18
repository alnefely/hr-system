@extends('layouts.portal.app')
@section('title', __('portal.nav.permissions'))

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card" id="new">
            <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-solid fa-plus me-2 text-primary"></i>{{ __('portal.permissions.new') }}</h6></div>
            <div class="card-body">
                <div class="alert alert-info small">
                    {{ __('portal.permissions.limit_info', ['used' => $approvedThisMonth + 0, 'limit' => $limit + 0]) }}
                </div>

                <form action="{{ route('portal.permissions.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="permission_date">{{ __('portal.permissions.date') }}</label>
                        <input type="date" class="form-control" id="permission_date" name="permission_date" value="{{ old('permission_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" for="start_time">{{ __('portal.permissions.from') }}</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" value="{{ old('start_time') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="end_time">{{ __('portal.permissions.to') }}</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" value="{{ old('end_time') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="reason">{{ __('portal.fields.reason') }}</label>
                        <textarea class="form-control" id="reason" name="reason" rows="2" maxlength="1000">{{ old('reason') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">{{ __('portal.requests.submit') }}</button>
                    <div class="form-text">{{ __('portal.permissions.hours_hint') }}</div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0 fw-bold">{{ __('portal.permissions.title') }}</h6></div>
            <div class="card-body border-bottom">
                @include('portal.partials.date-filter', ['period' => $period])
            </div>
            <div class="card-body">
                <x-dataTable.table
                    id="portal-permissions"
                    :columns="[__('portal.permissions.date'), __('portal.permissions.time'), __('portal.permissions.hours'), __('portal.fields.status'), '']"
                    :fields="[
                        'permission_date',
                        ['data' => 'time', 'orderable' => false, 'searchable' => false],
                        'hours_count',
                        ['data' => 'status_badge', 'name' => 'status', 'searchable' => false],
                        ['data' => 'action', 'orderable' => false, 'searchable' => false, 'className' => 'text-end'],
                    ]"
                    :order="[[0, 'desc']]"
                    :exportable="false"
                    :searchable="false" />
            </div>
        </div>
    </div>
</div>
@endsection
