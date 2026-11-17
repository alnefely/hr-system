@extends('layouts.portal.app')
@section('title', __('portal.nav.leaves'))

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        @if ($balance)
            <div class="card mb-4">
                <div class="card-header"><h6 class="mb-0 fw-bold">{{ __('portal.leaves.balance_title', ['year' => $balance->year]) }}</h6></div>
                <ul class="list-group list-group-flush">
                    @foreach (['annual', 'casual', 'sick'] as $type)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('leave.type_enums.' . $type) }}</span>
                            <span>
                                <strong>{{ (float) $balance->{'remaining_' . $type} + 0 }}</strong>
                                <small class="text-muted">/ {{ (float) $balance->{$type . '_balance'} + 0 }}</small>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card" id="new">
            <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-solid fa-plus me-2 text-primary"></i>{{ __('portal.leaves.new') }}</h6></div>
            <div class="card-body">
                <form action="{{ route('portal.leaves.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="leave_type">{{ __('portal.leaves.type') }}</label>
                        <select class="form-select" id="leave_type" name="leave_type" required>
                            @foreach (\App\Enums\LeaveType::options() as $value => $label)
                                <option value="{{ $value }}" @selected(old('leave_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" for="start_date">{{ __('portal.leaves.from') }}</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="end_date">{{ __('portal.leaves.to') }}</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="days_count">{{ __('portal.leaves.days') }}</label>
                        <input type="number" step="0.5" min="0.5" class="form-control" id="days_count" name="days_count" value="{{ old('days_count') }}" required>
                        <div class="form-text">{{ __('portal.leaves.days_hint') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="reason">{{ __('portal.fields.reason') }}</label>
                        <textarea class="form-control" id="reason" name="reason" rows="2" maxlength="1000">{{ old('reason') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">{{ __('portal.requests.submit') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0 fw-bold">{{ __('portal.leaves.title') }}</h6></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('portal.leaves.type') }}</th>
                            <th>{{ __('portal.leaves.period') }}</th>
                            <th>{{ __('portal.leaves.days') }}</th>
                            <th>{{ __('portal.fields.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leaves as $l)
                            <tr>
                                <td>{!! \App\Enums\LeaveType::fromValue($l->leave_type)?->badgeHtml() ?? e($l->leave_type) !!}</td>
                                <td dir="ltr" class="text-nowrap">{{ $l->start_date?->format('Y-m-d') }} → {{ $l->end_date?->format('Y-m-d') }}</td>
                                <td>{{ $l->days_count + 0 }}</td>
                                <td>
                                    {!! \App\Enums\LeaveStatus::badgeFor($l->status) !!}
                                    @if ($l->rejection_reason)
                                        <div class="small text-danger mt-1">{{ $l->rejection_reason }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @include('portal.partials.cancel-button', [
                                        'pending' => $l->status === \App\Enums\LeaveStatus::Pending->value,
                                        'action'  => route('portal.leaves.destroy', $l->id),
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">{{ __('portal.requests.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($leaves->hasPages())
                <div class="card-footer">{{ $leaves->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
