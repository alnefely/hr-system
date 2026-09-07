<?php

return [
    'title'          => 'Expense Categories',
    'singular'       => 'Expense Category',
    'create_title'   => 'Add Expense Category',
    'edit_title'     => 'Edit Expense Category',

    'fields' => [
        'name'           => 'Category Name',
        'code'           => 'Category Code',
        'description'    => 'Description & Details',
        'is_active'      => 'Status',
        'expenses_count' => 'Recorded Expenses Count',
    ],

    'enums' => [
        'active'   => 'Active',
        'inactive' => 'Inactive',
    ],

    'actions' => [
        'add_new' => 'Add New Category',
    ],

    'messages' => [
        'created'                    => 'Expense category added successfully',
        'updated'                    => 'Expense category updated successfully',
        'deleted'                    => 'Expense category deleted successfully',
        'cannot_delete_has_expenses' => 'Cannot delete category with associated expenses.',
            'restored' => 'Record restored successfully',
        'force_deleted' => 'Permanently deleted successfully',
],
    'trash' => 'Trash',
    'back' => 'Back to list',
];
