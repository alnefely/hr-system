<?php

return [

    'title'        => 'Customer Sources',
    'singular'     => 'Customer source',
    'create_title' => 'Add a new source',
    'edit_title'   => 'Edit source',
    'trash'        => 'Customer sources trash',

    'view_customers'  => 'View the customers that came from this source',
    'customers_count' => 'customers',

    'fields' => [
        'code'            => 'Code',
        'name'            => 'Source name',
        'description'     => 'Description',
        'sort_order'      => 'Display order',
        'is_active'       => 'Status',
        'customers_count' => 'Customers',
    ],

    'hints' => [
        'module'      => 'Add and edit the customer sources that appear in the "Customer source" list on the customer screen',
        'code'        => 'A short unique code for the source, used in reports and linking. Must be unique',
        'name'        => 'The name shown to users in the list, e.g. Referral, Exhibition, Ad campaign',
        'description' => 'Optional explanation so the rest of the team knows what this source means',
        'sort_order'  => 'Controls the order the source appears in the list - lower numbers come first',
    ],

    'actions' => [
        'add_new' => 'Add source',
    ],

    'messages' => [
        'created'       => 'Source created successfully',
        'updated'       => 'Source updated successfully',
        'deleted'       => 'Source deleted successfully',
        'has_customers' => 'This source cannot be deleted because customers are linked to it. Deactivate it instead if you no longer want it in the list.',
    ],

];
