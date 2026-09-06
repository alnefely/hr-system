@props([
    'name' => null,
    'value' => null,
    'label' => null,
    'class' => null,
    'required' => false,
    'data' => [],
    'data_key' => null,
    'data_value' => 'id',
    'placeholder' => 'اختر...',
])

@php
    $uniqueId = 'select_2_' . uniqid();
@endphp

@if ($label)
    <label class="form-label">{{ $label }} @if($required)<strong class="text-danger">*</strong>@endif</label>
@endif

<select class="form-select {{ $uniqueId }} {{ $class }}" name="{{ $name }}" @if($required) required @endif>
    <option value="">{{ $placeholder }}</option>
    @foreach ($data as $val)
        <option value="{{ $val->$data_value }}" @if($value != null && $value == $val->$data_value) selected @endif>
            {{ $val[$data_key] }}
        </option>
    @endforeach
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
            $(".{{ $uniqueId }}").select2({
                dir: "rtl"
            });
        });
    </script>
@endpush