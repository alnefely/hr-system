<?php

return [
    'title'         => 'Branches',
    'singular'      => 'Branch',
    'add_new'       => 'Add New Branch',
    'edit'          => 'Edit Branch',
    'trash'         => 'Branches Trash',
    'back'          => 'Back to Branches',
    'all_branches'  => 'All Branches',

    'fields' => [
        'name'        => 'Branch Name',
        'code'        => 'Branch Code',
        'country'     => 'Country',
        'city'        => 'City',
        'address'     => 'Address',
        'phone'       => 'Phone',
        'email'       => 'Email',
        'currency'    => 'Currency',
        'timezone'    => 'Timezone',
        'status'      => 'Status',
        'active'      => 'Active',
        'inactive'    => 'Inactive',
        'employees_count' => 'Employees Count',
        'created_at'  => 'Created At',
        'deleted_at'  => 'Deleted At',
    ],

    'countries' => [
        'Egypt'        => 'Egypt',
        'Saudi Arabia' => 'Saudi Arabia',
        'Oman'         => 'Oman',
    ],

    'messages' => [
        'created'       => 'Branch created successfully',
        'updated'       => 'Branch updated successfully',
        'deleted'       => 'Branch moved to trash successfully',
        'restored'      => 'Branch restored successfully',
        'force_deleted' => 'Branch permanently deleted',
        'cannot_delete' => 'Cannot delete branch because it contains active employees',
        'empty_trash'   => 'Branches trash is currently empty',
    ],
];
