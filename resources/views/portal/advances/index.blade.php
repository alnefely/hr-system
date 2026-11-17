@extends('layouts.portal.app')
@section('title', __('portal.nav.advances'))

@section('content')
@php($currency = $employee->branch?->currency ?? '')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card" id="new">
            <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-solid fa-plus me-2 text-primary"></i>{{ __('portal.advances.new') }}</h6></div>
            <div class="card-body">
                <form action="{{ route('portal.advances.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="amount">{{ __('portal.advances.amount') }} <small class="text-muted">({{ $currency }})</small></label>
                        <input type="number" step="0.01" min="1" class="form-control" id="amount" name="amount" value="{{ old('amount') }}" required dir="ltr">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" for="installments_count">{{ __('portal.advances.installments') }}</label>
                            <input type="number" min="1" max="36" class="form-control" id="installments_count" name="installments_count" value="{{ old('installments_count', 1) }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="start_date">{{ __('portal.advances.start') }}</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="{{ old('start_date', now()->startOfMonth()->addMonth()->toDateString()) }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="reason">{{ __('portal.fields.reason') }}</label>
                        <textarea class="form-control" id="reason" name="reason" rows="2" maxlength="1000" required>{{ old('reason') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">{{ __('portal.requests.submit') }}</button>
                    <div class="form-text">{{ __('portal.advances.hint') }}</div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0 fw-bold">{{ __('portal.advances.title') }}</h6></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('portal.advances.date') }}</th>
                            <th>{{ __('portal.advances.amount') }}</th>
                            <th>{{ __('portal.advances.installment') }}</th>
                            <th>{{ __('portal.advances.remaining') }}</th>
                            <th>{{ __('portal.fields.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($advances as $a)
                            <tr>
                                <td dir="ltr" class="text-nowrap">{{ $a->advance_date?->format('Y-m-d') }}</td>
                                <td dir="ltr" class="text-nowrap">{{ number_format((float) $a->amount, 2) }}</td>
                                <td dir="ltr" class="text-nowrap">{{ number_format((float) $a->monthly_installment, 2) }} <small class="text-muted">&times; {{ $a->installments_count }}</small></td>
                                <td dir="ltr" class="text-nowrap fw-bold {{ (float) $a->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) $a->remaining_amount, 2) }}</td>
                                <td>{!! \App\Enums\AdvanceStatus::badgeFor($a->status, 'advance.status_enums') !!}</td>
                                <td class="text-end">
                                    @include('portal.partials.cancel-button', [
                                        'pending' => $a->status === \App\Enums\AdvanceStatus::Pending->value,
                                        'action'  => route('portal.advances.destroy', $a->id),
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">{{ __('portal.requests.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($advances->hasPages())
                <div class="card-footer">{{ $advances->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
