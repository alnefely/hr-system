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

    // شرح الحقول (أيقونة ؟ جنب العنوان)
    'hints' => [
        'code'      => 'Prefix for the branch employees’ codes (e.g. CAI → CAI-0001). Avoid changing it after adding employees.',
        'currency'  => 'Currency of the branch salaries, expenses and sales (e.g. EGP, SAR). All branch amounts are shown in it.',
        'timezone'  => 'The branch local timezone in international format (e.g. Africa/Cairo) — for reference.',
        'is_active' => 'An inactive branch is hidden from selection lists (new employee, expense, payroll); its existing data stays.',
    ],

];
