{{-- إجراءات الموردين: التعديل في نافذة منبثقة، والحذف بتأكيد (كان بيحذف فوراً) --}}
@include('admin.partials.table-actions.crud', [
    'row'    => $row,
    'routes' => 'admin.vendors',
    'can'    => 'vendor',
    'edit'   => false,
    'before' => admin_can('vendor_update')
        ? '<button type="button" class="btn btn-sm btn-icon btn-label-primary edit-vendor-btn"'
            . ' data-id="' . $row->id . '"'
            . ' data-code="' . e($row->code) . '"'
            . ' data-name="' . e($row->name) . '"'
            . ' data-company="' . e($row->company_name) . '"'
            . ' data-contact="' . e($row->contact_person) . '"'
            . ' data-phone="' . e($row->phone) . '"'
            . ' data-email="' . e($row->email) . '"'
            . ' data-tax="' . e($row->tax_number) . '"'
            . ' data-active="' . ($row->is_active ? '1' : '0') . '"'
            . ' data-notes="' . e($row->notes) . '"'
            . ' title="' . e(__('global.edit')) . '" aria-label="' . e(__('global.edit')) . '"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button>'
        : '',
])
