@extends('layouts.portal.app')
@section('title', __('portal.nav.home'))

@section('content')
@php($currency = $employee->branch?->currency ?? '')

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">{{ __('portal.home.greeting', ['name' => $employee->name]) }}</h4>
        <p class="text-muted mb-0">
            {{ $employee->jobTitle?->name }} · {{ $employee->branch?->name }} ·
            <span dir="ltr">{{ $employee->employee_code }}</span>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('portal.permissions.index') }}#new" class="btn btn-primary"><i class="fa-solid fa-clock me-1"></i>{{ __('portal.permissions.new') }}</a>
        <a href="{{ route('portal.leaves.index') }}#new" class="btn btn-label-primary"><i class="fa-solid fa-calendar-plus me-1"></i>{{ __('portal.leaves.new') }}</a>
    </div>
</div>

{{-- تنبيه المستندات: المنتهية أو اللي هتنتهي خلال المدة --}}
@if ($expiringDocuments->isNotEmpty())
    <div class="alert alert-warning d-flex gap-3 mb-4" role="alert">
        <i class="fa-solid fa-triangle-exclamation fa-lg mt-1"></i>
        <div class="flex-grow-1">
            <h6 class="alert-heading fw-bold mb-2">{{ __('portal.home.documents_alert') }}</h6>
            <ul class="mb-1 ps-3">
                @foreach ($expiringDocuments as $doc)
                    @php($days = (int) now()->startOfDay()->diffInDays($doc->expiry_date, false))
                    <li>
                        <strong>{{ $doc->title ?: __('document.types.' . $doc->document_type) }}</strong> —
                        @if ($days < 0)
                            <span class="text-danger">{{ __('portal.home.document_expired', ['date' => $doc->expiry_date->format('Y-m-d')]) }}</span>
                        @elseif ($days === 0)
                            {{ __('portal.home.document_expires_today') }}
                        @else
                            {{ __('portal.home.document_expires_in', ['days' => $days, 'date' => $doc->expiry_date->format('Y-m-d')]) }}
                        @endif
                    </li>
                @endforeach
            </ul>
            <small>{{ __('portal.home.documents_alert_hint') }}</small>
        </div>
    </div>
@endif

{{-- مهامي: أقرب التسليمات --}}
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-list-check text-primary me-2"></i>{{ __('portal.nav.tasks') }}
            <span class="badge bg-label-primary ms-1">{{ $taskCounts['open'] }}</span>
            @if ($taskCounts['overdue'])<span class="badge bg-label-danger ms-1">{{ __('task.stats.overdue') }}: {{ $taskCounts['overdue'] }}</span>@endif
        </h6>
        <a href="{{ route('portal.tasks.index') }}" class="btn btn-sm btn-label-primary">{{ __('notification.view_all') }}</a>
    </div>
    <div class="list-group list-group-flush">
        @forelse ($myTasks as $task)
            <a href="{{ route('portal.tasks.show', $task->id) }}" class="list-group-item list-group-item-action d-flex flex-wrap align-items-center gap-3">
                <div class="flex-grow-1" style="min-width: 10rem">
                    <div class="fw-semibold text-break">{{ $task->title }}</div>
                    <div class="d-flex gap-2 mt-1">{!! \App\Enums\TaskStatus::badgeFor($task->status) !!} {!! \App\Enums\TaskPriority::badgeFor($task->priority) !!}</div>
                </div>
                <div class="small">@include('tasks.partials.due', ['task' => $task])</div>
                <div style="width: 7rem">@include('tasks.partials.progress-bar', ['task' => $task, 'compact' => true])</div>
            </a>
        @empty
            <div class="list-group-item text-body-secondary small">{{ __('task.labels_text.empty') }}</div>
        @endforelse
    </div>
</div>
@include('tasks.partials.styles')

<div class="row g-4 mb-4">
    {{-- أرصدة الإجازات --}}
    @foreach ([
        ['annual', 'fa-umbrella-beach', 'success'],
        ['casual', 'fa-mug-hot',        'info'],
        ['sick',   'fa-notes-medical',  'warning'],
    ] as [$type, $icon, $color])
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 portal-stat">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="icon bg-label-{{ $color }}"><i class="fa-solid {{ $icon }} fs-5"></i></span>
                    <div>
                        <div class="small text-muted">{{ __('portal.home.balance_' . $type) }}</div>
                        <div class="fs-5 fw-bold">
                            {{ $balance ? rtrim(rtrim(number_format((float) $balance->{'remaining_' . $type}, 2), '0'), '.') : '—' }}
                            <small class="text-muted fw-normal">{{ __('portal.home.days') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    {{-- الاستئذان هذا الشهر --}}
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100 portal-stat">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="icon bg-label-primary"><i class="fa-solid fa-clock fs-5"></i></span>
                <div>
                    <div class="small text-muted">{{ __('portal.home.permission_hours') }}</div>
                    <div class="fs-5 fw-bold @if ($permissionHours > $permissionLimit) text-danger @endif">
                        <span dir="ltr">{{ $permissionHours + 0 }} / {{ $permissionLimit + 0 }}</span>
                        <small class="text-muted fw-normal">{{ __('portal.home.hours') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- طلباتي المعلّقة --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-solid fa-hourglass-half me-2 text-warning"></i>{{ __('portal.home.pending_title') }}</h6></div>
            <div class="list-group list-group-flush">
                @foreach ([
                    ['permissions', 'portal.permissions.index'],
                    ['leaves',      'portal.leaves.index'],
                    ['advances',    'portal.advances.index'],
                ] as [$key, $route])
                    <a href="{{ route($route) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        {{ __('portal.nav.' . $key) }}
                        <span class="badge {{ $pending[$key] ? 'bg-warning' : 'bg-label-secondary' }} rounded-pill">{{ $pending[$key] }}</span>
                    </a>
                @endforeach
            </div>
            @if ($advanceRemaining > 0)
                <div class="card-footer small text-muted">
                    <i class="fa-solid fa-hand-holding-dollar me-1"></i>
                    {{ __('portal.home.advance_remaining') }}:
                    <strong class="text-danger" dir="ltr">{{ number_format($advanceRemaining, 2) }} {{ $currency }}</strong>
                </div>
            @endif
        </div>
    </div>

    {{-- آخر قسائم الراتب --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-file-invoice-dollar me-2 text-success"></i>{{ __('portal.home.latest_payslips') }}</h6>
                <a href="{{ route('portal.payslips.index') }}" class="small">{{ __('portal.home.view_all') }}</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($latestPayslips as $item)
                    <a href="{{ route('portal.payslips.show', $item->id) }}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span dir="ltr">{{ sprintf('%02d / %04d', $item->payroll?->month, $item->payroll?->year) }}</span>
                        <strong class="text-success" dir="ltr">{{ number_format((float) $item->net_salary, 2) }} {{ $currency }}</strong>
                    </a>
                @empty
                    <div class="list-group-item text-muted small">{{ __('portal.payslips.empty') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
