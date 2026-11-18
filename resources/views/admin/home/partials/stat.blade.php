{{--
    كارت رقم موحّد للرئيسية — كل الأرقام (المالية والموارد البشرية) بنفس
    الشكل: عنوان صغير، رقم كبير، وأيقونة بحجم ثابت.

    $label, $value, $icon, $color (لون الأيقونة: primary/success/danger/warning/info)
    اختياري: $suffix (العملة)، $hint (شرح ؟)، $valueClass (لون الرقم — الافتراضي محايد)
--}}
<div class="card dash-stat h-100">
    <div class="card-body d-flex align-items-center gap-3">
        <span class="dash-stat-icon bg-label-{{ $color }}"><i class="fa-solid {{ $icon }}"></i></span>
        <div class="min-w-0">
            <div class="dash-stat-label">
                {{ $label }}
                @if (! empty($hint))
                    <x-inputs.tip :text="$hint" />
                @endif
            </div>
            <div class="dash-stat-value {{ $valueClass ?? '' }}">
                <span dir="ltr">{{ $value }}</span>
                @if (! empty($suffix))
                    <small class="dash-stat-suffix">{{ $suffix }}</small>
                @endif
            </div>
        </div>
    </div>
</div>
