@extends('layouts.admin.app')

@php
    $lockedSubject = $lockedSubject ?? null;
    $pageTitle = $lockedSubject
        ? __('activity.module_title', ['module' => activity_subject_label($lockedSubject)])
        : __('activity.title');
@endphp

@section('title', $pageTitle)

@section('content')

{{-- بطاقات الإحصائيات --}}
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100 shadow-sm border-top border-3 border-primary">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="badge bg-label-primary rounded p-3">
                    <i class="fa-solid fa-list-check fs-4"></i>
                </span>
                <div>
                    <small class="text-muted d-block" data-bs-toggle="tooltip"
                           title="{{ __('activity.stats.total_hint') }}">
                        {{ __('activity.stats.total') }}
                    </small>
                    <h4 class="mb-0 fw-bold" dir="ltr">{{ number_format($stats['total']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card h-100 shadow-sm border-top border-3 border-info">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="badge bg-label-info rounded p-3">
                    <i class="fa-solid fa-clock-rotate-left fs-4"></i>
                </span>
                <div>
                    <small class="text-muted d-block" data-bs-toggle="tooltip"
                           title="{{ __('activity.stats.today_hint') }}">
                        {{ __('activity.stats.today') }}
                    </small>
                    <h4 class="mb-0 fw-bold" dir="ltr">{{ number_format($stats['today']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card h-100 shadow-sm border-top border-3 border-success">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="badge bg-label-success rounded p-3">
                    <i class="fa-solid fa-circle-plus fs-4"></i>
                </span>
                <div>
                    <small class="text-muted d-block">{{ __('activity.stats.created') }}</small>
                    <h4 class="mb-0 fw-bold" dir="ltr">{{ number_format($stats['created']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card h-100 shadow-sm border-top border-3 border-warning">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="badge bg-label-warning rounded p-3">
                    <i class="fa-solid fa-pen-to-square fs-4"></i>
                </span>
                <div>
                    <small class="text-muted d-block">{{ __('activity.stats.updated') }}</small>
                    <h4 class="mb-0 fw-bold" dir="ltr">{{ number_format($stats['updated']) }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-shield-halved text-primary me-2"></i> {{ $pageTitle }}
            <i class="fa-solid fa-circle-info text-muted ms-1"
               data-bs-toggle="tooltip"
               title="{{ __('activity.hints.read_only') }} {{ __('activity.hints.sensitive') }}"></i>
        </h5>

        <div class="d-flex gap-2">
            @if($lockedSubject && admin_can('activity_log_show_all'))
                <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-label-secondary waves-effect">
                    <i class="fa-solid fa-arrow-right icon-back me-1" aria-hidden="true"></i> {{ __('activity.actions.back_to_all') }}
                </a>
            @endif

            @if(admin_can('activity_log_export'))
                <a href="#" id="exportActivityBtn" class="btn btn-label-success waves-effect">
                    <i class="fa-solid fa-file-excel me-1"></i> {{ __('activity.actions.export') }}
                </a>
            @endif
        </div>
    </div>

    {{-- الفلاتر --}}
    <div class="card-body border-bottom pb-3">
        <form method="GET" id="activityFilters" class="row g-3 align-items-end">
            @unless($lockedSubject)
                <div class="col-md-3">
                    <label class="form-label" for="filter_subject">{{ __('activity.filters.subject') }}</label>
                    <select name="subject_type" id="filter_subject" class="form-select">
                        <option value="">{{ __('activity.filters.all') }}</option>
                        @foreach($subjects as $class => $label)
                            <option value="{{ $class }}" @selected(request('subject_type') === $class)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endunless

            <div class="col-md-2">
                <label class="form-label" for="filter_event">{{ __('activity.filters.event') }}</label>
                <select name="event" id="filter_event" class="form-select">
                    <option value="">{{ __('activity.filters.all') }}</option>
                    @foreach($events as $value => $label)
                        <option value="{{ $value }}" @selected(request('event') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="filter_causer">{{ __('activity.filters.causer') }}</label>
                <select name="causer_id" id="filter_causer" class="form-select">
                    <option value="">{{ __('activity.filters.all') }}</option>
                    @foreach($admins as $adminUser)
                        <option value="{{ $adminUser->id }}" @selected((string) request('causer_id') === (string) $adminUser->id)>
                            {{ $adminUser->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label" for="filter_from">{{ __('activity.filters.date_from') }}</label>
                <input type="date" name="date_from" id="filter_from" class="form-control"
                       dir="ltr" value="{{ request('date_from') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label" for="filter_to">{{ __('activity.filters.date_to') }}</label>
                <input type="date" name="date_to" id="filter_to" class="form-control"
                       dir="ltr" value="{{ request('date_to') }}">
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary waves-effect waves-light">
                    <i class="fa-solid fa-filter me-1"></i> {{ __('activity.filters.apply') }}
                </button>
                <a href="{{ $lockedSubject ? route('admin.activity-logs.subject', $lockedSubject) : route('admin.activity-logs.index') }}"
                   class="btn btn-label-secondary waves-effect">
                    <i class="fa-solid fa-rotate-left me-1"></i> {{ __('activity.filters.reset') }}
                </a>
            </div>
        </form>
    </div>

    <div class="card-body pt-4">
        @php
            $columns = [
                ['data' => 'id', 'name' => 'id'],
                ['data' => 'event_badge', 'name' => 'event'],
                ['data' => 'subject_name', 'name' => 'subject_label'],
                ['data' => 'causer', 'name' => 'causer_name'],
                ['data' => 'changes_count', 'name' => 'changes_count', 'orderable' => false, 'searchable' => false],
                ['data' => 'created', 'name' => 'created_at', 'searchType' => 'date'],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ];
        @endphp

        <x-dataTable.table
            :columns="[
                __('global.id'),
                __('activity.fields.event'),
                __('activity.fields.subject_name'),
                __('activity.fields.causer'),
                __('activity.fields.changes_count'),
                __('activity.fields.created_at'),
                __('global.actions'),
            ]"
            :fields="$columns"
            :url="request()->fullUrl()"
        />
    </div>
</div>

{{-- نافذة التفاصيل --}}
<div class="modal fade" id="activityDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title">
                    <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>
                    {{ __('activity.details.title') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="activityDetailsBody">
                <div class="text-center py-5 text-muted">
                    <div class="spinner-border spinner-border-sm me-2"></div>
                    {{ __('activity.details.loading') }}
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
(function () {
    const modalEl = document.getElementById('activityDetailsModal');
    const body    = document.getElementById('activityDetailsBody');
    const modal   = new bootstrap.Modal(modalEl);

    const T = {
        operation:   @json(__('activity.details.operation')),
        changes:     @json(__('activity.details.changes')),
        requestInfo: @json(__('activity.details.request_info')),
        noChanges:   @json(__('activity.details.no_changes')),
        loadFailed:  @json(__('activity.details.load_failed')),
        loading:     @json(__('activity.details.loading')),
        field:       @json(__('activity.fields.field')),
        oldValue:    @json(__('activity.fields.old_value')),
        newValue:    @json(__('activity.fields.new_value')),
        subject:     @json(__('activity.fields.subject')),
        record:      @json(__('activity.fields.subject_name')),
        causer:      @json(__('activity.fields.causer')),
        createdAt:   @json(__('activity.fields.created_at')),
        ip:          @json(__('activity.fields.ip_address')),
        url:         @json(__('activity.fields.url')),
        agent:       @json(__('activity.fields.user_agent')),
    };

    const esc = (v) => {
        if (v === null || v === undefined) return '—';
        return String(v)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    };

    // زرار التفاصيل بيتولد داخل DataTables، فبنستخدم delegation
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.activity-details-btn');
        if (!btn) return;

        e.preventDefault();
        body.innerHTML = '<div class="text-center py-5 text-muted">'
            + '<div class="spinner-border spinner-border-sm me-2"></div>' + esc(T.loading) + '</div>';
        modal.show();

        fetch(btn.dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => { if (!r.ok) throw new Error('http ' + r.status); return r.json(); })
            .then(renderDetails)
            .catch(() => {
                body.innerHTML = '<div class="alert alert-danger mb-0">' + esc(T.loadFailed) + '</div>';
            });
    });

    function renderDetails(d) {
        let html = '';

        // بيانات العملية
        html += '<h6 class="fw-bold mb-3">' + esc(T.operation) + '</h6>';
        html += '<div class="row g-3 mb-4">';
        html += infoCol(T.subject,   esc(d.subject_label));
        html += infoCol(T.record,    esc(d.subject_name || ('#' + d.subject_id)));
        html += infoCol(T.causer,    esc(d.causer_name));
        html += infoCol(T.createdAt, '<span dir="ltr">' + esc(d.created_at) + '</span>');
        html += '</div>';

        // الحقول المتغيّرة
        html += '<h6 class="fw-bold mb-3">' + esc(T.changes)
             + ' <span class="badge ' + esc(d.event_badge) + '">' + esc(d.event_label) + '</span></h6>';

        if (!d.changes || d.changes.length === 0) {
            html += '<div class="alert alert-secondary">' + esc(T.noChanges) + '</div>';
        } else {
            html += '<div class="table-responsive mb-4"><table class="table table-sm table-bordered align-middle mb-0">';
            html += '<thead><tr>'
                 + '<th style="width:30%">' + esc(T.field) + '</th>'
                 + '<th style="width:35%">' + esc(T.oldValue) + '</th>'
                 + '<th style="width:35%">' + esc(T.newValue) + '</th>'
                 + '</tr></thead><tbody>';

            d.changes.forEach(function (c) {
                const changed = c.old !== c.new;
                html += '<tr>'
                     + '<td class="fw-semibold">' + esc(c.label) + '</td>'
                     + '<td><span class="text-danger">' + esc(c.old) + '</span></td>'
                     + '<td><span class="' + (changed ? 'text-success fw-semibold' : 'text-muted') + '">'
                     + esc(c.new) + '</span></td>'
                     + '</tr>';
            });

            html += '</tbody></table></div>';
        }

        // بيانات الطلب
        html += '<h6 class="fw-bold mb-3">' + esc(T.requestInfo) + '</h6>';
        html += '<div class="row g-3">';
        html += infoCol(T.ip,  '<span dir="ltr">' + esc(d.ip_address) + '</span>');
        html += infoCol(T.url, '<small dir="ltr" class="text-break">' + esc(d.url) + '</small>', 12);
        html += infoCol(T.agent, '<small dir="ltr" class="text-break text-muted">' + esc(d.user_agent) + '</small>', 12);
        html += '</div>';

        body.innerHTML = html;
    }

    function infoCol(label, value, width) {
        return '<div class="col-md-' + (width || 6) + '">'
             + '<small class="text-muted d-block">' + esc(label) + '</small>'
             + '<div class="fw-semibold">' + value + '</div>'
             + '</div>';
    }

    // التصدير بيحترم الفلاتر الحالية
    const exportBtn = document.getElementById('exportActivityBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const url = new URL(window.location.href);
            url.searchParams.set('export', 'excel');
            window.location.href = url.toString();
        });
    }
})();
</script>
@endpush
