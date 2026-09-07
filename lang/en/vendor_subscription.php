<?php

return [
    'trash'         => 'Trash',
    'back'          => 'Back to list',
    'title'         => 'External Subscriptions & Services',
    'singular'      => 'Subscription / Service Contract',
    'create_title'  => 'Add New Subscription / Contract',
    'edit_title'    => 'Edit Subscription / Service Contract',
    'details_title' => 'Subscription & Service Details',

    'stats' => [
        'active_total'               => 'Total Cost of Active Subscriptions',
        'active_total_hint'          => 'Cost of approved recurring subscriptions, per currency independently',
        'upcoming_renewals'          => 'Subscriptions Approaching Renewal',
        'upcoming_renewals_hint'     => 'Due for renewal within 30 days',
        'expired_subscriptions'      => 'Expired / Renewal Needed',
        'expired_subscriptions_hint' => 'Require renewal or service termination',
    ],

    'fields' => [
        'title'                 => 'Subscription / Service Name',
        'vendor'                => 'External Vendor / Company',
        'select_vendor'         => 'Select vendor / company...',
        'branch'                => 'Beneficiary Branch / Office',
        'all_branches'          => 'All Offices & Branches',
        'type'                  => 'Service Type',
        'billing_cycle'         => 'Billing & Renewal Cycle',
        'cost'                  => 'Cost / Subscription Value',
        'currency'              => 'Currency',
        'start_date'            => 'Start Date',
        'next_billing_date'     => 'Next Renewal Date',
        'auto_renew'            => 'Auto Renewal',
        'status'                => 'Subscription Status',
        'attachment'            => 'Contract / Invoice Attachment',
        'notes'                 => 'Service Terms & Notes',
        'payment_record'        => 'Record Payment / Renewal',
        'paid_amount'           => 'Paid Amount',
        'payment_date'          => 'Transfer / Payment Date',
        'payment_method'        => 'Payment Method',
        'transaction_reference' => 'Bank Transfer / Transaction Reference',
        'invoice_number'        => 'Invoice Number',
        'renew_next_date'       => 'New Next Renewal Date',
    ],

    'type_enums' => [
        'subscription' => 'Recurring Subscription (SaaS / Services)',
        'contract'     => 'Project Contract / External Work',
        'one_time'     => 'One-Time Service',
    ],

    'cycle_enums' => [
        'monthly'       => 'Monthly',
        'quarterly'     => 'Every 3 Months (Quarterly)',
        'semi_annually' => 'Every 6 Months (Semi-Annually)',
        'annually'      => 'Annually',
        'one_time'      => 'One-Time Payment',
    ],

    'cycles' => [
        'monthly'       => 'Monthly',
        'quarterly'     => 'Quarterly',
        'semi_annually' => 'Semi-Annually',
        'annually'      => 'Annually',
        'one_time'      => 'One-Time',
    ],

    'statuses' => [
        'active'    => 'Active',
        'pending'   => 'Pending',
        'expired'   => 'Expired',
        'cancelled' => 'Cancelled',
    ],

    'actions' => [
        'add_new'        => 'Add New Subscription',
        'back_to_list'   => 'Back to Subscriptions List',
        'record_payment' => 'Record New Payment',
    ],

    'messages' => [
        'restored'        => 'Record restored successfully',
        'force_deleted'   => 'Permanently deleted successfully',
        'created'         => 'Subscription added successfully',
        'updated'         => 'Subscription updated successfully',
        'deleted'         => 'Subscription deleted successfully',
        'payment_success' => 'Payment recorded and next due date updated successfully',
    ],

    'payment_methods' => [
        'bank_transfer' => 'Bank transfer',
        'card'          => 'Card / Visa',
        'cash'          => 'Cash',
        'cheque'        => 'Bank cheque',
    ],

];
