{{--
    أيقونة الشرح (؟) جنب عنوان الحقل — نفس شكل شاشة الموظف.

    الاستخدام جوّه أي label:
        <label class="form-label">{{ __('x.fields.y') }} <x-inputs.tip :text="__('x.hints.y')" /></label>

    ومكوّنات الحقول (field / select2 / lfm / phone / month-year) بتاخد
    خاصية tooltip وبترسمها هي.

    الـ tooltips بتتفعّل تلقائياً في main.js بتاع القالب لأي
    data-bs-toggle="tooltip" موجود في الصفحة (حتى جوّه النوافذ).
--}}
@props(['text' => null])

@if (filled($text))
    <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer"
       data-bs-toggle="tooltip" data-bs-placement="top"
       title="{{ $text }}" tabindex="0" aria-label="{{ $text }}"></i>
@endif
