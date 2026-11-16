<?php

use App\Mail\AppMail;
use App\Models\Settings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

require_once __DIR__ . '/AdminRoles.php';
require_once __DIR__ . '/ActivityHelper.php';

// جلب قيمة setting معينة بالـ key
if (!function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}

// تحديث أو إنشاء setting جديدة
if (!function_exists('setting_set')) {
    function setting_set(string $key, mixed $value): void
    {
        Settings::set($key, $value);
    }
}

// اعدادات ثيم vuexy الافتراضية
if (!function_exists('vuexy_settings')) {
    function vuexy_setting($key = null, $customizerHide= false): mixed
    {
        $settings = [
            'lang' => 'ar',
            // 'class' => 'layout-wide customizer-hide',
            'class' => 'layout-navbar-fixed layout-menu-fixed layout-compact',
            'dir' => 'rtl',
            'data_skin' => 'default',
            'data_bs_theme' => 'light',
            'data_assets_path' => '/admin/',
            'data_template' => 'vertical-menu-template'
        ];
        if ($key === null) {
            return $settings;
        }
        if ($customizerHide) {
            $settings['class'] .= ' customizer-hide';
            
        }
        return $settings[$key];
    }
}


// تحديث قيمة في ملف .env
if (!function_exists('setEnvVal')) {
    function setEnvVal($key, $value)
    {
        $path = base_path('.env');

        if (!file_exists($path) || !is_writable($path)) {
            throw new Exception("The .env file does not exist or is not writable.");
        }

        $value = '"' . addslashes($value) . '"'; // دعم القيم التي تحتوي على رموز أو فراغات

        $env = file($path, FILE_IGNORE_NEW_LINES);
        $envUpdated = false;

        foreach ($env as $index => $line) {
            if (strpos($line, "$key=") === 0) {
                $env[$index] = "$key=$value";
                $envUpdated = true;
                break;
            }
        }

        if (!$envUpdated) {
            $env[] = "$key=$value";
        }

        $envContent = implode("\n", $env) . "\n";
        file_put_contents($path, $envContent);
    }
}

// تنسيق التاريخ حسب توقيت التطبيق
if (!function_exists('FormatDate')) {
    function FormatDate($date)
    {
        // Carbon::parse(null) بيرجّع الوقت الحالي، فسجل من غير تاريخ كان
        // بيتعرض كأنه اتعمل دلوقتي.
        if ($date === null || $date === '') {
            return null;
        }

        return Carbon::parse($date)->setTimezone(config('app.timezone'))->format('Y-m-d h:i A');
    }
}

// دالة لإرسال إيميل باستخدام قالب ماركداون
if (!function_exists('sendEmail')) {
    function sendEmail(string $to, string $subject, string $markdownView, array $data= []): void
    {
        Mail::to($to)->send(new AppMail(
            title: $subject,
            markdownView: $markdownView,
            data: $data,
        ));
    }
}

// demo image url
if (!function_exists('demo')) {
    function demo(string $type = 'image'): string
    {
        return match ($type) {
            'img' => '/admin/demo.png',
            'pdf' => '/admin/demo.pdf',
            'audio' => '/admin/demo.mp3',
            'video' => '/admin/demo.mp4',
            default => '/admin/demo.png',
        };
    }
}