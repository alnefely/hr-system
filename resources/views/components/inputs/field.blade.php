{{-- resources/views/components/form/field.blade.php --}}

@props([
    'name',                     // اسم الحقل (مطلوب)
    'type'        => 'text',    // نوع الحقل
    'id'          => null,      // id (افتراضي = name)
    'label'       => null,      // عنوان الحقل
    'value'       => null,      // القيمة
    'placeholder' => null,      // placeholder
    'hint'        => null,      // نص مساعد
    'required'    => false,     // مطلوب
    'disabled'    => false,     // معطل
    'readonly'    => false,     // للقراءة فقط
    'options'     => [],        // للـ select والـ checkbox
    'rows'        => 4,         // للـ textarea
    'col'         => null,      // col-md-X (اختياري)
    'class'       => '',        // كلاسات إضافية للـ wrapper
    'inputClass'  => '',        // كلاسات إضافية للـ input
    'dir'         => null,   // ← ltr, rtl, auto (افتراضي = null يورث من الصفحة)
])

@php
    $inputId    = $id ?? $name;
    $value      = old($name, $value);
    $hasError   = $errors->has($name);
    $inputAttrs = collect([
        'id'          => $inputId,
        'name'        => $name,
        'placeholder' => $placeholder,
        'disabled'    => $disabled  ? 'disabled'  : null,
        'readonly'    => $readonly  ? 'readonly'  : null,
        'required'    => $required  ? 'required'  : null,
        'dir'         => $dir,
    ])->filter()->toArray();
@endphp

<div @class(['col-md-' . $col => $col, $class => $class, 'mb-3'])>

    {{-- Label --}}
    @if($label)
        <label for="{{ $inputId }}" class="form-label">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    {{-- Input --}}
    @switch($type)

        @case('text')
        @case('email')
        @case('url')
        @case('number')
        @case('color')
        @case('date')
        @case('time')
        @case('tel')
        @case('datetime-local')
            <input
                type="{{ $type }}"
                value="{{ $value ?? '' }}"
                class="form-control {{ $inputClass }} @error($name) is-invalid @enderror"
                {{ $attributes->except(['class']) }}
                @foreach($inputAttrs as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
            >
        @break

        @case('password')
            <div class="input-group form-password-toggle">
                <input
                    type="password"
                    value="{{ $value ?? '' }}"
                    class="form-control {{ $inputClass }} @error($name) is-invalid @enderror"
                    {{ $attributes->except(['class']) }}
                    @foreach($inputAttrs as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                >
                <span class="input-group-text cursor-pointer">
                    <i class="icon-base ti tabler-eye-off"></i>
                </span>
            </div>
        @break

        @case('textarea')
            <textarea
                rows="{{ $rows }}"
                class="form-control {{ $inputClass }} @error($name) is-invalid @enderror"
                {{ $attributes->except(['class']) }}
                @foreach($inputAttrs as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
            >{{ $value }}</textarea>
        @break

        @case('select')
            <select
                class="form-select select2 {{ $inputClass }} @error($name) is-invalid @enderror"
                {{ $attributes->except(['class']) }}
                @foreach($inputAttrs as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
            >
                @foreach($options as $optLabel)
                    <option value="{{ $optLabel }}" {{ $value == $optLabel ? 'selected' : '' }}>
                        {{ $optLabel }}
                    </option>
                @endforeach
            </select>
        @break

        @case('checkbox')
            <div class="form-check form-switch">
                <input type="hidden" name="{{ $name }}" value="0">
                <input
                    type="checkbox"
                    value="1"
                    class="form-check-input {{ $inputClass }} @error($name) is-invalid @enderror"
                    {{ $attributes->except(['class']) }}
                    @foreach($inputAttrs as $attr => $val) {{ $attr }}="{{ $val }}" @endforeach
                    {{ $value ? 'checked' : '' }}
                >
            </div>
        @break

    @endswitch

    {{-- Hint --}}
    @if($hint)
        <div class="form-text text-muted">{{ $hint }}</div>
    @endif

    {{-- Error --}}
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror

</div>