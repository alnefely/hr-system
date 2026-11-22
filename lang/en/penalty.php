<?php

return [
    'title'       => 'Penalties & Disciplinary Actions',
    'singular'    => 'Penalty',
    'add_new'     => 'Record Disciplinary Action',
    'trash_title' => 'Penalties Trash',

    'fields' => [
        'employee'      => 'Employee',
        'penalty_type'  => 'Penalty Type',
        'title'         => 'Action Title / Reason',
        'amount'        => 'Deduction Amount',
        'days_count'    => 'Deducted Days',
        'penalty_date'  => 'Violation Date',
        'status'        => 'Status',
        'notes'         => 'Investigation Notes',
            'deduction_amount' => 'Deduction Amount',
        'reason' => 'Reason & Violation',
        'created_by' => 'Recorded By',
],

    'status_enums' => [
        'pending'  => 'Pending Decision',
        'approved' => 'Approved & Deducted',
        'rejected' => 'Rejected',
    ],

    'messages' => [
        'reject_hint' => 'The penalty will not be deducted, and the rejection reason is recorded for audit.',
        'confirm_approve' => 'Approve this penalty? It will be deducted from the employee salary in this month payroll.',
        'approved' => 'Penalty approved and will be deducted in this month payroll.',
        'rejected' => 'Penalty rejected and will not be deducted.',
        'not_pending' => 'This action cannot be applied to a penalty that was already decided.',
        'cannot_modify_approved' => 'An approved penalty cannot be edited or deleted because it entered the payroll.',
        'created' => 'Disciplinary penalty recorded successfully',
        'updated' => 'Penalty updated successfully',
        'deleted' => 'Penalty moved to trash successfully',
        'restored'=> 'Penalty restored successfully',
    ],

    'export' => [
        'penalty_type'     => 'Penalty Type',
        'days_deducted'    => 'Days Deducted',
        'deduction_amount' => 'Deduction Amount',
        'penalty_date'     => 'Penalty Date',
        'reason'           => 'Reason & Violation',
        'admin_notes'      => 'Administrative Notes',
        'file_title'       => 'Administrative Penalties & Deductions Log',
    ],
    'create_title' => 'Record New Penalty',
    'type_enums' => [
        'days_deduction' => 'Working Days Deduction',
        'amount_deduction' => 'Direct Financial Deduction',
        'warning' => 'Written Warning',
    ],
    'actions' => [
        'reject' => 'Reject Penalty',
        'add_new' => 'Record New Penalty',
    ],

    // شرح الحقول (أيقونة ؟ جنب العنوان)
    'hints' => [
        'penalty_type'     => 'Days: days × daily rate. Amount: a fixed amount. Warning: recorded in the file with no deduction.',
        'days_count'       => 'Multiplied by the daily rate (basic ÷ 30, or the employee’s custom daily rate). Half days allowed.',
        'deduction_amount' => 'Deducted as-is from net salary.',
        'penalty_date'     => 'Determines which month’s payroll deducts it.',
        'reason'           => 'Shown to the employee in their portal — write it clearly.',
    ],

];
