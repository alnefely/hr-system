{{-- عمود الإجراءات في جدول الأذونات --}}
<div class="d-inline-flex gap-1">
    @if ($row->status === \App\Enums\ApprovalStatus::Pending->value)
        @if (admin_can('permission_approve'))
            @include('admin.partials.table-actions.confirm', [
                'action'             => route('admin.permissions.approve', $row->id),
                'icon'               => 'fa-solid fa-check',
                'variant'            => 'btn-label-success',
                'title'              => __('permission.actions.approve'),
                'confirmTitle'       => 'اعتماد طلب الاستئذان',
                'confirmText'        => 'هل أنت متأكد من اعتماد طلب الاستئذان لهذا الموظف؟',
                'swalIcon'           => 'question',
                'confirmButton'      => 'نعم، اعتمد',
                'confirmButtonClass' => 'btn-success',
            ])
        @endif

        @if (admin_can('permission_reject'))
            <button type="button" class="btn btn-sm btn-icon btn-label-danger reject-btn" data-id="{{ $row->id }}" title="{{ __('permission.actions.reject') }}">
                <i class="fa-solid fa-xmark"></i>
            </button>
        @endif
    @endif

    @if (admin_can('permission_delete'))
        @include('admin.partials.table-actions.confirm', [
            'action'        => route('admin.permissions.destroy', $row->id),
            'method'        => 'DELETE',
            'icon'          => 'fa-solid fa-trash',
            'title'         => __('global.delete'),
            'confirmTitle'  => 'تأكيد الحذف',
            'confirmText'   => 'هل أنت متأكد من حذف هذا السجل؟',
            'confirmButton' => 'نعم، احذف',
        ])
    @endif
</div>
