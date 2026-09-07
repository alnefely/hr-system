<?php

return [
    'title'       => 'Rewards & Bonuses',
    'singular'    => 'Reward',
    'add_new'     => 'Grant Reward / Bonus',
    'trash_title' => 'Rewards Trash',

    'fields' => [
        'employee'    => 'Employee',
        'reward_type' => 'Reward Type',
        'title'       => 'Reward Title / Reason',
        'amount'      => 'Reward Amount',
        'reward_date' => 'Grant Date',
        'status'      => 'Status',
        'notes'       => 'Appreciation Notes',
            'reason' => 'Reason',
        'created_by' => 'Recorded By',
        'approved_by' => 'Approved By',
],

    'status_enums' => [
        'pending'  => 'Pending Approval',
        'approved' => 'Approved for Payroll',
        'paid'     => 'Disbursed in Payroll',
        'rejected' => 'Rejected',
    ],

    'messages' => [
        'reject_hint' => 'The reward will not be paid, and the rejection reason is recorded for audit.',
        'confirm_approve' => 'Approve this reward? It will be paid to the employee in this month payroll.',
        'approved' => 'Reward approved and will be paid in this month payroll.',
        'rejected' => 'Reward rejected and will not be paid.',
        'not_pending' => 'This action cannot be applied to a reward that was already decided.',
        'cannot_self_approve' => 'You cannot approve a reward you recorded yourself — another officer must review it.',
        'cannot_modify_approved' => 'An approved reward cannot be edited or deleted because it entered the payroll.',
        'created' => 'Reward granted successfully',
        'updated' => 'Reward updated successfully',
        'deleted' => 'Reward moved to trash successfully',
        'restored'=> 'Reward restored successfully',
    ],

    'export' => [
        'reward_type'  => 'Entitlement Type',
        'reward_title' => 'Reward Title / Description',
        'reward_date'  => 'Entitlement Date',
        'file_title'   => 'Rewards & Additional Allowances Log',
    ],
    'create_title' => 'Add New Reward',
    'type_enums' => [
        'bonus' => 'Bonus',
        'incentive' => 'Incentive',
        'allowance' => 'Allowance',
        'appreciation' => 'Appreciation Award',
    ],
    'actions' => [
        'reject' => 'Reject Reward',
    ],
];
