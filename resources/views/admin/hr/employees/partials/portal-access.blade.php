{{--
    دخول بوابة الموظف في ملفه: الحالة + التفعيل / إعادة التعيين / الإيقاف بسبب.
    الأزرار بتظهر لصاحب صلاحية employee_portal_manage بس (والسيرفر بيتحقق برضه).
--}}
@php
    $canManage = admin_can('employee_portal_manage');

    [$label, $badge] = match (true) {
        $employee->status === 'terminated'                    => [__('portal.admin.status_terminated'), 'bg-label-secondary'],
        $employee->portal_enabled && $employee->password      => [__('portal.admin.status_enabled'), 'bg-label-success'],
        (bool) $employee->portal_disabled_reason              => [__('portal.admin.status_suspended'), 'bg-label-danger'],
        default                                               => [__('portal.admin.status_not_activated'), 'bg-label-warning'],
    };

    $isActive = $employee->portal_enabled && $employee->password;
@endphp

<div class="col-12">
    <div class="border rounded p-3 bg-transparent">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h6 class="fw-bold text-primary mb-0">
                <i class="fa-solid fa-door-open me-1"></i> {{ __('portal.admin.title') }}
                <span class="badge {{ $badge }} ms-1">{{ $label }}</span>
            </h6>

            @if ($canManage && $employee->status !== 'terminated')
                <div class="d-flex flex-wrap gap-2">
                    @if ($isActive)
                        <button type="button" class="btn btn-sm btn-label-primary" data-bs-toggle="modal" data-bs-target="#portalResetModal">
                            <i class="fa-solid fa-key me-1"></i>{{ __('portal.admin.reset') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-label-danger" data-bs-toggle="modal" data-bs-target="#portalDisableModal">
                            <i class="fa-solid fa-ban me-1"></i>{{ __('portal.admin.disable') }}
                        </button>
                    @else
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#portalEnableModal">
                            <i class="fa-solid fa-unlock me-1"></i>{{ $employee->portal_disabled_reason ? __('portal.admin.reenable') : __('portal.admin.enable') }}
                        </button>
                    @endif
                </div>
            @endif
        </div>

        <div class="row g-3 small">
            <div class="col-md-4">
                <strong>{{ __('portal.admin.login_phone') }}:</strong>
                <div dir="ltr" class="text-start">{{ $employee->phone }}</div>
            </div>
            <div class="col-md-4">
                <strong>{{ __('portal.admin.last_login') }}:</strong>
                <div dir="ltr" class="text-start">{{ $employee->last_login_at?->format('Y-m-d h:i A') ?? __('portal.admin.never') }}</div>
            </div>
            <div class="col-md-4">
                <strong>{{ __('portal.admin.portal_url') }}:</strong>
                <div dir="ltr" class="text-start"><a href="{{ route('portal.login') }}" target="_blank">{{ route('portal.login') }}</a></div>
            </div>

            @if ($isActive && $employee->must_change_password)
                <div class="col-12 text-muted"><i class="fa-solid fa-circle-info me-1"></i>{{ __('portal.admin.must_change') }}</div>
            @endif

            @if (! $employee->portal_enabled && $employee->portal_disabled_reason)
                <div class="col-12">
                    <div class="alert alert-danger mb-0 py-2">
                        <strong>{{ __('portal.admin.disable_reason') }}:</strong> {{ $employee->portal_disabled_reason }}
                        @if ($employee->portal_disabled_at)
                            <span class="text-muted ms-2" dir="ltr">({{ $employee->portal_disabled_at->format('Y-m-d') }})</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
