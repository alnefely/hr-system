{{--
    أزرار عرض/تعديل/حذف في جداول DataTables — كل زرار بيظهر لو صلاحيته موجودة.

    من الكنترولر:
        ->addColumn('actions', fn ($row) => view('admin.partials.table-actions.crud', [
            'row'    => $row,
            'routes' => 'admin.sales',     // بادئة أسماء المسارات: .show / .edit / .destroy
            'can'    => 'sale',            // بادئة الصلاحيات: _show / _update / _delete
            'show'   => true,              // اختياري: زرار العرض (افتراضياً مخفي)
        ])->render())

    أي زرار إضافي (تعديل عبر نافذة منبثقة مثلاً) بيتمرّر كـ HTML جاهز في $before.
--}}
<div class="d-inline-flex gap-1">
    {!! $before ?? '' !!}

    @if (($show ?? false) && admin_can("{$can}_show"))
        <a href="{{ route("{$routes}.show", $row->id) }}" class="btn btn-sm btn-icon btn-label-info" data-bs-toggle="tooltip" title="{{ __('global.view') }}" aria-label="{{ __('global.view') }}">
            <i class="fa-solid fa-eye"></i>
        </a>
    @endif

    @if (($edit ?? true) && admin_can("{$can}_update"))
        <a href="{{ route("{$routes}.edit", $row->id) }}" class="btn btn-sm btn-icon btn-label-primary" data-bs-toggle="tooltip" title="{{ __('global.edit') }}" aria-label="{{ __('global.edit') }}">
            <i class="fa-solid fa-pen-to-square"></i>
        </a>
    @endif

    @if (($delete ?? true) && admin_can("{$can}_delete"))
        @include('admin.partials.table-actions.confirm', [
            'action'        => route("{$routes}.destroy", $row->id),
            'method'        => 'DELETE',
            'icon'          => 'fa-solid fa-trash',
            'variant'       => 'btn-label-danger',
            'title'         => __('global.delete'),
            'confirmText'   => $deleteText ?? __('global.confirm_delete'),
            'confirmButton' => __('global.delete'),
        ])
    @endif
</div>
