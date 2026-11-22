<?php

/*
|--------------------------------------------------------------------------
| Task management
|--------------------------------------------------------------------------
*/

return [

    'title'    => 'Tasks',
    'singular' => 'Task',

    'create_title' => 'New Task',
    'edit_title'   => 'Edit Task',
    'trash'        => 'Tasks Trash',
    'my_tasks'     => 'My Tasks',

    'fields' => [
        'code'                       => 'Code',
        'title'                      => 'Task title',
        'description'                => 'Description',
        'branch'                     => 'Branch',
        'project'                    => 'Project',
        'parent'                     => 'Parent task',
        'status'                     => 'Status',
        'priority'                   => 'Priority',
        'starts_at'                  => 'Start date',
        'due_at'                     => 'Due date',
        'assignees'                  => 'Assignees',
        'creator'                    => 'Created by',
        'progress'                   => 'Progress',
        'expected_progress'          => 'Expected progress',
        'progress_source'            => 'Progress calculation',
        'progress_update_interval'   => 'Progress update required',
        'progress_update_every_days' => 'Every (days)',
        'next_progress_update'       => 'Next update',
        'estimated_hours'            => 'Estimated time (hours)',
        'requires_review'            => 'Requires review before closing',
        'labels'                     => 'Labels',
        'checklist'                  => 'Checklist',
        'subtasks'                   => 'Subtasks',
        'created_at'                 => 'Created at',
        'completed_at'               => 'Completed at',
        'last_activity'              => 'Last activity',
        'note'                       => 'Note',
        'reason'                     => 'Reason',
        'deleted_at'                 => 'Deleted at',
    ],

    'actions' => [
        'add_new'         => 'New Task',
        'edit'            => 'Edit',
        'view'            => 'View',
        'delete'          => 'Delete',
        'back'            => 'Back to tasks',
        'start'           => 'Start work',
        'send_to_review'  => 'Submit for review',
        'complete'        => 'Complete task',
        'return'          => 'Return to work',
        'cancel'          => 'Cancel task',
        'reopen'          => 'Reopen',
        'decline'         => 'Decline task',
        'manage_assignees'=> 'Edit assignees',
        'update_progress' => 'Update progress',
        'add_step'        => 'Add step',
        'add_subtask'     => 'Add subtask',
        'add_step_row'    => 'Another step',
    ],

    'filters' => [
        'search'         => 'Search by title or code',
        'all_projects'   => 'All projects',
        'no_project'     => 'No project',
        'all_employees'  => 'All employees',
        'all_statuses'   => 'All statuses',
        'open'           => 'Open',
        'overdue'        => 'Overdue',
        'all_priorities' => 'All priorities',
        'due_from'       => 'Due from',
        'due_to'         => 'Due to',
        'apply'          => 'Apply',
    ],

    'tabs' => [
        'assigned' => 'Assigned to me',
        'created'  => 'Created by me',
        'team'     => 'My team',
        'watching' => 'Watching',
    ],

    'stats' => [
        'open'        => 'Open',
        'in_progress' => 'In progress',
        'in_review'   => 'In review',
        'overdue'     => 'Overdue',
    ],

    'labels_text' => [
        'overdue'       => 'Overdue',
        'due_today'     => 'Due today',
        'blocked'       => 'Waiting on another task',
        'no_due'        => 'No due date',
        'no_assignees'  => 'No assignees',
        'steps_done'    => ':done of :total steps',
        'subtasks_done' => ':done of :total subtasks',
        'expected_hint' => 'Should be at :expected% by today',
        'automatic'     => 'Calculated automatically',
        'empty'         => 'No tasks here',
        'empty_hint'    => 'Tasks you are assigned or create will show up here.',
        'no_steps'      => 'No steps yet',
        'no_subtasks'   => 'No subtasks',
        'no_activity'   => 'No activity yet',
        'timeline'      => 'Task timeline',
        'progress_log'  => 'Progress updates',
        'details'       => 'Details',
        'you'           => 'You',
        'system'        => 'System',
        'every_days'    => 'Every :days days',
    ],

    'activity' => [
        'created'           => 'created the task',
        'updated'           => 'updated the task details',
        'status_changed'    => 'changed status from :from to :to',
        'assigned'          => 'assigned :names',
        'unassigned'        => 'unassigned :names',
        'declined'          => 'declined the task',
        'progress_updated'  => 'updated progress from :from% to :to%',
        'checklist_added'   => 'added a step: :title',
        'checklist_done'    => 'completed a step: :title',
        'checklist_undone'  => 'reopened a step: :title',
        'checklist_removed' => 'removed a step: :title',
        'deleted'           => 'deleted the task',
        'restored'          => 'restored the task',
    ],

    'messages' => [
        'created'           => 'Task created successfully',
        'updated'           => 'Task updated',
        'deleted'           => 'Task deleted',
        'restored'          => 'Task restored',
        'force_deleted'     => 'Task permanently deleted',
        'status_changed'    => 'Task status changed',
        'progress_updated'  => 'Progress updated',
        'assignees_updated' => 'Assignees updated',
        'declined'          => 'You declined the task',
        'checklist_saved'   => 'Step saved',
        'checklist_removed' => 'Step removed',
    ],

    'errors' => [
        'forbidden'          => 'You are not allowed to do this on the task',
        'branch_required'    => 'The task branch is required',
        'invalid_transition' => 'A task cannot move from ":from" to ":to"',
        'blocked'            => 'The task is waiting on another task to finish first',
        'needs_review'       => 'This task needs review — submit it for review instead of closing it',
        'closed'             => 'The task is closed — reopen it first',
        'progress_automatic' => 'This task progress is calculated automatically from its steps or subtasks',
        'invalid_assignee'   => 'A selected employee cannot be assigned to this task (another branch and not a project member)',
    ],

    'hints' => [
        'title'                    => 'A short sentence describing the work, e.g. Prepare a quote for client X.',
        'project'                  => 'Optional: pick a project if the task belongs to one; leave empty for a daily task.',
        'assignees'                => 'You can pick several employees — each one gets notified.',
        'priority'                 => 'Urgent and high tasks appear first.',
        'due_at'                   => 'The deadline. After it the task shows as overdue.',
        'progress_source'          => 'Manual: the employee enters the progress. Automatic: progress comes from steps or subtasks.',
        'progress_update_interval' => 'For long tasks, set how often the employee must update progress and the system will remind them.',
        'requires_review'          => 'When on, the assignee submits the task and the creator (or direct manager) approves it.',
        'estimated_hours'          => 'Expected effort — compared to actual time in reports.',
        'checklist'                => 'Small steps to tick off while working.',
        'employee_filter'          => 'Tasks assigned to this employee.',
    ],

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
