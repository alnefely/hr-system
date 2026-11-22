<?php

/*
|--------------------------------------------------------------------------
| Projects
|--------------------------------------------------------------------------
*/

return [

    'title'    => 'Projects',
    'singular' => 'Project',

    'create_title'   => 'New Project',
    'edit_title'     => 'Edit Project',
    'trash'          => 'Projects Trash',
    'my_projects'    => 'My Projects',
    'templates'      => 'Project Templates',
    'template'       => 'Template',

    'fields' => [
        'code'        => 'Code',
        'name'        => 'Project name',
        'description' => 'Description',
        'color'       => 'Color',
        'branch'      => 'Branch',
        'manager'     => 'Project manager',
        'status'      => 'Status',
        'priority'    => 'Priority',
        'start_date'  => 'Start date',
        'due_date'    => 'End date',
        'progress'    => 'Progress',
        'members'     => 'Project members',
        'role'        => 'Role',
        'tasks'       => 'Tasks',
        'template'    => 'Create from template',
        'deleted_at'  => 'Deleted at',
        'completed_at'=> 'Completed at',
        'template_name'      => 'Template name',
        'template_global'    => 'Available to all branches',
        'template_tasks'     => 'Template tasks',
        'task_title'         => 'Task title',
        'start_offset_days'  => 'Starts after (days)',
        'due_offset_days'    => 'Due after (days)',
        'checklist_lines'    => 'Steps (one per line)',
        'is_active'          => 'Active',
        'tasks_count'        => 'Tasks count',
    ],

    'actions' => [
        'add_new'          => 'New Project',
        'edit'             => 'Edit',
        'view'             => 'View',
        'delete'           => 'Delete',
        'back'             => 'Back to projects',
        'manage_members'   => 'Edit members',
        'add_member'       => 'Add member',
        'add_task'         => 'Task in project',
        'all_tasks'        => 'All project tasks',
        'save_as_template' => 'Save as template',
        'new_template'     => 'New template',
        'add_template_task'=> 'Template task',
        'remove'           => 'Remove',
    ],

    'filters' => [
        'search'       => 'Search by name or code',
        'all_statuses' => 'All statuses',
        'all_managers' => 'All managers',
        'active_only'  => 'Ongoing',
    ],

    'stats' => [
        'total'     => 'Tasks',
        'completed' => 'Completed',
        'open'      => 'Open',
        'overdue'   => 'Overdue',
    ],

    'labels_text' => [
        'empty'          => 'No projects here',
        'empty_hint'     => 'Projects you manage or belong to will show up here.',
        'no_manager'     => 'No manager',
        'no_members'     => 'No members',
        'no_due'         => 'No end date',
        'overdue'        => 'Overdue',
        'tasks_done'     => ':done of :total tasks',
        'members_count'  => ':count members',
        'no_template'    => 'Empty project',
        'template_tasks' => ':count tasks',
        'no_templates'   => 'No templates yet',
        'your_role'      => 'Your role',
        'recent_tasks'   => 'Tasks',
        'none'           => 'None',
    ],

    'messages' => [
        'created'          => 'Project created successfully',
        'updated'          => 'Project updated',
        'deleted'          => 'Project deleted',
        'restored'         => 'Project restored',
        'force_deleted'    => 'Project permanently deleted',
        'members_updated'  => 'Project members updated',
        'status_changed'   => 'Project status changed',
        'template_saved'   => 'Template saved',
        'template_deleted' => 'Template deleted',
    ],

    'errors' => [
        'forbidden'      => 'You are not allowed to do this on the project',
        'invalid_member' => 'A selected employee does not exist or was terminated',
    ],

    'hints' => [
        'name'       => 'A clear project name, e.g. New website launch.',
        'manager'    => 'The employee responsible — can edit the project and its members from the portal and approve its tasks.',
        'members'    => 'Members see the project and add tasks; viewers can only see. Members may come from other branches.',
        'template'   => 'Pick a ready template and its tasks are created automatically, dated from the project start date.',
        'start_date' => 'Template task dates are calculated from this date.',
        'offsets'    => 'Days from project start. E.g. starts after 0, due after 5 = the first 5 days of the project.',
        'color'      => 'A color that marks the project in lists.',
    ],

    'status_enums' => [
        'planning'  => 'Planning',
        'active'    => 'Active',
        'on_hold'   => 'On Hold',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'member_role_enums' => [
        'manager' => 'Manager',
        'member'  => 'Member',
        'viewer'  => 'Viewer',
    ],

];
