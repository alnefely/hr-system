<?php

/*
|--------------------------------------------------------------------------
| إدارة المهام
|--------------------------------------------------------------------------
*/

return [

    'title'    => 'المهام',
    'singular' => 'مهمة',

    'status_enums' => [
        'new'         => 'جديدة',
        'in_progress' => 'قيد التنفيذ',
        'in_review'   => 'قيد المراجعة',
        'completed'   => 'مكتملة',
        'cancelled'   => 'ملغية',
    ],

    'priority_enums' => [
        'low'    => 'منخفضة',
        'medium' => 'متوسطة',
        'high'   => 'عالية',
        'urgent' => 'عاجلة',
    ],

    'progress_source_enums' => [
        'manual'    => 'يدوي (تحديثات من الموظف)',
        'checklist' => 'تلقائي من الخطوات',
        'subtasks'  => 'تلقائي من المهام الفرعية',
    ],

    'progress_interval_enums' => [
        'none'   => 'غير مطلوب',
        'daily'  => 'يومياً',
        'weekly' => 'أسبوعياً',
        'custom' => 'كل عدد أيام محدد',
    ],

    'progress_health' => [
        'ahead'    => 'سابق الخطة',
        'on_track' => 'في الموعد',
        'behind'   => 'متأخر عن الخطة',
    ],

    'assignee_status_enums' => [
        'active'   => 'مكلّف',
        'declined' => 'اعتذر',
    ],

    'review_status_enums' => [
        'pending'  => 'بانتظار المراجعة',
        'approved' => 'معتمدة',
        'rejected' => 'مرفوضة',
    ],

    'frequency_enums' => [
        'daily'   => 'يومي',
        'weekly'  => 'أسبوعي',
        'monthly' => 'شهري',
        'yearly'  => 'سنوي',
    ],

    'reminder_type_enums' => [
        'due_soon'        => 'اقتراب موعد التسليم',
        'overdue'         => 'مهمة متأخرة',
        'progress_update' => 'تحديث نسبة الإنجاز',
        'custom'          => 'تذكير مخصص',
    ],

];
