{{--
    زر إجراء محتاج تأكيد (حذف، اعتماد، استرجاع...) جوّه فورم — لجداول DataTables.

    كان الـ HTML ده مكتوب كنص جوّه كل كنترولر (حوالي 20 نسخة) ومن غير
    escape للنصوص. الاستخدام من الكنترولر عبر partial الموديول:

        @include('admin.partials.table-actions.confirm', [
            'action'      => route('admin.leaves.destroy', $row->id),
            'method'      => 'DELETE',                 // اختياري، الافتراضي POST
            'icon'        => 'fa-solid fa-trash',
            'variant'     => 'btn-label-secondary',     // اختياري
            'title'       => __('global.delete'),       // التلميح
            'confirmText' => __('...'),
            // اختياري: confirmTitle, swalIcon, confirmButton, confirmButtonClass
        ])
--}}
@php
    $method = strtoupper($method ?? 'POST');
@endphp
<form action="{{ $action }}" method="POST" class="d-inline">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <button type="button"
        class="btn btn-sm btn-icon {{ $variant ?? 'btn-label-secondary' }} btn-swal-confirm"
        title="{{ $title }}"
        data-title="{{ $confirmTitle ?? __('global.confirm_delete') }}"
        data-text="{{ $confirmText }}"
        data-icon="{{ $swalIcon ?? 'warning' }}"
        data-btn-text="{{ $confirmButton ?? $title }}"
        data-btn-class="{{ $confirmButtonClass ?? 'btn-danger' }}" aria-label="{{ $title }}">
        <i class="{{ $icon }}"></i>
    </button>
</form>
