<?php

return [

    'title'        => 'Short Links',
    'singular'     => 'Short Link',
    'create_title' => 'Create New Short Link',
    'edit_title'   => 'Edit Short Link',
    'trash'        => 'Trash',
    'back'         => 'Back to list',

    'fields' => [
        'short_code'    => 'Short Code',
        'short_url'     => 'Short URL',
        'original_url'  => 'Destination URL',
        'title'         => 'Link Title',
        'notes'         => 'Notes',
        'branch'        => 'Branch',
        'creator'       => 'Created By',
        'is_active'     => 'Status',
        'expires_at'    => 'Expires At',
        'max_clicks'    => 'Max Clicks',
        'clicks_count'  => 'Clicks',
        'last_clicked'  => 'Last Clicked',
        'created_at'    => 'Created At',
        'deleted_at'    => 'Deleted At',
    ],

    'hints' => [
        'short_code'   => 'A random code generated for you — you can change it',
        'code_length'  => ':min to :max characters (letters, digits, - and _)',
        'original_url' => 'The destination to redirect to — must start with http or https',
        'expires_at'   => 'After this date the link stops working',
        'max_clicks'   => 'The link stops automatically once this many clicks are reached',
        'branch'       => 'The branch this link belongs to',
    ],

    'filters' => [
        'all_branches' => 'All Branches',
        'all_statuses' => 'All Statuses',
        'period'       => 'Period (from / to)',
        'date_from'    => 'From Date',
        'date_to'      => 'To Date',
    ],

    'status' => [
        'active'   => 'Active',
        'inactive' => 'Disabled',
        'expired'  => 'Expired',
    ],

    'stats' => [
        'total_links'   => 'Total Links',
        'active_links'  => 'Active Links',
        'total_clicks'  => 'Total Clicks',
        'expired_links' => 'Expired Links',
    ],

    'actions' => [
        'copy'      => 'Copy Link',
        'copied'    => 'Link copied',
        'open'      => 'Open Link',
        'stats'     => 'Statistics',
        'qr'        => 'QR Code',
        'regenerate' => 'Generate a new code',
    ],

    'confirm' => [
        'delete_title'  => 'Delete this link?',
        'delete_text'   => 'The link :link will stop working immediately for everyone who has it.',
        'delete_button' => 'Yes, delete',
        'restore_title'  => 'Restore this link?',
        'restore_text'   => 'The link :link will start working again and resume redirecting immediately.',
        'restore_button' => 'Yes, restore',
    ],

    'errors' => [
        'generate_failed' => 'Could not generate a unique code, please try again.',
        'code_format'     => 'The code must be :min to :max characters (letters, digits and dashes only).',
        'code_reserved'   => 'This code is reserved by the system.',
        'code_taken'      => 'This code is already in use.',
        'url_invalid'     => 'The URL is not valid.',
        'url_scheme'      => 'Only http and https URLs are allowed.',
        'url_internal'    => 'The destination points to a disallowed internal address.',
        'url_self'        => 'You cannot shorten a short link from this same system.',
    ],

    'messages' => [
        'created' => 'Short link created successfully.',
        'updated' => 'Link updated successfully.',
        'deleted' => 'Link deleted successfully.',
        'restored' => 'Link restored successfully.',
        'force_deleted' => 'Link permanently deleted.',
    ],

    'public' => [
        'not_found_title' => 'Link Not Found',
        'not_found_text'  => 'The link you are trying to open is invalid or has been deleted.',
        'expired_title'   => 'Link Expired',
        'expired_text'    => 'This link is no longer available.',
        'inactive_text'   => 'This link has been temporarily disabled.',
    ],

    'stats_screen' => [
        'title'          => 'Link Statistics',
        'period'         => 'Period',
        'total_clicks'   => 'Total Clicks (all time)',
        'unique_clicks'  => 'Unique Visitors (all time)',
        'period_clicks'  => 'Clicks in Period',
        'period_unique'  => 'Visitors in Period',
        'bot_clicks'     => 'Bot Visits',
        'bot_hint'       => 'Link previews and crawlers — not counted as clicks',
        'daily_average'  => 'Daily Average',
        'best_day'       => 'Best Day',
        'timeline'       => 'Clicks Over Time',
        'countries'      => 'Countries',
        'devices'        => 'Devices',
        'browsers'       => 'Browsers',
        'referrers'      => 'Traffic Sources',
        'direct'         => 'Direct visit',
        'no_data'        => 'No data in this period',
        'pending_hint'   => 'Statistics appear after the daily rollup',
        'clicks_label'   => 'Clicks',
        'unique_label'   => 'Unique Visitors',
    ],

    'empty' => 'No short links yet.',

];
