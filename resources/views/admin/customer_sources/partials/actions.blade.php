{{-- إجراءات مصادر العملاء: التعديل في نافذة منبثقة --}}
@include('admin.partials.table-actions.crud', [
    'row'    => $row,
    'routes' => 'admin.customer-sources',
    'can'    => 'customer_source',
    'edit'   => false,
    'before' => admin_can('customer_source_update')
        ? '<button type="button" class="btn btn-sm btn-icon btn-label-primary edit-source-btn"'
            . ' data-id="' . $row->id . '"'
            . ' data-code="' . e($row->code) . '"'
            . ' data-name="' . e($row->name) . '"'
            . ' data-description="' . e($row->description) . '"'
            . ' data-sort="' . (int) $row->sort_order . '"'
            . ' data-active="' . ($row->is_active ? '1' : '0') . '"'
            . ' title="' . e(__('global.edit')) . '" aria-label="' . e(__('global.edit')) . '"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button>'
        : '',
])
