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

    'time' => [
        // عدّاد نسيه الموظف شغّال بيتحسب بالحد ده بس (مش أيام)
        'max_timer_hours' => (int) env('TASK_MAX_TIMER_HOURS', 12),
    ],

    'reminders' => [
        // تذكير "قرب التسليم" قبل الموعد بكام ساعة
        'due_soon_hours' => (int) env('TASK_DUE_SOON_HOURS', 24),
    ],

];
