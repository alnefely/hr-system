@extends('layouts.admin.app')
@section('title', __('report.documents'))

@section('content')

{{-- بطاقات الحالة: كل واحدة فلتر --}}
<div class="row g-4 mb-4">
    @php
        $cards = [
            ['key' => 'expiring', 'count' => $counts['expiring'], 'colour' => 'warning', 'icon' => 'fa-hourglass-half'],
            ['key' => 'expired',  'count' => $counts['expired'],  'colour' => 'danger',  'icon' => 'fa-triangle-exclamation'],
            ['key' => 'pending',  'count' => $counts['pending'],  'colour' => 'info',    'icon' => 'fa-clipboard-check'],
        ];
    @endphp

    @foreach ($cards as $card)
        <div class="col-sm-6 col-xl-4">
            <a href="{{ route('admin.reports.documents', array_filter([
                    'status'    => $card['key'],
                    'days'      => $days,
                    'branch_id' => $branchId,
                ])) }}"
               class="card h-100 border-start border-{{ $card['colour'] }} border-4 shadow-sm text-decoration-none
                      {{ $status === $card['key'] ? 'bg-label-' . $card['colour'] : '' }}">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="avatar avatar-md rounded bg-label-{{ $card['colour'] }} d-flex align-items-center justify-content-center">
                            <i class="fa-solid {{ $card['icon'] }} fs-4"></i>
                        </span>
                        <h3 class="mb-0 fw-bold text-{{ $card['colour'] }}" dir="ltr">{{ $card['count'] }}</h3>
                    </div>
                    <h6 class="mb-1 fw-bold">{{ __('report.doc_status.' . $card['key']) }}</h6>
                    <p class="mb-0 text-muted small">{{ __('report.doc_hints.' . $card['key'], ['days' => $days]) }}</p>
                </div>
            </a>
        </div>
    @endforeach
</div>

{{-- الفلاتر --}}
<div class="card mb-4 border-0 shadow-sm d-print-none">
    <div class="card-body">
        <form action="{{ route('admin.reports.documents') }}" method="GET" class="row g-3 align-items-end">

            <div class="col-md-4">
                <label for="admin_hr_reports_documen_f_status" class="form-label fw-bold mb-1">{{ __('document.fields.status') }}</label>
                <select id="admin_hr_reports_documen_f_status" name="status" class="form-select select2">
                    <option value="attention" {{ $status === 'attention' ? 'selected' : '' }}>
                        {{ __('report.doc_status.attention') }}
                    </option>
                    @foreach (['expiring', 'expired', 'pending'] as $key)
                        <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>
                            {{ __('report.doc_status.' . $key) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="admin_hr_reports_documen_f_branch_id" class="form-label fw-bold mb-1">{{ __('report.filters.branch') }}</label>
                <select id="admin_hr_reports_documen_f_branch_id" name="branch_id" class="form-select select2">
                    <option value="">{{ __('payroll.filters.all_branches') }}</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (int) $branchId === (int) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label for="admin_hr_reports_documen_f_days" class="form-label fw-bold mb-1">
                    {{ __('report.doc_filters.days') }}
                    <x-inputs.help :text="__('report.doc_filters.days_hint')" />
                </label>
                <input id="admin_hr_reports_documen_f_days" type="number" name="days" class="form-control h-38" min="1" max="365"
                       value="{{ $days }}" dir="ltr">
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1 d-flex align-items-center justify-content-center h-38">
                    <i class="fa-solid fa-filter me-1"></i> {{ __('global.filter') }}
                </button>
                <a href="{{ route('admin.reports.documents') }}"
                   class="btn btn-label-secondary d-flex align-items-center justify-content-center h-38"
                   data-bs-toggle="tooltip" title="{{ __('global.reset_filter') }}" aria-label="{{ __('global.reset_filter') }}">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>

        </form>
    </div>
</div>

{{-- الجدول --}}
<div class="card border-0 shadow-sm">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0 fw-bold">
            <i class="fa-solid fa-folder-open text-primary me-2"></i> {{ __('report.documents') }}
        </h5>
        <span class="badge bg-label-primary">
            <span dir="ltr">{{ $documents->total() }}</span> {{ __('document.singular') }}
        </span>
    </div>

    <div class="card-body pt-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>{{ __('document.fields.employee') }}</th>
                        <th>{{ __('document.fields.document_type') }}</th>
                        <th>{{ __('document.fields.title') }}</th>
                        <th>{{ __('document.fields.expiry_date') }}</th>
                        <th>{{ __('document.fields.status') }}</th>
                        <th>{{ __('document.fields.is_verified') }}</th>
                        <th class="text-center">{{ __('global.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $doc)
                        <tr>
                            <td>
                                <span class="fw-semibold">{{ $doc->employee?->name ?? '-' }}</span>
                                <small class="text-muted d-block">
                                    {{ $doc->employee?->employee_code }}
                                    @if ($doc->employee?->branch)
                                        — {{ $doc->employee->branch->name }}
                                    @endif
                                </small>
                            </td>

                            <td>
                                @php $typeKey = 'document.types.' . $doc->document_type; @endphp
                                <span class="badge bg-label-secondary">
                                    {{ __($typeKey) === $typeKey ? $doc->document_type : __($typeKey) }}
                                </span>
                            </td>

                            <td>{{ $doc->title }}</td>

                            <td>
                                @if ($doc->expiry_date)
                                    <span dir="ltr">{{ $doc->expiry_date->format('Y-m-d') }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                @if ($doc->is_expired)
                                    <span class="badge bg-label-danger">
                                        {{ __('document.fields.expired') }}
                                        <span dir="ltr">({{ abs($doc->days_until_expiry) }})</span>
                                    </span>
                                @elseif ($doc->is_expiring_soon)
                                    <span class="badge bg-label-warning">
                                        {{ __('report.doc_status.days_left', ['days' => $doc->days_until_expiry]) }}
                                    </span>
                                @elseif ($doc->expiry_date)
                                    <span class="badge bg-label-success">{{ __('document.fields.valid') }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                @if ($doc->is_verified)
                                    <span class="badge bg-label-success">{{ __('document.verified') }}</span>
                                @else
                                    <span class="badge bg-label-info">{{ __('document.unverified') }}</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    @if (admin_can('employee_show'))
                                        <a href="{{ route('admin.employees.show', $doc->employee_id) }}#tab-docs"
                                           class="btn btn-sm btn-icon btn-label-primary"
                                           data-bs-toggle="tooltip" title="{{ __('document.back') }}" aria-label="{{ __('document.back') }}">
                                            <i class="fa-solid fa-arrow-left"></i>
                                        </a>
                                    @endif

                                    @if ($doc->file_path)
                                        <a href="{{ $doc->file_path }}" target="_blank"
                                           class="btn btn-sm btn-icon btn-label-info"
                                           data-bs-toggle="tooltip" title="{{ __('document.view_file') }}" aria-label="{{ __('document.view_file') }}">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    @endif

                                    @if (! $doc->is_verified && admin_can('document_update'))
                                        <form action="{{ route('admin.documents.toggle-verification', $doc->id) }}"
                                              method="POST" class="d-inline">
                                            @csrf
                                            <button type="button"
                                                    class="btn btn-sm btn-icon btn-label-success waves-effect btn-swal-confirm"
                                                    data-title="{{ __('document.confirm.verify_title') }}"
                                                    data-text="{{ __('document.confirm.verify_text', [
                                                        'document' => $doc->title,
                                                        'employee' => $doc->employee?->name ?? '',
                                                    ]) }}"
                                                    data-icon="question"
                                                    data-btn-text="{{ __('document.confirm.verify_button') }}"
                                                    data-btn-class="btn-success"
                                                    title="{{ __('document.verified') }}" aria-label="{{ __('document.confirm.verify_title') }}">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fa-solid fa-circle-check text-success mb-2" style="font-size: 2rem; opacity: .4;"></i>
                                <p class="text-muted mb-0">{{ __('report.doc_status.all_clear') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($documents->hasPages())
            <div class="mt-3">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>

@endsection
