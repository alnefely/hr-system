{{-- إجراءات تصنيفات المصاريف: التعديل في نافذة منبثقة --}}
@include('admin.partials.table-actions.crud', [
    'row'    => $row,
    'routes' => 'admin.expense-categories',
    'can'    => 'expense_category',
    'edit'   => false,
    'before' => admin_can('expense_category_update')
        ? '<button type="button" class="btn btn-sm btn-icon btn-label-primary edit-category-btn waves-effect"'
            . ' data-id="' . $row->id . '"'
            . ' data-name="' . e($row->name) . '"'
            . ' data-code="' . e($row->code ?? '') . '"'
            . ' data-description="' . e($row->description ?? '') . '"'
            . ' data-active="' . ($row->is_active ? 1 : 0) . '"'
            . ' title="' . e(__('global.edit')) . '"><i class="fa-solid fa-pen-to-square"></i></button>'
        : '',
])
