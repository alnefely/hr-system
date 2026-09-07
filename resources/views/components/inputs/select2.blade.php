@props([
    'name'        => null,
    'value'       => null,
    'label'       => null,
    'class'       => '',
    'id'          => null,
    'required'    => false,
    'disabled'    => false,
    'multiple'    => false,
    'data'        => [],
    'data_key'    => null,
    'data_value'  => 'id',
    'placeholder' => 'اختر...',
])

@php
    $uniqueId = $id ?? ('select_2_' . uniqid());
    $currentVal = old($name, $value);
@endphp

@if ($label)
    <label class="form-label" for="{{ $uniqueId }}">{{ $label }} @if($required)<strong class="text-danger">*</strong>@endif</label>
@endif

<select 
    id="{{ $uniqueId }}"
    class="form-select select2 {{ $class }}" 
    name="{{ $name }}" 
    @if($required) required @endif
    @if($disabled) disabled @endif
    @if($multiple) multiple @endif
    data-placeholder="{{ $placeholder }}"
    {{ $attributes->except(['class', 'name', 'id', 'required', 'disabled', 'multiple']) }}
>
    @if($placeholder && !$multiple)
        <option value="">{{ $placeholder }}</option>
    @endif

    @if (isset($slot) && trim((string)$slot) !== '')
        {{ $slot }}
    @else
        @foreach ($data as $key => $val)
            @if (is_object($val))
                @php
                    $optVal = $val->$data_value ?? $val->id ?? $key;
                    $optText = $data_key ? ($val->$data_key ?? $val[$data_key] ?? '') : ($val->name ?? $val->title ?? (string)$val);
                    $isSelected = is_array($currentVal) ? in_array($optVal, $currentVal) : ($currentVal !== null && (string)$currentVal === (string)$optVal);
                @endphp
                <option value="{{ $optVal }}" @selected($isSelected)>
                    {{ $optText }}
                </option>
            @elseif (is_array($val))
                @php
                    $optVal = $val[$data_value] ?? $key;
                    $optText = $data_key ? ($val[$data_key] ?? '') : ($val['name'] ?? $val['title'] ?? (string)$key);
                    $isSelected = is_array($currentVal) ? in_array($optVal, $currentVal) : ($currentVal !== null && (string)$currentVal === (string)$optVal);
                @endphp
                <option value="{{ $optVal }}" @selected($isSelected)>
                    {{ $optText }}
                </option>
            @else
                @php
                    $isSelected = is_array($currentVal) ? in_array($key, $currentVal) : ($currentVal !== null && (string)$currentVal === (string)$key);
                @endphp
                <option value="{{ $key }}" @selected($isSelected)>
                    {{ $val }}
                </option>
            @endif
        @endforeach
    @endif
</select>

@once
    @push('css')
        <link rel="stylesheet" href="/admin/vendor/libs/select2/select2.css" />
    @endpush
    @push('js')
        <script src="/admin/vendor/libs/select2/select2.js"></script>
    @endpush
@endonce

@push('js')
    <script>
        $(document).ready(function () {
            let $el = $("#{{ $uniqueId }}");
            if ($el.hasClass('select2-hidden-accessible')) {
                return;
            }
            let $modal = $el.closest('.modal');
            let config = {
                dir: $('html').attr('dir') === 'rtl' ? 'rtl' : 'ltr',
                width: '100%',
                placeholder: "{{ $placeholder }}",
                allowClear: {{ $required ? 'false' : 'true' }}
            };
            if ($modal.length) {
                config.dropdownParent = $modal;
            }
            $el.select2(config);
        });
    </script>
@endpush
