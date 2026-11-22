{{-- إجراءات قنوات البيع: التعديل في نافذة منبثقة --}}
@include('admin.partials.table-actions.crud', [
    'row'    => $row,
    'routes' => 'admin.sales-channels',
    'can'    => 'sales_channel',
    'edit'   => false,
    'before' => admin_can('sales_channel_update')
        ? '<button type="button" class="btn btn-sm btn-icon btn-label-primary edit-channel-btn"'
            . ' data-id="' . $row->id . '"'
            . ' data-code="' . e($row->code) . '"'
            . ' data-name="' . e($row->name) . '"'
            . ' data-type="' . e($row->type) . '"'
            . ' data-active="' . ($row->is_active ? '1' : '0') . '"'
            . ' data-notes="' . e($row->notes) . '"'
            . ' title="' . e(__('global.edit')) . '" aria-label="' . e(__('global.edit')) . '"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button>'
        : '',
])
