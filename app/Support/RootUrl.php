<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

/**
 * دومين النظام من غير الاعتماد على APP_URL.
 *
 * في طلبات المتصفح لارافيل بياخد الدومين من الطلب نفسه، فـ APP_URL
 * مالوش لازمة. المشكلة في الأوامر المجدولة والطابور (إيميلات، تنبيهات):
 * مفيش طلب، فلارافيل بيرجع لـ APP_URL — ولو كان localhost، أي رابط
 * بيتبني هناك بيطلع على localhost.
 *
 * الحل: كل طلب متصفح بيسجّل دومينه، والكونسول بيستخدم آخر دومين اتسجّل.
 * APP_URL بقى احتياطي بس قبل أول زيارة بعد التنصيب.
 */
class RootUrl
{
    public const CACHE_KEY = 'app:root_url';

    /** من طلب متصفح: سجّل الدومين لو اتغيّر. */
    public static function remember(string $root): void
    {
        try {
            if (Cache::get(self::CACHE_KEY) !== $root) {
                Cache::forever(self::CACHE_KEY, $root);
            }
        } catch (\Throwable $e) {
            // الكاش واقع: الطلب يكمّل عادي
        }
    }

    /** في الكونسول: استخدم آخر دومين اتسجّل بدل APP_URL. */
    public static function applyToConsole(): void
    {
        try {
            $root = Cache::get(self::CACHE_KEY);
        } catch (\Throwable $e) {
            // الكاش واقع: أي أمر artisan مايقعش بسببه
            return;
        }

        if (! is_string($root) || $root === '') {
            return;
        }

        URL::forceRootUrl($root);

        if (str_starts_with($root, 'https://')) {
            URL::forceScheme('https');
        }
    }

    /** الدومين الحالي — من الطلب لو موجود، وإلا من آخر دومين اتسجّل. */
    public static function current(): string
    {
        if (! app()->runningInConsole() && request()) {
            return request()->root();
        }

        try {
            $root = Cache::get(self::CACHE_KEY);
        } catch (\Throwable $e) {
            $root = null;
        }

        return is_string($root) && $root !== '' ? $root : (string) config('app.url');
    }
}
