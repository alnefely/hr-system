<?php

return [
    'trash'               => 'Trash',
    'back'                => 'Back to list',
    'title'               => 'Vendors & External Companies',
    'singular'            => 'External Company / Vendor',
    'create_title'        => 'Add New External Company / Vendor',
    'edit_title'          => 'Edit Company / Vendor Details',
    'details_title'       => 'Company / Vendor Details',
    'subscriptions_count' => 'Subscriptions & Services',

    'fields' => [
        'code'                => 'Vendor Code',
        'name'                => 'Company / Vendor Name',
        'company_name'        => 'Entity / Company Name',
        'contact_person'      => 'Contact Person',
        'phone'               => 'Phone / Contact Number',
        'email'               => 'Email',
        'tax_number'          => 'Tax / Registration Number',
        'is_active'           => 'Status',
        'notes'               => 'Notes & Dealing Details',
        'subscriptions_count' => 'Number of Subscriptions & Services',
    ],

    'enums' => [
        'active'   => 'Active',
        'inactive' => 'Inactive',
    ],

    'actions' => [
        'add_new'      => 'Add New Vendor',
        'back_to_list' => 'Back to Vendors List',
    ],

    'messages' => [
        'restored'      => 'Record restored successfully',
        'force_deleted' => 'Permanently deleted successfully',
        'created'       => 'Company / Vendor added successfully',
        'updated'       => 'Vendor details updated successfully',
        'deleted'       => 'Vendor deleted successfully',
    ],

    // شرح الحقول (أيقونة ؟ جنب العنوان)
    'hints' => [
        'code'       => 'A short unique code for search and reports (e.g. V-001).',
        'tax_number' => 'Tax or commercial registration number — used for audits and invoices.',
        'is_active'  => 'An inactive vendor is hidden when adding a new subscription or payment; its history stays.',
    ],

];
