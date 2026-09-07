<?php

return [
    'title'         => 'Salary History',
    'singular'      => 'Salary Record',
    'add_new'       => 'Add Salary Revision',
    'edit'          => 'Edit Salary Record',
    'trash'         => 'Salary History Trash',
    'back'          => 'Back to Employee Profile',

    'fields' => [
        'employee'       => 'Employee',
        'basic_salary'   => 'Basic Salary',
        'allowances'     => 'Allowances',
        'total_salary'   => 'Total Salary',
        'currency'       => 'Currency',
        'effective_date' => 'Effective Date',
        'change_reason'  => 'Reason of Change',
        'approved_by'    => 'Approved By',
        'notes'          => 'Notes & Details',
        'created_at'     => 'Logged At',
        'deleted_at'     => 'Deleted At',
    ],

    'reasons' => [
        'hiring'        => 'New Hiring',
        'promotion'     => 'Promotion',
        'annual_raise'  => 'Annual / Periodic Raise',
        'settlement'    => 'Settlement',
        'other'         => 'Other Reasons',
    ],

    'messages' => [
        'created'       => 'Salary revision saved successfully',
        'updated'       => 'Salary record updated successfully',
        'deleted'       => 'Salary record moved to trash successfully',
        'restored'      => 'Salary record restored successfully',
        'force_deleted' => 'Salary record permanently deleted',
        'empty_trash'   => 'Salary history trash is empty',
    ],
];
