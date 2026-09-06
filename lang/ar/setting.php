<?php

/*
|--------------------------------------------------------------------------
| الإعدادات
|--------------------------------------------------------------------------
| عناوين الشاشة + لوابل كل حقل إعداد.
|
| موديل Settings بيقرا من هنا مباشرة:
|
|     trans("setting.{$this->key}.label")
|     trans("setting.{$this->key}.hint")
|     trans("setting.{$this->key}.placeholder")
|
| فأي إعداد جديد بتضيفه في الداتابيز لازم يبقى ليه مفتاح هنا
| بنفس الـ key بالظبط، وإلا الشاشة هتعرض اسم المفتاح الخام.
*/

return [


    'title' => 'الإعدادات',
    'clear_cache' => 'مسح الكاش',

    // خيارات حقول التفعيل/التعطيل (select بقيمة 1/0)
    'options' => [
        1 => 'مفعّل',
        0 => 'غير مفعّل',
    ],

    'groups' => [
        'general' => 'اعدادات عامة',
        'social' => 'وسائل التواصل الاجتماعي',
        'mail' => 'البريد الإلكتروني',
    ],

    // Divider
    'logo_favicon_divider' => [
        'label' => 'الشعار والأيقونة',
        'hint' => '',
        'placeholder' => '',
    ],
    'app_env_divider' => [
        'label' => 'بيئة التطبيق والمنطقة الزمنية',
        'hint' => '',
        'placeholder' => '',
    ],

    'site_name' => [
        'label' => 'اسم الموقع',
        'hint' => '',
        'placeholder' => 'أدخل اسم الموقع',
    ],
    'site_email' => [
        'label' => 'البريد الإلكتروني للموقع',
        'hint' => '',
        'placeholder' => 'أدخل البريد الإلكتروني للموقع',
    ],
    'site_logo' => [
        'label' => 'شعار الموقع',
        'hint' => '',
        'placeholder' => '',
    ],
    'site_favicon' => [
        'label' => 'أيقونة الموقع (Favicon)',
        'hint' => '',
        'placeholder' => '',
    ],
    
    'mail_driver' => [
        'label' => 'سيرفر البريد (Mail Driver)',
        'hint' => '',
        'placeholder' => '',
    ],

    'mail_host' => [
        'label' => 'سيرفر SMTP (Mail Host)',
        'hint' => '',
        'placeholder' => '',
    ],
    'mail_port' => [
        'label' => 'منفذ البريد (Mail Port)',
        'hint' => '',
        'placeholder' => '',
    ],
    'mail_username' => [
        'label' => 'اسم مستخدم البريد (Mail Username)',
        'hint' => '',
        'placeholder' => '',
    ],
    'mail_password' => [
        'label' => 'كلمة مرور البريد (Mail Password)',
        'hint' => '',
        'placeholder' => '',
    ],
    'mail_encryption' => [
        'label' => 'تشفير البريد (Mail Encryption)',
        'hint' => '',
        'placeholder' => '',
    ],
    'mail_from_name' => [
        'label' => 'اسم المرسل (Mail From Name)',
        'hint' => '',
        'placeholder' => '',
    ],
    'mail_from_address' => [
        'label' => 'عنوان المرسل (Mail From Address)',
        'hint' => '',
        'placeholder' => '',
    ],
    'facebook_url' => [
        'label' => 'رابط فيسبوك (Facebook)',
        'hint' => '',
        'placeholder' => '',
    ],
    'twitter_url' => [
        'label' => 'رابط تويتر (Twitter)',
        'hint' => '',
        'placeholder' => '',
    ],
    'instagram_url' => [
        'label' => 'رابط إنستغرام (Instagram)',
        'hint' => '',
        'placeholder' => '',
    ],
    'linkedin_url' => [
        'label' => 'رابط لينكدإن (LinkedIn)',
        'hint' => '',
        'placeholder' => '',
    ],
    'youtube_url' => [
        'label' => 'رابط يوتيوب (Youtube)',
        'hint' => '',
        'placeholder' => '',
    ],
    'tiktok_url' => [
        'label' => 'رابط تيك توك (TikTok)',
        'hint' => '',
        'placeholder' => '',
    ],
    'snapchat_url' => [
        'label' => 'رابط سناب شات (Snapchat)',
        'hint' => '',
        'placeholder' => '',
    ],
    'github_url' => [
        'label' => 'رابط جيت هب (GitHub)',
        'hint' => '',
        'placeholder' => '',
    ],
    'telegram_url' => [
        'label' => 'رابط تيليجرام (Telegram)',
        'hint' => '',
        'placeholder' => '',
    ],
    'whatsapp_url' => [
        'label' => 'رابط واتساب (WhatsApp)',
        'hint' => '',
        'placeholder' => '',
    ],
    'phone_number' => [
        'label' => 'رقم الهاتف (Phone Number)',
        'hint' => '',
        'placeholder' => '',
    ],
    'app_env' => [
        'label' => 'بيئة التطبيق (App Environment)',
        'hint' => 'تحدد بيئة التطبيق الحالية (مثل: local: وضع تطوير, production: وضع إنتاج)',
        'placeholder' => '',
    ],
    'app_timezone' => [
        'label' => 'المنطقة الزمنية للتطبيق (App Timezone)',
        'hint' => 'تحدد المنطقة الزمنية الافتراضية للتطبيق (مثل: UTC, Asia/Riyadh, America/New_York)',
        'placeholder' => '',
    ],

];
