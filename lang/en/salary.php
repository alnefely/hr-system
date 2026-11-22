<?php

return [
    'title'         => 'Salary History',
    'singular'      => 'Salary Record',
    'add_new'       => 'Add Salary Revision',
    'edit'          => 'Edit Salary Record',
    'trash'         => 'Salary History Trash',
    'back'          => 'Back to Employee Profile',
    'notes'         => ['edited_from_profile' => 'Edited directly from the employee profile'],

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

    // شرح الحقول (أيقونة ؟ جنب العنوان)
    'hints' => [
        'basic_salary'   => 'The new basic. The latest effective record becomes the salary used in payroll and for daily/hourly rates.',
        'allowances'     => 'Total allowances at the time of this change — for history. Actual allowances are managed in the employee file.',
        'effective_date' => 'When the new salary takes effect. The record with the latest date is the current one.',
        'change_reason'  => 'Shown in the salary history and to the employee in their portal.',
    ],

];
