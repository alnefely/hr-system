@props([
    'month'      => null,          // الشهر المختار
    'year'       => null,          // السنة المختارة
    'monthName'  => 'month',       // اسم حقل الشهر
    'yearName'   => 'year',        // اسم حقل السنة
    'label'      => null,          // عنوان فوق الحقلين
    'help'       => null,          // نص التلميح
    'yearsBack'  => 3,             // كام سنة للخلف من السنة الحالية
    'yearsAhead' => 1,             // كام سنة للأمام
    'inline'     => true,          // الشهر والسنة في سطر واحد
    'required'   => false,
])

@php
    $currentYear  = (int) date('Y');
    $selectedYear = (int) ($year ?? $currentYear);

    // النطاق مبني على السنة الحالية مش على رقم ثابت — كان 2024 مكتوبة
    // في الكود، فكل سنة بتعدي كان لازم حد يفتح الملف ويعدّلها.
    $from = $currentYear - (int) $yearsBack;
    $to   = $currentYear + (int) $yearsAhead;

    // لو السجل المعروض بره النطاق (بيانات قديمة) بنوسّعه عشان
    // القيمة المحفوظة تفضل ظاهرة ومختارة.
    $from = min($from, $selectedYear);
    $to   = max($to, $selectedYear);

    $selectedMonth = (int) ($month ?? date('n'));
@endphp

<div>
    @if($label)
        <label class="form-label text-muted small mb-1">
            {{ $label }}
            @if($required)<span class="text-danger">*</span>@endif
            @if($help)
                <x-inputs.help :text="$help" />
            @endif
        </label>
    @endif

    <div class="{{ $inline ? 'input-group' : '' }}">
        {{-- no-select2 عشان الحقلين يفضلوا جنب بعض في الـ input-group --}}
        <select name="{{ $monthName }}" class="form-select no-select2 px-2" @required($required)>
            @foreach (__('global.months') as $number => $name)
                <option value="{{ $number }}" @selected($selectedMonth === (int) $number)>
                    {{ $name }}
                </option>
            @endforeach
        </select>

        <select name="{{ $yearName }}" class="form-select no-select2 px-2" @required($required)>
            @for ($y = $to; $y >= $from; $y--)
                <option value="{{ $y }}" @selected($selectedYear === $y)>{{ $y }}</option>
            @endfor
        </select>
    </div>
</div>
