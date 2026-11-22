<?php

/*
|--------------------------------------------------------------------------
| Notifications
|--------------------------------------------------------------------------
*/

return [

    'title'       => 'Notifications',
    'singular'    => 'Notification',
    'empty'       => 'No new notifications',
    'empty_hint'  => 'Any request or alert that needs your attention will appear here.',
    'view_all'    => 'View all notifications',
    'unread'      => 'Unread',
    'mark_read'   => 'Mark as read',
    'mark_all'    => 'Mark all as read',
    'marked_all'  => 'All notifications marked as read',

    'types' => [
        'leave_requested'         => 'New Leave Request',
        'leave_decided'           => 'Leave Request Decision',
        'permission_requested'    => 'New permission request',
        'advance_requested'       => 'New Advance Request',
        'advance_decided'         => 'Advance Request Decision',
        'payroll_awaiting_review' => 'Payroll Awaiting Approval',
        'subscription_due_soon'   => 'Subscription Renewal Approaching',
        'petty_cash_low'          => 'Low Petty Cash Balance',
        'invoice_overdue'         => 'Overdue Invoice',
        'task_review_requested'   => 'Task awaiting review',
        'task_activity'           => 'Task activity',
    ],

    'messages' => [
        'leave_requested' => [
            'title' => 'New leave request awaiting review',
            'body'  => ':employee submitted a :type request for :days day(s).',
        ],
        'leave_decided' => [
            'title' => 'Leave request decided',
            'body'  => 'Your :days day(s) leave request is now: :status.',
        ],
        'permission_requested' => [
            'title' => 'New permission request awaiting review',
            'body'  => ':employee requested permission on :date from :from to :to.',
        ],
        'advance_requested' => [
            'title' => 'New advance request awaiting review',
            'body'  => ':employee requested an advance of :amount over :installments installments.',
        ],
        'advance_decided' => [
            'title' => 'Advance request decided',
            'body'  => 'The advance request of :amount is now: :status.',
        ],
        'payroll_awaiting_review' => [
            'title' => 'Payroll ready for approval',
            'body'  => ':title was generated with a net of :net — awaiting your approval.',
        ],
        'subscription_due_soon' => [
            'title' => 'Subscription renewal approaching',
            'body'  => ':name is due for renewal in :days day(s) for :amount.',
        ],
        'petty_cash_low' => [
            'title' => 'Petty cash balance is running low',
            'body'  => 'Only :remaining remains in the :branch petty cash.',
        ],
        'invoice_overdue' => [
            'title' => 'Invoice past its due date',
            'body'  => 'Invoice :invoice for :customer is overdue by :amount.',
        ],
        'invoice_overdue_summary' => [
            'title' => 'Invoices past their due date',
            'body'  => ':count invoices are more than :days days overdue, totalling :amount.',
        ],
    ],

    'filters' => [
        'type'   => 'Notification Type',
        'status' => 'Status',
        'all'    => 'All',
        'unread' => 'Unread only',
        'read'   => 'Read only',
    ],

    'no_extra_details' => 'This notification has no details beyond what is shown here',

];
