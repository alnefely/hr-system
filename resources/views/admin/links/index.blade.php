@extends('layouts.admin.app')
@section('title', __('link.title'))

@section('content')

{{-- بطاقات ملخّص --}}
<div class="row g-4 mb-4">
    @php
        $cards = [
            ['key' => 'total',   'value' => $stats['total'],   'colour' => 'primary', 'icon' => 'fa-link'],
            ['key' => 'active',  'value' => $stats['active'],  'colour' => 'success', 'icon' => 'fa-circle-check'],
            ['key' => 'clicks',  'value' => $stats['clicks'],  'colour' => 'info',    'icon' => 'fa-arrow-pointer'],
            ['key' => 'expired', 'value' => $stats['expired'], 'colour' => 'danger',  'icon' => 'fa-hourglass-end'],
        ];
        $cardLabels = [
            'total'   => 'link.stats.total_links',
            'active'  => 'link.stats.active_links',
            'clicks'  => 'link.stats.total_clicks',
            'expired' => 'link.stats.expired_links',
        ];
    @endphp

    @foreach ($cards as $card)
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-start border-{{ $card['colour'] }} border-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="avatar avatar-md rounded bg-label-{{ $card['colour'] }} d-flex align-items-center justify-content-center">
                            <i class="fa-solid {{ $card['icon'] }} fs-4"></i>
                        </span>
                        <h3 class="mb-0 fw-bold text-{{ $card['colour'] }}" dir="ltr">
                            {{ number_format($card['value']) }}
                        </h3>
                    </div>
                    <p class="mb-0 text-muted small">{{ __($cardLabels[$card['key']]) }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card border-0 shadow-sm">

    {{-- الترويسة --}}
    <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0 fw-bold">
            <i class="fa-solid fa-link text-primary me-2"></i> {{ __('link.title') }}
        </h5>

        @if (admin_can('link_trash'))
            <a href="{{ route('admin.links.trash') }}" class="btn btn-label-danger waves-effect">
                <i class="fa-solid fa-trash me-1"></i> {{ __('link.trash') }}
                @if ($trashCount > 0)
                    <span class="badge bg-danger ms-1">{{ $trashCount }}</span>
                @endif
            </a>
        @endif
    </div>

    {{-- الفلاتر --}}
    <div class="card-header border-bottom d-print-none">
        <h6 class="fw-bold mb-3">
            <i class="fa-solid fa-filter text-primary me-1"></i> {{ __('global.search') }}
        </h6>

        <form action="{{ route('admin.links.index') }}" method="GET" class="row g-3 align-items-end">

            <div class="col-md-3">
                <label class="form-label text-muted small mb-1">{{ __('link.fields.branch') }}</label>
                <select name="branch_id" class="form-select select2">
                    <option value="">{{ __('link.filters.all_branches') }}</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (int) request('branch_id') === (int) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label text-muted small mb-1">{{ __('link.fields.is_active') }}</label>
                <select name="status" class="form-select select2">
                    <option value="">{{ __('link.filters.all_statuses') }}</option>
                    @foreach (['active', 'inactive', 'expired'] as $statusKey)
                        <option value="{{ $statusKey }}" {{ request('status') === $statusKey ? 'selected' : '' }}>
                            {{ __('link.status.' . $statusKey) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label text-muted small mb-1">{{ __('link.filters.period') }}</label>
                <div class="input-group">
                    <input type="date" name="date_from" class="form-control px-1" value="{{ request('date_from') }}" />
                    <input type="date" name="date_to" class="form-control px-1" value="{{ request('date_to') }}" />
                </div>
            </div>

            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa-solid fa-filter me-1"></i> {{ __('global.search') }}
                </button>
                <a href="{{ route('admin.links.index') }}" class="btn btn-outline-secondary"
                   data-bs-toggle="tooltip" title="{{ __('global.reset_filter') }}">
                    <i class="fa-solid fa-rotate"></i>
                </a>
            </div>

        </form>
    </div>

    <div class="card-body pt-4">
        @php
            $branchOptions = $branches->pluck('name', 'name')->all();

            $statusOptions = collect(['active', 'inactive', 'expired'])
                ->mapWithKeys(fn ($s) => [$s => __('link.status.' . $s)])->all();

            $columns = [
                ['data' => 'id', 'name' => 'id'],
                ['data' => 'short_url', 'name' => 'short_code'],
                ['data' => 'destination', 'name' => 'original_url'],
                ['data' => 'branch_name', 'name' => 'branch.name', 'searchType' => 'select', 'searchOptions' => $branchOptions],
                ['data' => 'clicks', 'name' => 'clicks_count'],
                ['data' => 'status', 'name' => 'is_active', 'searchType' => 'select', 'searchOptions' => $statusOptions],
                ['data' => 'creator_name', 'name' => 'creator.name'],
                ['data' => 'formatted_date', 'name' => 'created_at', 'searchType' => 'date'],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ];
        @endphp

        <x-dataTable.table
            :columns="[
                __('global.id'),
                __('link.fields.short_url'),
                __('link.fields.original_url'),
                __('link.fields.branch'),
                __('link.fields.clicks_count'),
                __('link.fields.is_active'),
                __('link.fields.creator'),
                __('link.fields.created_at'),
                __('global.actions'),
            ]"
            :fields="$columns"
            :url="route('admin.links.index', request()->query())"
            :add-new="admin_can('link_create') ? __('link.create_title') : null"
            :add-new-url="admin_can('link_create') ? route('admin.links.create') : null"
        />
    </div>
</div>

@endsection

@push('js')
<script>
$(document).ready(function () {
    // النسخ بيتعمل بالتفويض لأن صفوف الجدول بتتبني بعد التحميل
    $(document).on('click', '.btn-copy-link', function () {
        var url = $(this).data('url');
        var btn = $(this);

        var done = function () {
            var icon = btn.find('i');
            icon.removeClass('fa-copy').addClass('fa-check text-success');
            setTimeout(function () {
                icon.removeClass('fa-check text-success').addClass('fa-copy');
            }, 1500);
        };

        // clipboard API بيشتغل على https بس، فبنرجع لطريقة قديمة لو مش متاح
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(done);
        } else {
            var tmp = $('<input>').val(url).appendTo('body').select();
            document.execCommand('copy');
            tmp.remove();
            done();
        }
    });
});
</script>
@endpush
