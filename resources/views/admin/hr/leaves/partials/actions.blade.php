{{-- عمود الإجراءات في جدول الإجازات --}}
<div class="d-inline-flex gap-1">
    @if ($row->status === \App\Enums\LeaveStatus::Pending->value)
        @if (admin_can('leave_approve'))
            @include('admin.partials.table-actions.confirm', [
                'action'             => route('admin.leaves.approve', $row->id),
                'icon'               => 'fa-solid fa-check',
                'variant'            => 'btn-label-success',
                'title'              => __('leave.actions.approve'),
                'confirmTitle'       => 'اعتماد الإجازة',
                'confirmText'        => 'هل أنت متأكد من اعتماد طلب الإجازة لهذا الموظف؟',
                'swalIcon'           => 'question',
                'confirmButton'      => 'نعم، اعتمد',
                'confirmButtonClass' => 'btn-success',
            ])
        @endif

        @if (admin_can('leave_reject'))
            <button type="button" class="btn btn-sm btn-icon btn-label-danger reject-btn" data-id="{{ $row->id }}" title="{{ __('leave.actions.reject') }}">
                <i class="fa-solid fa-xmark"></i>
            </button>
        @endif
    @endif

    @if (admin_can('leave_delete'))
        @include('admin.partials.table-actions.confirm', [
            'action'        => route('admin.leaves.destroy', $row->id),
            'method'        => 'DELETE',
            'icon'          => 'fa-solid fa-trash',
            'title'         => __('global.delete'),
            'confirmTitle'  => 'تأكيد الحذف',
            'confirmText'   => 'هل أنت متأكد من نقل الإجازة إلى سلة المحذوفات؟',
            'confirmButton' => 'نعم، احذف',
        ])
    @endif
</div>
