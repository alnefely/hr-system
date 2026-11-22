<?php

/*
|--------------------------------------------------------------------------
| Task management
|--------------------------------------------------------------------------
*/

return [

    'title'    => 'Tasks',
    'singular' => 'Task',

    'status_enums' => [
        'new'         => 'New',
        'in_progress' => 'In Progress',
        'in_review'   => 'In Review',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
    ],

    'priority_enums' => [
        'low'    => 'Low',
        'medium' => 'Medium',
        'high'   => 'High',
        'urgent' => 'Urgent',
    ],

    'progress_source_enums' => [
        'manual'    => 'Manual (employee updates)',
        'checklist' => 'Automatic from checklist',
        'subtasks'  => 'Automatic from subtasks',
    ],

    'progress_interval_enums' => [
        'none'   => 'Not required',
        'daily'  => 'Daily',
        'weekly' => 'Weekly',
        'custom' => 'Every set number of days',
    ],

    'progress_health' => [
        'ahead'    => 'Ahead of plan',
        'on_track' => 'On track',
        'behind'   => 'Behind plan',
    ],

    'assignee_status_enums' => [
        'active'   => 'Assigned',
        'declined' => 'Declined',
    ],

    'review_status_enums' => [
        'pending'  => 'Awaiting review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'frequency_enums' => [
        'daily'   => 'Daily',
        'weekly'  => 'Weekly',
        'monthly' => 'Monthly',
        'yearly'  => 'Yearly',
    ],

    'reminder_type_enums' => [
        'due_soon'        => 'Due date approaching',
        'overdue'         => 'Overdue task',
        'progress_update' => 'Progress update',
        'custom'          => 'Custom reminder',
    ],

];
