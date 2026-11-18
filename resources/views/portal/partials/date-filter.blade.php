{{--
    فلتر التاريخ في البوابة — نفس فكرة لوحة الإدارة (from_date / to_date).
    GET على نفس الصفحة: الجدول (server-side) بيطلب بياناته من نفس الرابط
    فبيتفلتر تلقائياً، والكروت الملخّصة كمان. فورم GET مالوش تأكيد إرسال.

    المتغيرات: $period = [from, to]
--}}
@php
    [$from, $to] = $period;
@endphp
<form method="GET" action="{{ url()->current() }}" class="row g-2 align-items-end">
    <div class="col-sm-4 col-md-3">
        <label class="form-label small mb-1" for="from_date">{{ __('portal.filter.from') }}</label>
        <input type="date" id="from_date" name="from_date" class="form-control form-control-sm" value="{{ $from }}">
    </div>
    <div class="col-sm-4 col-md-3">
        <label class="form-label small mb-1" for="to_date">{{ __('portal.filter.to') }}</label>
        <input type="date" id="to_date" name="to_date" class="form-control form-control-sm" value="{{ $to }}">
    </div>
    <div class="col-sm-4 col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
            <i class="fa-solid fa-filter me-1"></i>{{ __('portal.filter.apply') }}
        </button>
        {{-- مسح = كل الفترات (from/to فاضيين صراحةً عشان الافتراضي مايرجعش) --}}
        <a href="{{ url()->current() }}?from_date=&to_date=" class="btn btn-sm btn-label-secondary" title="{{ __('portal.filter.reset') }}">
            <i class="fa-solid fa-rotate-left"></i>
        </a>
    </div>
</form>
