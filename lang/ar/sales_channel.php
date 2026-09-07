<?php

return [
    'trash' => 'سلة المحذوفات',
    'back'  => 'رجوع للقائمة',
    'title'          => 'إدارة قنوات البيع والتطبيقات',
    'singular'       => 'قناة بيع / تطبيق',
    'create_title'   => 'إضافة قناة بيع / تطبيق جديد',
    'edit_title'     => 'تعديل قناة البيع',
    'view_sales'     => 'عرض عمليات البيع لهذه القناة',
    'sales_count'    => 'عمليات بيع',
    
    'fields' => [
        'code'        => 'كود القناة',
        'name'        => 'اسم التطبيق / القناة / المنصة',
        'type'        => 'نوع القناة / المجال',
        'custom_type' => 'أو اكتب نوع مخصص يدوياً...',
        'notes'       => 'ملاحظات',
        'is_active'   => 'الحالة',
        'sales_count' => 'عدد عمليات البيع',
    ],

    'type_enums' => [
        'app'          => 'تطبيق جوال (Mobile App)',
        'marketing'    => 'تسويق وإعلانات (Marketing & Ads)',
        'social_media' => 'سوشيال ميديا وتواصل اجتماعي (Social Media)',
        'project'      => 'مشروع / عمل خارجي (External Project)',
        'platform'     => 'منصة / موقع إلكتروني (Website / Platform)',
        'store'        => 'متجر إلكتروني (E-Commerce Store)',
        'consulting'   => 'استشارات وتدريب (Consulting & Training)',
        'branch_sale'  => 'مبيعات فرع / مقر مباشر (Physical Branch)',
        'other'        => 'أخرى / نوع مخصص يدوياً',
    ],

    'enums' => [
        'active'   => 'مفعل',
        'inactive' => 'معطل',
    ],

    'actions' => [
        'add_new' => 'إضافة قناة بيع جديدة',
    ],

    'messages' => [
        'restored'       => 'تم استرجاع السجل بنجاح',
        'force_deleted'  => 'تم الحذف النهائي بنجاح',
        'created' => 'تم إضافة قناة البيع بنجاح',
        'updated' => 'تم تحديث قناة البيع بنجاح',
        'deleted' => 'تم حذف قناة البيع بنجاح',
    ],
];
