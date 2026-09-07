@props([
    'text'  => null,        // نص الشرح (مطلوب)
    'title' => null,        // عنوان اختياري — بيحوّلها لنافذة popover
    'icon'  => 'fa-solid fa-circle-info',
    'color' => 'primary',   // primary | warning | danger | success | info
])

@if($text)
    @if($title)
        {{-- عنوان + نص => popover، مناسب للشرح الطويل --}}
        <a href="javascript:void(0);"
           class="text-decoration-none ms-1 align-middle"
           data-bs-toggle="popover"
           data-bs-trigger="hover focus"
           data-bs-placement="top"
           data-bs-html="true"
           title="{{ $title }}"
           data-bs-content="{{ $text }}">
            <i class="{{ $icon }} text-{{ $color }}" style="font-size: .8rem;"></i>
        </a>
    @else
        {{-- نص بس => tooltip، مناسب للشرح القصير --}}
        <i class="{{ $icon }} text-{{ $color }} ms-1 align-middle"
           style="font-size: .8rem;"
           data-bs-toggle="tooltip"
           data-bs-html="true"
           title="{{ $text }}"></i>
    @endif
@endif
