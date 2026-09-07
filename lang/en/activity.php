<?php

/*
|--------------------------------------------------------------------------
| System Activity Log (Audit Log)
|--------------------------------------------------------------------------
*/

return [

    'title'        => 'System Activity Log',
    'singular'     => 'Activity',
    'module_title' => ':module Activity Log',
    'system'       => 'System',

    'events' => [
        'created'       => 'Created',
        'updated'       => 'Updated',
        'deleted'       => 'Deleted',
        'restored'      => 'Restored',
        'force_deleted' => 'Permanently Deleted',
    ],

    'fields' => [
        'event'         => 'Operation',
        'subject'       => 'Module',
        'subject_name'  => 'Record',
        'subject_id'    => 'Record ID',
        'causer'        => 'User',
        'changes'       => 'Changes',
        'changes_count' => 'Fields',
        'ip_address'    => 'IP Address',
        'user_agent'    => 'Browser & Device',
        'url'           => 'URL',
        'method'        => 'Request Method',
        'created_at'    => 'Date & Time',
        'field'         => 'Field',
        'old_value'     => 'Before',
        'new_value'     => 'After',
    ],

    'filters' => [
        'subject'   => 'Module',
        'event'     => 'Operation',
        'causer'    => 'User',
        'date_from' => 'Date From',
        'date_to'   => 'Date To',
        'all'       => 'All',
        'apply'     => 'Apply Filters',
        'reset'     => 'Reset',
    ],

    'stats' => [
        'total'      => 'Total Activities',
        'total_hint' => 'All activities recorded across the system',
        'today'      => "Today's Activities",
        'today_hint' => 'Operations performed during the current day',
        'created'    => 'Create Operations',
        'updated'    => 'Update Operations',
        'deleted'    => 'Delete Operations',
    ],

    'actions' => [
        'details'     => 'View Details',
        'export'      => 'Export to Excel',
        'view_module' => 'View Module Log',
        'back_to_all' => 'Back to Full Log',
    ],

    'details' => [
        'title'        => 'Activity Details',
        'operation'    => 'Operation Data',
        'changes'      => 'Changed Fields',
        'request_info' => 'Request Data',
        'no_changes'   => 'No field details recorded for this activity.',
        'loading'      => 'Loading details...',
        'load_failed'  => 'Failed to load activity details.',
    ],

    'hints' => [
        'read_only' => 'This log is read-only and cannot be edited or deleted from the interface, to keep the audit trail trustworthy.',
        'retention' => 'Records older than :days days are removed automatically by the scheduled prune command.',
        'sensitive' => 'Sensitive fields (passwords, bank accounts, national IDs) are never recorded.',
    ],

];
