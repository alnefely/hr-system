<?php

return [
    'trash' => 'Trash',
    'back'  => 'Back to list',
    'title'          => 'Sales Channels & Apps',
    'singular'       => 'Sales Channel',
    'create_title'   => 'Add New Sales Channel / App',
    'edit_title'     => 'Edit Sales Channel',
    'view_sales'     => 'View sales for this channel',
    'sales_count'    => 'Sales Orders',
    
    'fields' => [
        'code'        => 'Channel Code',
        'name'        => 'App / Channel / Platform Name',
        'type'        => 'Channel Type',
        'custom_type' => 'Or enter custom type...',
        'notes'       => 'Notes',
        'is_active'   => 'Status',
        'sales_count' => 'Sales Count',
    ],

    'type_enums' => [
        'app'          => 'Mobile App',
        'marketing'    => 'Marketing & Ads',
        'social_media' => 'Social Media',
        'project'      => 'External Project',
        'platform'     => 'Platform / Website',
        'store'        => 'E-Commerce Store',
        'consulting'   => 'Consulting & Training',
        'branch_sale'  => 'Physical Branch Sales',
        'other'        => 'Other / Custom Type',
    ],

    'enums' => [
        'active'   => 'Active',
        'inactive' => 'Inactive',
    ],

    'actions' => [
        'add_new' => 'Add New Channel',
    ],

    'messages' => [
        'restored'       => 'Channel restored successfully',
        'force_deleted'  => 'Channel permanently deleted',
        'created'        => 'Sales channel created successfully',
        'updated'        => 'Sales channel updated successfully',
        'deleted'        => 'Sales channel deleted successfully',
    ],

    // شرح الحقول (أيقونة ؟ جنب العنوان)
    'hints' => [
        'code'      => 'A short unique code for the channel (e.g. WEB, APP).',
        'type'      => 'Channel category — sales are grouped by it in the dashboard and reports.',
        'is_active' => 'An inactive channel is hidden when recording a new sale; its past sales stay in reports.',
    ],

];
