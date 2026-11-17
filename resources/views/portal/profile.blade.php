@extends('layouts.portal.app')
@section('title', __('portal.nav.profile'))

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card text-center">
            <div class="card-body">
                <img src="{{ $employee->avatar_url }}" alt="" class="rounded-circle mb-3" width="96" height="96" style="object-fit: cover">
                <h5 class="fw-bold mb-1">{{ $employee->name }}</h5>
                <p class="text-muted mb-2">{{ $employee->jobTitle?->name }}</p>
                <span class="badge bg-label-primary" dir="ltr">{{ $employee->employee_code }}</span>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0 fw-bold">{{ __('portal.profile.job') }}</h6></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">{{ __('portal.profile.branch') }}</dt><dd class="col-sm-8">{{ $employee->branch?->name }}</dd>
                    <dt class="col-sm-4">{{ __('portal.profile.manager') }}</dt><dd class="col-sm-8">{{ $employee->manager?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">{{ __('portal.profile.hire_date') }}</dt><dd class="col-sm-8" dir="ltr">{{ $employee->hire_date?->format('Y-m-d') }}</dd>
                    <dt class="col-sm-4">{{ __('portal.profile.phone') }}</dt><dd class="col-sm-8" dir="ltr">{{ $employee->phone }}</dd>
                    <dt class="col-sm-4">{{ __('portal.profile.email') }}</dt><dd class="col-sm-8" dir="ltr">{{ $employee->personal_email ?: '—' }}</dd>
                    <dt class="col-sm-4">{{ __('portal.profile.permission_limit') }}</dt><dd class="col-sm-8">{{ (float) $employee->monthly_permission_hours_limit + 0 }} {{ __('portal.home.hours') }}</dd>
                </dl>
            </div>
        </div>

        @if ($employee->documents->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header"><h6 class="mb-0 fw-bold">{{ __('portal.profile.documents') }}</h6></div>
                <ul class="list-group list-group-flush">
                    @foreach ($employee->documents as $doc)
                        @php($days = $doc->expiry_date ? (int) now()->startOfDay()->diffInDays($doc->expiry_date, false) : null)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ $doc->title ?: __('document.types.' . $doc->document_type) }}</span>
                            @if ($days !== null)
                                <span class="badge {{ $days < 0 ? 'bg-label-danger' : ($days <= 30 ? 'bg-label-warning' : 'bg-label-success') }}" dir="ltr">
                                    {{ $doc->expiry_date->format('Y-m-d') }}
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="alert alert-secondary small mb-0">
            <i class="fa-solid fa-circle-info me-1"></i>{{ __('portal.profile.edit_hint') }}
        </div>
    </div>
</div>
@endsection
