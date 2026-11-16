{{-- عمود الإجراءات في جدول المسيرات --}}
<div class="d-inline-flex gap-1">
    <a href="{{ route('admin.payrolls.show', $row->id) }}" class="btn btn-sm btn-icon btn-label-primary" title="{{ __('payroll.actions.details') }}">
        <i class="fa-solid fa-eye"></i>
    </a>

    @if (\App\Enums\PayrollStatus::fromValue($row->status)?->isEditable() && admin_can('payroll_delete'))
        @include('admin.partials.table-actions.confirm', [
            'action'        => route('admin.payrolls.destroy', $row->id),
            'method'        => 'DELETE',
            'icon'          => 'fa-solid fa-trash',
            'title'         => __('global.delete'),
            'confirmText'   => __('payroll.messages.confirm_delete'),
        ])
    @endif
</div>
