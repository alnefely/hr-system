<?php

/*
|--------------------------------------------------------------------------
| إعدادات موديول المهام
|--------------------------------------------------------------------------
*/

return [

    'attachments' => [
        // disk خاص (مش public): الملفات بتتنزل من مسار بيتحقق من الصلاحية
        'disk'      => env('TASK_ATTACHMENTS_DISK', 'local'),
        'max_kb'    => (int) env('TASK_ATTACHMENT_MAX_KB', 10240),
        'max_files' => 5,
        'mimes'     => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip'],
    ],

];
