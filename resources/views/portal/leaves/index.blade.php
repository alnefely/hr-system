@extends('layouts.portal.app')
@section('title', __('portal.nav.leaves'))

@push('js')
<script>
    // عدد أيام الإجازة من التاريخين (شاملين الاتنين) — ونص يوم متاح بس لليوم الواحد.
    // السيرفر بيحسبه تاني (PortalLeaveRequest)، الرقم هنا للعرض بس.
    (function () {
        const start = document.getElementById('start_date');
        const end = document.getElementById('end_date');
        const half = document.getElementById('half_day');
        const halfWrap = document.getElementById('half_day_wrapper');
        const preview = document.getElementById('leave_days_preview');
        if (!start || !end || !preview) return;

        function refresh() {
            const days = start.value && end.value
                ? Math.round((new Date(end.value) - new Date(start.value)) / 86400000) + 1
                : 0;

            halfWrap.classList.toggle('d-none', days !== 1);
            if (days !== 1) half.checked = false;
            preview.textContent = days < 1 ? '—' : (days === 1 && half.checked ? '0.5' : String(days));
        }

        start.addEventListener('change', function () {
            end.min = start.value;
            if (!end.value || end.value < start.value) end.value = start.value;
            refresh();
        });
        end.addEventListener('change', refresh);
        half.addEventListener('change', refresh);
        end.min = start.value;
        refresh();
    })();
</script>
@endpush

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        @if ($balance)
            <div class="card mb-4">
                <div class="card-header"><h6 class="mb-0 fw-bold">{{ __('portal.leaves.balance_title', ['year' => $balance->year]) }}</h6></div>
                <ul class="list-group list-group-flush">
                    @foreach (['annual', 'casual', 'sick'] as $type)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('leave.type_enums.' . $type) }}</span>
                            <span>
                                <strong>{{ (float) $balance->{'remaining_' . $type} + 0 }}</strong>
                                <small class="text-muted">/ {{ (float) $balance->{$type . '_balance'} + 0 }}</small>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card" id="new">
            <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-solid fa-plus me-2 text-primary"></i>{{ __('portal.leaves.new') }}</h6></div>
            <div class="card-body">
                <form action="{{ route('portal.leaves.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="leave_type">{{ __('portal.leaves.type') }}</label>
                        <select class="form-select" id="leave_type" name="leave_type" required>
                            @foreach (\App\Enums\LeaveType::options() as $value => $label)
                                <option value="{{ $value }}" @selected(old('leave_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" for="start_date">{{ __('portal.leaves.from') }}</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="end_date">{{ __('portal.leaves.to') }}</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                        </div>
                    </div>
                    {{-- عدد الأيام بيتحسب من التاريخين (والسيرفر بيحسبه تاني — الرقم ده للعرض بس) --}}
                    <div class="mb-3">
                        <label class="form-label">{{ __('portal.leaves.days') }}</label>
                        <div class="form-control bg-light fw-bold" id="leave_days_preview">—</div>
                        <div class="form-text">{{ __('portal.leaves.days_auto') }}</div>
                    </div>
                    <div class="mb-3 form-check d-none" id="half_day_wrapper">
                        <input type="hidden" name="half_day" value="0">
                        <input class="form-check-input" type="checkbox" id="half_day" name="half_day" value="1" @checked(old('half_day'))>
                        <label class="form-check-label" for="half_day">{{ __('portal.leaves.half_day') }}</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="reason">{{ __('portal.fields.reason') }}</label>
                        <textarea class="form-control" id="reason" name="reason" rows="2" maxlength="1000">{{ old('reason') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">{{ __('portal.requests.submit') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0 fw-bold">{{ __('portal.leaves.title') }}</h6></div>
            <div class="card-body border-bottom">
                @include('portal.partials.date-filter', ['period' => $period])
            </div>
            <div class="card-body">
                <x-dataTable.table
                    id="portal-leaves"
                    :columns="[__('portal.leaves.type'), __('portal.leaves.period'), __('portal.leaves.days'), __('portal.fields.status'), '']"
                    :fields="[
                        ['data' => 'type_badge', 'name' => 'leave_type', 'searchable' => false],
                        ['data' => 'start_date', 'searchable' => false],
                        'days_count',
                        ['data' => 'status_badge', 'name' => 'status', 'searchable' => false],
                        ['data' => 'action', 'orderable' => false, 'searchable' => false, 'className' => 'text-end'],
                    ]"
                    :order="[[1, 'desc']]"
                    :exportable="false"
                    :searchable="false" />
            </div>
        </div>
    </div>
</div>
@endsection
