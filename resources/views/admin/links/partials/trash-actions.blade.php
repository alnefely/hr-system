{{-- إجراءات سلة الروابط: استرجاع وحذف نهائي --}}
<div class="d-flex gap-1 justify-content-center">
    @if (admin_can('link_restore'))
        @include('admin.partials.table-actions.confirm', [
            'action'             => route('admin.links.restore', $row->id),
            'icon'               => 'fa-solid fa-rotate-left',
            'variant'            => 'btn-label-success',
            'title'              => __('global.restore'),
            'confirmTitle'       => __('link.confirm.restore_title'),
            'confirmText'        => __('link.confirm.restore_text', ['link' => $row->short_code]),
            'swalIcon'           => 'question',
            'confirmButton'      => __('link.confirm.restore_button'),
            'confirmButtonClass' => 'btn-success',
        ])
    @endif

    @if (admin_can('link_force_delete'))
        @include('admin.partials.table-actions.confirm', [
            'action'        => route('admin.links.forceDelete', $row->id),
            'method'        => 'DELETE',
            'icon'          => 'fa-solid fa-trash',
            'variant'       => 'btn-label-danger',
            'title'         => __('global.force_delete'),
            'confirmText'   => __('global.confirm_delete'),
            'confirmButton' => __('global.force_delete'),
        ])
    @endif
</div>
