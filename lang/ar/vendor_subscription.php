<?php

return [
    'trash' => 'سلة المحذوفات',
    'back'  => 'رجوع للقائمة',
    'title'          => 'الاشتراكات والخدمات الخارجية',
    'singular'       => 'اشتراك / عقد خدمة',
    'create_title'   => 'إضافة اشتراك / عقد جديد',
    'edit_title'     => 'تعديل الاشتراك / عقد الخدمة',
    'details_title'  => 'تفاصيل الاشتراك والخدمة',

    'stats' => [
        'active_total'               => 'إجمالي تكلفة الاشتراكات النشطة',
        'active_total_hint'          => 'تكلفة الاشتراكات الدورية المعتمدة لكل عملة بشكل مستقل',
        'upcoming_renewals'          => 'اشتراكات تقترب من التجديد',
        'upcoming_renewals_hint'     => 'مستحقة التجديد خلال 30 يوماً',
        'expired_subscriptions'      => 'اشتراكات تحتاج تجديد / منتهية',
        'expired_subscriptions_hint' => 'تتطلب التجديد أو إنهاء الخدمة',
    ],

    'fields' => [
        'title'             => 'اسم الاشتراك / الخدمة',
        'vendor'            => 'الشركة / المورد الخارجي',
        'select_vendor'     => 'اختر المورد / الشركة...',
        'branch'            => 'الفرع / المقر المستفيد',
        'all_branches'      => 'جميع المقرات والفروع',
        'type'              => 'نوع الخدمة',
        'billing_cycle'     => 'دورة الفوترة والتجديد',
        'cost'              => 'التكلفة / قيمة الاشتراك',
        'currency'          => 'العملة',
        'start_date'        => 'تاريخ البدء',
        'next_billing_date' => 'تاريخ التجديد القادم',
        'auto_renew'        => 'تجديد تلقائي',
        'status'            => 'حالة الاشتراك',
        'attachment'        => 'مرفق العقد / الفاتورة',
        'notes'             => 'شروط وملاحظات الخدمة',
        'payment_record'    => 'تسجيل سداد / تجديد دفعة',
        'paid_amount'       => 'المبلغ المدفوع',
        'payment_date'      => 'تاريخ التحويل / الدفع',
        'payment_method'    => 'طريقة الدفع',
        'transaction_reference' => 'رقم التحويل البنكي / العملية',
        'invoice_number'    => 'رقم الفاتورة',
        'renew_next_date'   => 'تاريخ التجديد القادم الجديد',
    ],

    'type_enums' => [
        'subscription' => 'اشتراك دوري (SaaS / Services)',
        'contract'     => 'عقد مشروع / مهام خارجية',
        'one_time'     => 'خدمة لمرة واحدة',
    ],

    'cycle_enums' => [
        'monthly'       => 'شهري',
        'quarterly'     => 'كل 3 شهور (ربع سنوي)',
        'semi_annually' => 'كل 6 شهور (نصف سنوي)',
        'annually'      => 'سنوي',
        'one_time'      => 'دفعة واحدة',
    ],

    'cycles' => [
        'monthly'       => 'شهري',
        'quarterly'     => 'ربع سنوي',
        'semi_annually' => 'نصف سنوي',
        'annually'      => 'سنوي',
        'one_time'      => 'مرة واحدة',
    ],

    'statuses' => [
        'active'    => 'نشط',
        'pending'   => 'قيد الانتظار',
        'expired'   => 'منتهي',
        'cancelled' => 'ملغي',
    ],

    'actions' => [
        'add_new'      => 'إضافة اشتراك جديد',
        'back_to_list' => 'الرجوع لقائمة الاشتراكات',
        'record_payment' => 'تسجيل دفعة جديدة',
    ],

    'messages' => [
        'restored'       => 'تم استرجاع السجل بنجاح',
        'force_deleted'  => 'تم الحذف النهائي بنجاح',
        'created'         => 'تم إضافة الاشتراك بنجاح',
        'updated'         => 'تم تحديث الاشتراك بنجاح',
        'deleted'         => 'تم حذف الاشتراك بنجاح',
        'payment_success' => 'تم تسجيل الدفعة وتحديث تاريخ الاستحقاق القادم بنجاح',
    ],

    'payment_methods' => [
        'bank_transfer' => 'تحويل بنكي',
        'card'          => 'بطاقة دفع إلكترونية / فيزا',
        'cash'          => 'نقدي (كاش)',
        'cheque'        => 'شيك مصرفي',
    ],

];
