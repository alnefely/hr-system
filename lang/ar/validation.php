<?php

/*
|--------------------------------------------------------------------------
| رسائل التحقق من المدخلات
|--------------------------------------------------------------------------
| :attribute بيتبدّل تلقائياً بالاسم اللي في مصفوفة attributes تحت.
| فبدل "حقل email مطلوب" بتطلع "حقل البريد الإلكتروني مطلوب".
*/

return [

    'accepted'             => 'يجب قبول :attribute.',
    'accepted_if'          => 'يجب قبول :attribute عندما يكون :other هو :value.',
    'active_url'           => ':attribute ليس رابطًا صحيحًا.',
    'after'                => 'يجب أن يكون :attribute تاريخًا بعد :date.',
    'after_or_equal'       => 'يجب أن يكون :attribute تاريخًا بعد أو يساوي :date.',
    'alpha'                => 'يجب أن يحتوي :attribute على حروف فقط.',
    'alpha_dash'           => 'يجب أن يحتوي :attribute على حروف وأرقام وشرطات فقط.',
    'alpha_num'            => 'يجب أن يحتوي :attribute على حروف وأرقام فقط.',
    'any_of'               => ':attribute غير صحيح.',
    'array'                => 'يجب أن يكون :attribute مصفوفة.',
    'ascii'                => 'يجب أن يحتوي :attribute على حروف ورموز إنجليزية فقط.',
    'before'               => 'يجب أن يكون :attribute تاريخًا قبل :date.',
    'before_or_equal'      => 'يجب أن يكون :attribute تاريخًا قبل أو يساوي :date.',

    'between' => [
        'array'   => 'يجب أن يحتوي :attribute على عدد عناصر بين :min و :max.',
        'file'    => 'يجب أن يكون حجم :attribute بين :min و :max كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و :max.',
        'string'  => 'يجب أن يكون طول :attribute بين :min و :max حرفًا.',
    ],

    'boolean'              => 'يجب أن تكون قيمة :attribute إما صح أو خطأ.',
    'can'                  => ':attribute يحتوي على قيمة غير مصرح بها.',
    'confirmed'            => 'تأكيد :attribute غير متطابق.',
    'contains'             => ':attribute لا يحتوي على قيمة مطلوبة.',
    'current_password'     => 'كلمة المرور غير صحيحة.',
    'date'                 => ':attribute ليس تاريخًا صحيحًا.',
    'date_equals'          => 'يجب أن يكون :attribute تاريخًا مساويًا لـ :date.',
    'date_format'          => 'لا يطابق :attribute الصيغة :format.',
    'decimal'              => 'يجب أن يحتوي :attribute على :decimal أرقام عشرية.',
    'declined'             => 'يجب رفض :attribute.',
    'declined_if'          => 'يجب رفض :attribute عندما يكون :other هو :value.',
    'different'            => 'يجب أن يكون :attribute مختلفًا عن :other.',
    'digits'               => 'يجب أن يتكون :attribute من :digits أرقام.',
    'digits_between'       => 'يجب أن يكون عدد أرقام :attribute بين :min و :max.',
    'dimensions'           => 'أبعاد صورة :attribute غير صحيحة.',
    'distinct'             => 'قيمة :attribute مكررة.',
    'doesnt_contain'       => 'يجب ألا يحتوي :attribute على أي من: :values.',
    'doesnt_end_with'      => 'يجب ألا ينتهي :attribute بأي من: :values.',
    'doesnt_start_with'    => 'يجب ألا يبدأ :attribute بأي من: :values.',
    'email'                => 'يجب أن يكون :attribute بريدًا إلكترونيًا صحيحًا.',
    'encoding'             => 'يجب أن يكون ترميز :attribute هو :encoding.',
    'ends_with'            => 'يجب أن ينتهي :attribute بأحد القيم التالية: :values.',
    'enum'                 => 'القيمة المختارة في :attribute غير صحيحة.',
    'exists'               => 'القيمة المختارة في :attribute غير موجودة.',
    'extensions'           => 'يجب أن يكون امتداد :attribute أحد التالي: :values.',
    'file'                 => 'يجب أن يكون :attribute ملفًا.',
    'filled'               => 'حقل :attribute مطلوب.',

    'gt' => [
        'array'   => 'يجب أن يحتوي :attribute على أكثر من :value عنصر.',
        'file'    => 'يجب أن يكون حجم :attribute أكبر من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من :value.',
        'string'  => 'يجب أن يكون طول :attribute أكبر من :value حرفًا.',
    ],

    'gte' => [
        'array'   => 'يجب أن يحتوي :attribute على :value عنصر أو أكثر.',
        'file'    => 'يجب أن يكون حجم :attribute :value كيلوبايت أو أكبر.',
        'numeric' => 'يجب أن تكون قيمة :attribute :value أو أكبر.',
        'string'  => 'يجب أن يكون طول :attribute :value حرفًا أو أكثر.',
    ],

    'hex_color'            => 'يجب أن يكون :attribute لونًا صحيحًا بصيغة HEX.',
    'image'                => 'يجب أن يكون :attribute صورة.',
    'in'                   => 'القيمة المختارة في :attribute غير صحيحة.',
    'in_array'             => 'قيمة :attribute غير موجودة ضمن :other.',
    'in_array_keys'        => 'يجب أن يحتوي :attribute على أحد المفاتيح التالية: :values.',
    'integer'              => 'يجب أن يكون :attribute رقمًا صحيحًا.',
    'ip'                   => 'يجب أن يكون :attribute عنوان IP صحيحًا.',
    'ipv4'                 => 'يجب أن يكون :attribute عنوان IPv4 صحيحًا.',
    'ipv6'                 => 'يجب أن يكون :attribute عنوان IPv6 صحيحًا.',
    'json'                 => 'يجب أن يكون :attribute نص JSON صحيحًا.',
    'list'                 => 'يجب أن يكون :attribute قائمة.',
    'lowercase'            => 'يجب أن يكون :attribute بحروف صغيرة.',

    'lt' => [
        'array'   => 'يجب أن يحتوي :attribute على أقل من :value عنصر.',
        'file'    => 'يجب أن يكون حجم :attribute أقل من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أقل من :value.',
        'string'  => 'يجب أن يكون طول :attribute أقل من :value حرفًا.',
    ],

    'lte' => [
        'array'   => 'يجب ألا يحتوي :attribute على أكثر من :value عنصر.',
        'file'    => 'يجب أن يكون حجم :attribute :value كيلوبايت أو أقل.',
        'numeric' => 'يجب أن تكون قيمة :attribute :value أو أقل.',
        'string'  => 'يجب أن يكون طول :attribute :value حرفًا أو أقل.',
    ],

    'mac_address'          => 'يجب أن يكون :attribute عنوان MAC صحيحًا.',

    'max' => [
        'array'   => 'يجب ألا يحتوي :attribute على أكثر من :max عنصر.',
        'file'    => 'يجب ألا يزيد حجم :attribute عن :max كيلوبايت.',
        'numeric' => 'يجب ألا تزيد قيمة :attribute عن :max.',
        'string'  => 'يجب ألا يزيد طول :attribute عن :max حرفًا.',
    ],

    'max_digits'           => 'يجب ألا يزيد عدد أرقام :attribute عن :max رقمًا.',
    'mimes'                => 'يجب أن يكون :attribute ملفًا من نوع: :values.',
    'mimetypes'            => 'يجب أن يكون :attribute ملفًا من نوع: :values.',

    'min' => [
        'array'   => 'يجب أن يحتوي :attribute على :min عنصر على الأقل.',
        'file'    => 'يجب ألا يقل حجم :attribute عن :min كيلوبايت.',
        'numeric' => 'يجب ألا تقل قيمة :attribute عن :min.',
        'string'  => 'يجب ألا يقل طول :attribute عن :min حرفًا.',
    ],

    'min_digits'           => 'يجب ألا يقل عدد أرقام :attribute عن :min رقمًا.',
    'missing'              => 'يجب ألا يكون حقل :attribute موجودًا.',
    'missing_if'           => 'يجب ألا يكون حقل :attribute موجودًا عندما يكون :other هو :value.',
    'missing_unless'       => 'يجب ألا يكون حقل :attribute موجودًا إلا إذا كان :other هو :value.',
    'missing_with'         => 'يجب ألا يكون حقل :attribute موجودًا عند وجود :values.',
    'missing_with_all'     => 'يجب ألا يكون حقل :attribute موجودًا عند وجود :values.',
    'multiple_of'          => 'يجب أن تكون قيمة :attribute من مضاعفات :value.',
    'not_in'               => 'القيمة المختارة في :attribute غير صحيحة.',
    'not_regex'            => 'صيغة :attribute غير صحيحة.',
    'numeric'              => 'يجب أن يكون :attribute رقمًا.',

    'password' => [
        'letters'       => 'يجب أن تحتوي :attribute على حرف واحد على الأقل.',
        'mixed'         => 'يجب أن تحتوي :attribute على حرف كبير وحرف صغير على الأقل.',
        'numbers'       => 'يجب أن تحتوي :attribute على رقم واحد على الأقل.',
        'symbols'       => 'يجب أن تحتوي :attribute على رمز واحد على الأقل.',
        'uncompromised' => 'ظهرت :attribute في تسريب بيانات سابق. من فضلك اختر كلمة مرور أخرى.',
    ],

    'present'              => 'يجب أن يكون حقل :attribute موجودًا.',
    'present_if'           => 'يجب أن يكون حقل :attribute موجودًا عندما يكون :other هو :value.',
    'present_unless'       => 'يجب أن يكون حقل :attribute موجودًا إلا إذا كان :other هو :value.',
    'present_with'         => 'يجب أن يكون حقل :attribute موجودًا عند وجود :values.',
    'present_with_all'     => 'يجب أن يكون حقل :attribute موجودًا عند وجود :values.',
    'prohibited'           => 'حقل :attribute غير مسموح به.',
    'prohibited_if'        => 'حقل :attribute غير مسموح به عندما يكون :other هو :value.',
    'prohibited_if_accepted' => 'حقل :attribute غير مسموح به عند قبول :other.',
    'prohibited_if_declined' => 'حقل :attribute غير مسموح به عند رفض :other.',
    'prohibited_unless'    => 'حقل :attribute غير مسموح به إلا إذا كان :other ضمن :values.',
    'prohibits'            => 'وجود حقل :attribute يمنع وجود :other.',
    'regex'                => 'صيغة :attribute غير صحيحة.',
    'required'             => 'حقل :attribute مطلوب.',
    'required_array_keys'  => 'يجب أن يحتوي :attribute على المفاتيح: :values.',
    'required_if'          => 'حقل :attribute مطلوب عندما يكون :other هو :value.',
    'required_if_accepted' => 'حقل :attribute مطلوب عند قبول :other.',
    'required_if_declined' => 'حقل :attribute مطلوب عند رفض :other.',
    'required_unless'      => 'حقل :attribute مطلوب إلا إذا كان :other ضمن :values.',
    'required_with'        => 'حقل :attribute مطلوب عند وجود :values.',
    'required_with_all'    => 'حقل :attribute مطلوب عند وجود :values.',
    'required_without'     => 'حقل :attribute مطلوب عند عدم وجود :values.',
    'required_without_all' => 'حقل :attribute مطلوب عند عدم وجود أي من :values.',
    'same'                 => 'يجب أن يتطابق :attribute مع :other.',

    'size' => [
        'array'   => 'يجب أن يحتوي :attribute على :size عنصر بالضبط.',
        'file'    => 'يجب أن يكون حجم :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute :size.',
        'string'  => 'يجب أن يكون طول :attribute :size حرفًا.',
    ],

    'starts_with'          => 'يجب أن يبدأ :attribute بأحد القيم التالية: :values.',
    'string'               => 'يجب أن يكون :attribute نصًا.',
    'timezone'             => 'يجب أن يكون :attribute منطقة زمنية صحيحة.',
    'unique'               => ':attribute مستخدم من قبل.',
    'uploaded'             => 'فشل رفع :attribute.',
    'uppercase'            => 'يجب أن يكون :attribute بحروف كبيرة.',
    'url'                  => 'يجب أن يكون :attribute رابطًا صحيحًا.',
    'ulid'                 => 'يجب أن يكون :attribute بصيغة ULID صحيحة.',
    'uuid'                 => 'يجب أن يكون :attribute بصيغة UUID صحيحة.',

    /*
    |--------------------------------------------------------------------------
    | رسائل مخصّصة لحقل + قاعدة معيّنة
    |--------------------------------------------------------------------------
    | لو عايز رسالة مخصوصة لحقل واحد مع قاعدة واحدة بس، مكانها هنا.
    | الصيغة: 'اسم_الحقل' => ['اسم_القاعدة' => 'الرسالة']
    */

    'custom' => [

        'password' => [
            'min' => 'يجب ألا تقل كلمة المرور عن :min أحرف.',
        ],

        'permissions' => [
            'required' => 'لازم تختار صلاحية واحدة على الأقل.',
            'min'      => 'لازم تختار صلاحية واحدة على الأقل.',
        ],

        'permissions.*' => [
            'in' => 'إحدى الصلاحيات المختارة غير معروفة.',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | أسماء الحقول
    |--------------------------------------------------------------------------
    | الاسم اللي هيظهر مكان :attribute في الرسائل فوق.
    | أي حقل مش مذكور هنا هيظهر باسمه الخام زي ما هو مكتوب في الـ input.
    */

    'attributes' => [

        // حقول عامة
        'name'                  => 'الاسم',
        'email'                 => 'البريد الإلكتروني',
        'password'              => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'current_password'      => 'كلمة المرور الحالية',
        'img'                   => 'الصورة',
        'image'                 => 'الصورة',
        'file'                  => 'الملف',
        'title'                 => 'العنوان',
        'description'           => 'الوصف',
        'value'                 => 'القيمة',
        'type'                  => 'النوع',
        'status'                => 'الحالة',
        'order'                 => 'الترتيب',
        'group'                 => 'المجموعة',
        'key'                   => 'المفتاح',
        'phone'                 => 'رقم الهاتف',
        'url'                   => 'الرابط',
        'date'                  => 'التاريخ',
        'time'                  => 'الوقت',
        'remember'              => 'تذكرني',

        // المشرفين
        'role_id'               => 'الصلاحية',

        // الأدوار
        'permissions'           => 'الصلاحيات',
        'permissions.*'         => 'الصلاحية',

        // الإعدادات — بتتبعت باسم settings[key]
        'settings.site_name'         => 'اسم الموقع',
        'settings.site_email'        => 'البريد الإلكتروني للموقع',
        'settings.site_logo'         => 'شعار الموقع',
        'settings.site_favicon'      => 'أيقونة الموقع',
        'settings.app_env'           => 'بيئة التطبيق',
        'settings.app_timezone'      => 'المنطقة الزمنية',
        'settings.mail_driver'       => 'سيرفر البريد',
        'settings.mail_host'         => 'سيرفر SMTP',
        'settings.mail_port'         => 'منفذ البريد',
        'settings.mail_username'     => 'اسم مستخدم البريد',
        'settings.mail_password'     => 'كلمة مرور البريد',
        'settings.mail_encryption'   => 'تشفير البريد',
        'settings.mail_from_name'    => 'اسم المرسل',
        'settings.mail_from_address' => 'عنوان المرسل',
        'settings.facebook_url'      => 'رابط فيسبوك',
        'settings.twitter_url'       => 'رابط تويتر',
        'settings.instagram_url'     => 'رابط إنستغرام',
        'settings.linkedin_url'      => 'رابط لينكدإن',
        'settings.youtube_url'       => 'رابط يوتيوب',
        'settings.tiktok_url'        => 'رابط تيك توك',
        'settings.snapchat_url'      => 'رابط سناب شات',
        'settings.github_url'        => 'رابط جيت هب',
        'settings.telegram_url'      => 'رابط تيليجرام',
        'settings.whatsapp_url'      => 'رابط واتساب',
        'settings.phone_number'      => 'رقم الهاتف',

    ],

];
