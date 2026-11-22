{{-- إجراءات الروابط المختصرة --}}
@include('admin.partials.table-actions.crud', [
    'row'        => $row,
    'routes'     => 'admin.links',
    'can'        => 'link',
    'deleteText' => __('link.confirm.delete_text', ['link' => $row->short_code]),
    'before'     => '<a href="' . e($row->short_url) . '" target="_blank" rel="noopener noreferrer"'
        . ' class="btn btn-sm btn-icon btn-label-info waves-effect" title="' . e(__('link.actions.open')) . '" aria-label="' . e(__('link.actions.open')) . '">'
        . '<i class="fa-solid fa-arrow-up-right-from-square"></i></a>'
        . (admin_can('link_stats')
            ? '<a href="' . route('admin.links.stats', $row->id) . '" class="btn btn-sm btn-icon btn-label-success waves-effect"'
                . ' title="' . e(__('link.actions.stats')) . '" aria-label="' . e(__('link.actions.stats')) . '"><i class="fa-solid fa-chart-line"></i></a>'
            : ''),
])
