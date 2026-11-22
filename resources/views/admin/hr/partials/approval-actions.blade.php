{{--
    إجراءات الجزاءات والمكافآت: اعتماد/رفض للمعلّق، وحذف قبل ما الحركة تدخل المسير.

    @include('admin.hr.partials.approval-actions', [
        'row'    => $row,
        'module' => 'penalty',            // بادئة الصلاحيات وملف الترجمة
        'routes' => 'admin.penalties',    // بادئة أسماء المسارات
        'approveTitle' => 'اعتماد الجزاء',
    ])
--}}
<div class="d-inline-flex gap-1">
    {{-- الاعتماد والرفض للمعلّق بس --}}
    @if ($row->isPending())
        @if (admin_can("{$module}_approve"))
            @include('admin.partials.table-actions.confirm', [
                'action'             => route("{$routes}.approve", $row->id),
                'icon'               => 'fa-solid fa-check',
                'variant'            => 'btn-label-success',
                'title'              => __('global.approve'),
                'confirmTitle'       => $approveTitle,
                'confirmText'        => __("{$module}.messages.confirm_approve"),
                'swalIcon'           => 'question',
                'confirmButton'      => __('global.approve'),
                'confirmButtonClass' => 'btn-success',
            ])
        @endif

        @if (admin_can("{$module}_reject"))
            <button type="button" class="btn btn-sm btn-icon btn-label-danger reject-btn" data-id="{{ $row->id }}" title="{{ __('global.reject') }}" aria-label="{{ __('global.reject') }}">
                <i class="fa-solid fa-xmark"></i>
            </button>
        @endif
    @endif

    {{-- الحذف ممنوع بعد الاعتماد — الحركة بقت في المسير --}}
    @if (admin_can("{$module}_delete") && $row->isEditable())
        @include('admin.partials.table-actions.confirm', [
            'action'      => route("{$routes}.destroy", $row->id),
            'method'      => 'DELETE',
            'icon'        => 'fa-solid fa-trash',
            'title'       => __('global.delete'),
            'confirmText' => __('global.confirm_delete'),
        ])
    @endif
</div>
