<?php

return [
    'title'         => 'Job Titles',
    'singular'      => 'Job Title',
    'add_new'       => 'Add New Job Title',
    'edit'          => 'Edit Job Title',
    'trash'         => 'Job Titles Trash',
    'back'          => 'Back to Job Titles',

    'fields' => [
        'name'            => 'Job Title',
        'description'     => 'Tasks & Responsibilities Description',
        'status'          => 'Available for Hiring',
        'active'          => 'Available',
        'inactive'        => 'Not Available',
        'employees_count' => 'Current Employees',
        'created_at'      => 'Created At',
        'deleted_at'      => 'Deleted At',
    ],

    'messages' => [
        'created'       => 'Job title created successfully',
        'updated'       => 'Job title updated successfully',
        'deleted'       => 'Job title moved to trash successfully',
        'restored'      => 'Job title restored successfully',
        'force_deleted' => 'Job title permanently deleted',
        'cannot_delete' => 'Cannot delete job title because it is assigned to employees',
        'empty_trash'   => 'Job titles trash is empty',
    ],

    // شرح الحقول (أيقونة ؟ جنب العنوان)
    'hints' => [
        'is_active' => 'An inactive title is hidden when adding new employees; current employees holding it are not affected.',
    ],

];
