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
                    <dt class="col-sm-4">{{ __('portal.profile.hire_date') }}</dt><dd class="col-sm-8"><span dir="ltr">{{ $employee->hire_date?->format('Y-m-d') }}</span></dd>
                    <dt class="col-sm-4">{{ __('portal.profile.phone') }}</dt><dd class="col-sm-8"><span dir="ltr">{{ $employee->phone }}</span></dd>
                    <dt class="col-sm-4">{{ __('portal.profile.email') }}</dt><dd class="col-sm-8"><span dir="ltr">{{ $employee->personal_email ?: '—' }}</span></dd>
                    <dt class="col-sm-4">{{ __('portal.profile.permission_limit') }}</dt><dd class="col-sm-8">{{ (float) $employee->monthly_permission_hours_limit + 0 }} {{ __('portal.home.hours') }}</dd>
                </dl>
            </div>
        </div>

        {{-- الراتب والمستندات بقى ليهم صفحات مستقلة في القائمة — هنا اختصار ليهم --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <a href="{{ route('portal.salary.index') }}" class="card h-100 text-decoration-none">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-initial rounded bg-label-success p-3"><i class="fa-solid fa-sack-dollar fa-lg"></i></span>
                        <div>
                            <h6 class="mb-0 fw-bold text-heading">{{ __('portal.nav.salary') }}</h6>
                            <small class="text-muted">{{ __('portal.salary.link_hint') }}</small>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-sm-6">
                <a href="{{ route('portal.documents.index') }}" class="card h-100 text-decoration-none">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-initial rounded bg-label-primary p-3"><i class="fa-solid fa-folder-open fa-lg"></i></span>
                        <div>
                            <h6 class="mb-0 fw-bold text-heading">{{ __('portal.nav.documents') }}</h6>
                            <small class="text-muted">{{ __('portal.documents.link_hint') }}</small>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="alert alert-secondary small mb-0">
            <i class="fa-solid fa-circle-info me-1"></i>{{ __('portal.profile.edit_hint') }}
        </div>
    </div>
</div>
@endsection
