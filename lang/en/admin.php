<?php

/*
|--------------------------------------------------------------------------
| Admins screen
|--------------------------------------------------------------------------
*/

return [

    'title'   => 'Admins',
    'name'    => 'Admin Name',
    'profile' => 'Profile',
    'img'     => 'Admin Photo',
    'role'    => 'Role',
    'add_new' => 'Add New Admin',
    'edit'    => 'Edit Admin [ :name ]',

    'admins_and_roles' => 'Admins & Roles',

    'linked_employee'      => 'Linked employee (optional)',
    'no_linked_employee'   => 'None — all branches',
    'linked_employee_hint' => 'If this admin is one of your employees, link their file: the admin is limited to that employee’s branch data and is notified of decisions on their own leaves and advances. Admin panel accounts are separate from employee portal access.',

    // شرح الحقول (أيقونة ؟ جنب العنوان)
    'hints' => [
        'email'    => 'Admin panel login, and where the password reset link is sent.',
        'password' => 'When editing: leave empty to keep the current one.',
        'role'     => 'Decides which screens and buttons are available. Roles are managed under “Roles & Permissions”.',
    ],

];
