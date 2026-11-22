{{-- اختيار الفترة (+ فلاتر إضافية في $slotHtml). $action, $period, $hidden --}}
<form action="{{ $action }}" method="GET" class="row g-2 align-items-end" id="periodForm">
    @foreach ($hidden ?? [] as $name => $value)
        @if ($value !== null)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
    @endforeach
    <div class="col-12 col-md-3">
        <label for="rp_period" class="form-label small mb-1">{{ __('task.reports.period') }}</label>
        <select name="period" id="rp_period" class="form-select no-select2"
                onchange="document.getElementById('customRange').classList.toggle('d-none', this.value !== 'custom'); if (this.value !== 'custom') this.form.submit();">
            @foreach (__('task.reports.periods') as $value => $label)
                <option value="{{ $value }}" @selected($period['preset'] === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-4 {{ $period['preset'] === 'custom' ? '' : 'd-none' }}" id="customRange">
        <div class="row g-2">
            <div class="col-6">
                <label for="rp_from" class="form-label small mb-1">{{ __('task.reports.from') }}</label>
                <input type="date" name="from" id="rp_from" class="form-control" dir="ltr" value="{{ $period['from']->toDateString() }}">
            </div>
            <div class="col-6">
                <label for="rp_to" class="form-label small mb-1">{{ __('task.reports.to') }}</label>
                <input type="date" name="to" id="rp_to" class="form-control" dir="ltr" value="{{ $period['to']->toDateString() }}">
            </div>
        </div>
    </div>
    {!! $slotHtml ?? '' !!}
    <div class="col-6 col-md-2">
        <button type="submit" class="btn btn-primary w-100">{{ __('task.reports.apply') }}</button>
    </div>
    <div class="col-12">
        <small class="text-body-secondary" dir="ltr">{{ $period['from']->format('Y-m-d') }} → {{ $period['to']->format('Y-m-d') }}</small>
    </div>
</form>
