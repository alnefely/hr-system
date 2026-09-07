<?php

return [
    'trash'          => 'Trash',
    'back'           => 'Back to List',
    'title'          => 'Leaves & Vacations',
    'singular'       => 'Leave Request',
    'create_title'   => 'Submit Leave Request',
    'balances_title' => 'Employee Leave Balances',
    'trash_title'    => 'Leaves Trash',

    'fields' => [
        'employee'         => 'Employee',
        'leave_type'       => 'Leave Type',
        'start_date'       => 'Start Date',
        'end_date'         => 'End Date',
        'days_count'       => 'Days Count',
        'reason'           => 'Reason',
        'status'           => 'Status',
        'approved_by'      => 'Approved By',
        'approved_at'      => 'Approved At',
        'attachment'       => 'Attachment / Medical Report',
        'rejection_reason' => 'Rejection Reason',
        'annual_balance'   => 'Annual Entitlement',
        'casual_balance'   => 'Casual Entitlement',
        'sick_balance'     => 'Sick Entitlement',
        'used'             => 'Used',
        'remaining'        => 'Remaining Balance',
        'used_unpaid'      => 'Unpaid Leave Days',
        'year'             => 'Year',
    ],

    'type_enums' => [
        'annual'   => 'Annual Leave',
        'casual'   => 'Casual Leave',
        'sick'     => 'Sick Leave',
        'unpaid'   => 'Unpaid Leave',
    ],

    'status_enums' => [
        'pending'  => 'Pending Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'actions' => [
        'add_new'       => 'Submit Leave Request',
        'view_balances' => 'Leave Balances Table',
        'approve'       => 'Approve Leave',
        'reject'        => 'Reject Request',
    ],

    'messages' => [
        'restored'        => 'Leave record restored successfully',
        'force_deleted'   => 'Leave permanently deleted successfully',
        'created'         => 'Leave request submitted successfully',
        'approved'        => 'Leave request approved and days deducted successfully',
        'rejected'        => 'Leave request rejected',
        'deleted'         => 'Leave request moved to trash successfully',
        'balance_updated' => 'Leave balance updated successfully',
    ],

    'errors' => [
        'not_pending'          => 'Cannot approve a request that is not pending or already resolved.',
        'insufficient_annual'  => 'Remaining annual leave balance (:remaining days) is insufficient for requested (:requested days).',
        'insufficient_casual'  => 'Remaining casual leave balance (:remaining days) is insufficient for requested (:requested days).',
        'insufficient_sick'    => 'Remaining sick leave balance (:remaining days) is insufficient for requested (:requested days).',
        'overlap'              => 'Schedule conflict! Employee already has an existing leave request in this date range.',
        'invalid_days_count'   => 'Requested days count (:days) exceeds maximum possible days between start and end dates (:max days).',
    ],

    'filters' => [
        'employee'      => 'Filter by employee',
        'all_employees' => '-- All employees --',
        'all_types'     => '-- All types --',
        'all_statuses'  => '-- All statuses --',
    ],

    'hints' => [
        'employee_filter' => 'Pick an employee to see only their leaves, or leave it empty to see everyone',
    ],

];
