<?php

return [
    'title'       => 'Permissions & Short Leaves',
    'singular'    => 'Permission Request',
    'create_title'=> 'New Permission Request',

    'fields' => [
        'employee'        => 'Employee',
        'permission_date' => 'Permission Date',
        'start_time'      => 'Departure Time',
        'end_time'        => 'Return Time',
        'hours_count'     => 'Duration (Hours)',
        'reason'          => 'Reason',
        'type'            => 'Permission Type',
        'status'          => 'Status',
            'approved_by' => 'Approved By',
],

    'types' => [
        'personal' => 'Personal Permission',
        'official' => 'Official Duty / Mission',
        'medical'  => 'Medical Appointment',
    ],

    'status_enums' => [
        'pending'  => 'Pending Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'messages' => [
        'not_pending' => 'This action cannot be applied to a request that was already decided.',
        'created'  => 'Permission request submitted successfully',
        'approved' => 'Permission approved successfully',
        'rejected' => 'Permission rejected',
            'restored' => 'Record restored successfully',
        'force_deleted' => 'Permanently deleted successfully',
        'deleted' => 'Permission deleted successfully',
],
    'trash' => 'Trash',
    'back' => 'Back to list',
    'actions' => [
        'add_new' => 'Record New Permission',
        'approve' => 'Approve',
        'reject' => 'Reject',
    ],
];
