{{-- عمود الإجراءات في جدول السلف --}}
@php
    $currency = $row->employee?->branch?->currency ?? 'EGP';
    $isApproved = in_array($row->status, [\App\Enums\AdvanceStatus::Approved->value, \App\Enums\AdvanceStatus::Paid->value], true);
@endphp
<div class="d-inline-flex gap-1">
    {{-- سجل الدفعات وسداد دفعة --}}
    <button type="button" class="btn btn-sm btn-icon btn-label-info view-repayments-btn"
        data-id="{{ $row->id }}"
        data-employee="{{ $row->employee?->name }}"
        data-total="{{ $row->amount }}"
        data-paid="{{ $row->paid_amount }}"
        data-waived="{{ $row->waived_amount }}"
        data-remaining="{{ $row->remaining_amount }}"
        data-currency="{{ $currency }}"
        title="سجل الدفعات وسداد دفعة">
        <i class="fa-solid fa-receipt"></i>
    </button>

    {{-- الإعفاء للسلفة المعتمدة اللي لسه عليها رصيد بس — نفس شرط AdvanceService --}}
    @if ($isApproved && $row->remaining_amount > 0)
        <button type="button" class="btn btn-sm btn-icon btn-label-warning waive-advance-btn"
            data-id="{{ $row->id }}"
            data-employee="{{ $row->employee?->name }}"
            data-remaining="{{ $row->remaining_amount }}"
            data-currency="{{ $currency }}"
            title="إسقاط / إعفاء سلفة للموظف">
            <i class="fa-solid fa-hand-holding-heart"></i>
        </button>
    @endif

    @if ($row->status === \App\Enums\AdvanceStatus::Pending->value)
        @if (admin_can('advance_approve'))
            @include('admin.partials.table-actions.confirm', [
                'action'             => route('admin.advances.approve', $row->id),
                'icon'               => 'fa-solid fa-check',
                'variant'            => 'btn-label-success',
                'title'              => __('advance.approve'),
                'confirmTitle'       => 'اعتماد السلفة',
                'confirmText'        => 'هل أنت متأكد من اعتماد السلفة؟',
                'swalIcon'           => 'question',
                'confirmButton'      => 'نعم، اعتمد',
                'confirmButtonClass' => 'btn-success',
            ])
        @endif

        @if (admin_can('advance_reject'))
            @include('admin.partials.table-actions.confirm', [
                'action'        => route('admin.advances.reject', $row->id),
                'icon'          => 'fa-solid fa-xmark',
                'variant'       => 'btn-label-danger',
                'title'         => __('advance.reject'),
                'confirmTitle'  => 'رفض السلفة',
                'confirmText'   => 'هل أنت متأكد من رفض طلب السلفة؟',
                'confirmButton' => 'نعم، ارفض',
            ])
        @endif
    @endif

    @if (admin_can('advance_delete') && (float) $row->paid_amount <= 0 && (float) $row->waived_amount <= 0)
        @include('admin.partials.table-actions.confirm', [
            'action'        => route('admin.advances.destroy', $row->id),
            'method'        => 'DELETE',
            'icon'          => 'fa-solid fa-trash',
            'title'         => __('global.delete'),
            'confirmTitle'  => 'تأكيد الحذف',
            'confirmText'   => 'هل أنت متأكد من حذف هذا السجل؟',
            'confirmButton' => 'نعم، احذف',
        ])
    @endif
</div>
