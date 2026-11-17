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
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('portal.permissions.date') }}</th>
                            <th>{{ __('portal.permissions.time') }}</th>
                            <th>{{ __('portal.permissions.hours') }}</th>
                            <th>{{ __('portal.fields.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($permissions as $p)
                            <tr>
                                <td dir="ltr" class="text-nowrap">{{ $p->permission_date?->format('Y-m-d') }}</td>
                                <td dir="ltr" class="text-nowrap">{{ $p->start_time }} - {{ $p->end_time }}</td>
                                <td>{{ $p->hours_count + 0 }}</td>
                                <td>
                                    {!! \App\Enums\ApprovalStatus::badgeFor($p->status, 'permission.status_enums') !!}
                                    @if ($p->rejection_reason)
                                        <div class="small text-danger mt-1">{{ $p->rejection_reason }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @include('portal.partials.cancel-button', [
                                        'pending' => $p->status === \App\Enums\ApprovalStatus::Pending->value,
                                        'action'  => route('portal.permissions.destroy', $p->id),
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">{{ __('portal.requests.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($permissions->hasPages())
                <div class="card-footer">{{ $permissions->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
