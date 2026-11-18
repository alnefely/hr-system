@extends('layouts.portal.app')
@section('title', __('portal.nav.salary'))

@section('content')
{{-- آخر سجل راتب هو الحالي (السجلات مرتبة من الأحدث) --}}
@php
    $current  = $employee->salaries->first();
    $currency = $current?->currency ?? $employee->branch?->currency ?? '';
    $basic    = $current ? (float) $current->basic_salary : (float) $employee->basic_salary;
    $allowSum = (float) $employee->allowances->sum('amount');
@endphp

<div class="row g-4 mb-4">
    @foreach ([
        [$basic,             'fa-sack-dollar',  'primary', __('portal.profile.basic'), ''],
        [$allowSum,          'fa-plus',         'info',    __('portal.profile.allowances'), '+'],
        [$basic + $allowSum, 'fa-wallet',       'success', __('portal.profile.gross'), ''],
    ] as [$value, $icon, $color, $label, $sign])
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar-initial rounded bg-label-{{ $color }} p-3"><i class="fa-solid {{ $icon }} fa-lg"></i></span>
                    <div>
                        <div class="text-muted small">{{ $label }}</div>
                        <h5 class="mb-0 fw-bold"><span dir="ltr">{{ $sign }}{{ number_format($value, 2) }} <small class="fs-6 text-muted">{{ $currency }}</small></span></h5>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-solid fa-list me-2 text-info"></i>{{ __('portal.profile.allowances') }}</h6></div>
            @if ($employee->allowances->isNotEmpty())
                <ul class="list-group list-group-flush">
                    @foreach ($employee->allowances as $alw)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ $alw->name }}</span>
                            <span dir="ltr">{{ number_format((float) $alw->amount, 2) }} {{ $currency }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="card-body text-muted">{{ __('portal.salary.no_allowances') }}</div>
            @endif
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-1 fw-bold"><i class="fa-solid fa-clock-rotate-left me-2 text-success"></i>{{ __('portal.profile.salary_history') }}</h6>
                <small class="text-muted">{{ __('portal.salary.history_hint') }}</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('portal.profile.effective_date') }}</th>
                            <th>{{ __('portal.profile.basic') }}</th>
                            <th>{{ __('portal.profile.allowances') }}</th>
                            <th>{{ __('portal.profile.change_reason') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employee->salaries as $s)
                            @php
                                $reasonKey = 'salary.reasons.' . $s->change_reason;
                            @endphp
                            <tr @class(['table-success' => $loop->first])>
                                <td class="text-nowrap">
                                    <span dir="ltr">{{ $s->effective_date?->format('Y-m-d') }}</span>
                                    @if ($loop->first)
                                        <span class="badge bg-label-success ms-1">{{ __('portal.salary.current') }}</span>
                                    @endif
                                </td>
                                <td class="text-nowrap fw-bold"><span dir="ltr">{{ number_format((float) $s->basic_salary, 2) }} {{ $s->currency }}</span></td>
                                <td class="text-nowrap"><span dir="ltr">{{ number_format((float) $s->allowances, 2) }}</span></td>
                                <td>{{ ! $s->change_reason ? '—' : (\Illuminate\Support\Facades\Lang::has($reasonKey) ? __($reasonKey) : $s->change_reason) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">{{ __('portal.salary.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
