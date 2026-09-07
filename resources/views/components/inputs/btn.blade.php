@props([
    'type' => 'submit',
    'status' => 'primary',
    'class' => '',
    'id' => null,
    'text' => null,
    'action' => null,
    'attr' => [],
])
@php
    $inputId = $id ?? 'btn_' . uniqid();
    $status = in_array($status, ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark']) ? $status : 'primary';
    $type = in_array($type, ['submit', 'button', 'reset']) ? $type : 'submit';
    $text = $text ?? __('global.save');
    $action = in_array($action, ['create', 'update', 'delete', 'save']) ? $action : null;

    $messages = [
        'save'   => __('alert.confirm_save'),
        'delete' => __('alert.confirm_delete'),
        'create' => __('alert.confirm_create'),
        'update' => __('alert.confirm_update'),
    ];
    $confirmMessage = $action ? ($messages[$action] ?? __('Are you sure?')) : null;

    // تحويل الـ attr array لـ HTML attributes string
    $attrString = collect($attr)
        ->map(fn($value, $key) => is_bool($value) ? ($value ? $key : '') : "{$key}=\"{$value}\"")
        ->filter()
        ->implode(' ');
@endphp

<button
    type="{{ $type }}"
    class="btn btn-{{ $status }} {{ $class }}"
    id="{{ $inputId }}"
    @if($confirmMessage) data-confirm="{{ $confirmMessage }}" @endif
    waves-effect
    {!! $attrString !!}
>{!! $text !!}</button>

{{--
    ملاحظة: data-confirm فوق كفاية لوحدها — المعترض العام في
    layouts/alert.blade.php بيلتقطها ويعرض SweetAlert.

    كان في هنا سكربت بينادي confirm() المدمج كمان، فالمستخدم كان
    بيشوف تنبيه المتصفح الأول وبعده SweetAlert. اتشال عشان يفضل
    تنبيه واحد بس.
--}}