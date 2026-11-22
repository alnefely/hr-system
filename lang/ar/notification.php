<?php

/*
|--------------------------------------------------------------------------
| الإشعارات
|--------------------------------------------------------------------------
*/

return [

    'title'       => 'الإشعارات',
    'singular'    => 'إشعار',
    'empty'       => 'مفيش إشعارات جديدة',
    'empty_hint'  => 'هيظهر هنا أي طلب أو تنبيه محتاج انتباهك.',
    'view_all'    => 'عرض كل الإشعارات',
    'unread'      => 'غير مقروء',
    'mark_read'   => 'تعليم كمقروء',
    'mark_all'    => 'تعليم الكل كمقروء',
    'marked_all'  => 'تم تعليم كل الإشعارات كمقروءة',

    'types' => [
        'leave_requested'         => 'طلب إجازة جديد',
        'leave_decided'           => 'نتيجة طلب إجازة',
        'permission_requested'    => 'طلب استئذان جديد',
        'advance_requested'       => 'طلب سلفة جديد',
        'advance_decided'         => 'نتيجة طلب سلفة',
        'payroll_awaiting_review' => 'مسير رواتب بانتظار الاعتماد',
        'subscription_due_soon'   => 'اشتراك يقترب من التجديد',
        'petty_cash_low'          => 'رصيد عهدة منخفض',
        'invoice_overdue'         => 'فاتورة متأخرة السداد',
        'task_review_requested'   => 'مهمة محتاجة مراجعة',
        'task_activity'           => 'نشاط على مهمة',
    ],

    'messages' => [
        'leave_requested' => [
            'title' => 'طلب إجازة جديد بانتظار المراجعة',
            'body'  => ':employee قدّم طلب :type لمدة :days يوم.',
        ],
        'leave_decided' => [
            'title' => 'تم البت في طلب الإجازة',
            'body'  => 'طلب إجازتك لمدة :days يوم أصبح: :status.',
        ],
        'permission_requested' => [
            'title' => 'طلب استئذان جديد بانتظار المراجعة',
            'body'  => ':employee طلب استئذان يوم :date من :from إلى :to.',
        ],
        'advance_requested' => [
            'title' => 'طلب سلفة جديد بانتظار المراجعة',
            'body'  => ':employee طلب سلفة بقيمة :amount على :installments قسط.',
        ],
        'advance_decided' => [
            'title' => 'تم البت في طلب السلفة',
            'body'  => 'طلب السلفة بقيمة :amount أصبح: :status.',
        ],
        'payroll_awaiting_review' => [
            'title' => 'مسير رواتب جاهز للاعتماد',
            'body'  => 'تم إصدار :title بصافي :net — بانتظار اعتمادك.',
        ],
        'subscription_due_soon' => [
            'title' => 'اشتراك يقترب من التجديد',
            'body'  => ':name يستحق التجديد خلال :days يوم بقيمة :amount.',
        ],
        'petty_cash_low' => [
            'title' => 'رصيد العهدة أوشك على النفاد',
            'body'  => 'المتبقي في عهدة :branch هو :remaining فقط.',
        ],
        'invoice_overdue' => [
            'title' => 'فاتورة تجاوزت موعد السداد',
            'body'  => 'الفاتورة :invoice للعميل :customer متأخرة بمبلغ :amount.',
        ],
        'invoice_overdue_summary' => [
            'title' => 'فواتير تجاوزت موعد السداد',
            'body'  => ':count فاتورة متأخرة أكثر من :days يوم، بإجمالي مستحق :amount.',
        ],
    ],

    'filters' => [
        'type'   => 'نوع الإشعار',
        'status' => 'الحالة',
        'all'    => 'الكل',
        'unread' => 'غير المقروء فقط',
        'read'   => 'المقروء فقط',
    ],

    'no_extra_details' => 'الإشعار ده مالوش تفاصيل إضافية غير اللي ظاهر هنا',

];
