<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * حماية الوجهة من SSRF ومن اللوب اللانهائي.
 *
 * من غير الحماية دي الموديول بيبقى أداة يقدر بيها أي مستخدم يوصل
 * لخدمات الشبكة الداخلية عن طريق السيرفر نفسه.
 */
class SafeUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail(__('link.errors.url_invalid'));

            return;
        }

        $parts = parse_url($value);

        if (! $parts || empty($parts['scheme']) || empty($parts['host'])) {
            $fail(__('link.errors.url_invalid'));

            return;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            $fail(__('link.errors.url_scheme'));

            return;
        }

        $host = strtolower($parts['host']);

        // منع اختصار رابط من نفس النظام — وإلا التحويل يلف على نفسه للأبد
        if ($this->isSelfHost($host, $parts['path'] ?? '')) {
            $fail(__('link.errors.url_self'));

            return;
        }

        // منع الشبكة الداخلية بالاسم
        if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.internal')
            || str_ends_with($host, '.localhost')) {
            $fail(__('link.errors.url_internal'));

            return;
        }

        // ومنعها بالـ IP كمان — سواء مكتوب صريح أو الدومين بيحل عليه
        if (! $this->isPublicIp($host)) {
            $fail(__('link.errors.url_internal'));
        }
    }

    /** الرابط بيشاور على نفس النظام؟ */
    private function isSelfHost(string $host, string $path): bool
    {
        $appHost = strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));

        if ($appHost === '' || $host !== $appHost) {
            return false;
        }

        // نفس الدومين مسموح إلا لو بيشاور على مسار التحويل نفسه
        $prefix = trim((string) config('links.path_prefix', 's'), '/');

        return str_starts_with(ltrim($path, '/'), $prefix . '/');
    }

    /**
     * الاستضافة عامة مش داخلية؟
     *
     * ملاحظة أمنية: الفحص بيتم وقت الحفظ، والـ DNS ممكن يتغيّر بعده
     * (DNS rebinding). مقبول هنا لأن الموديول داخلي ومحكوم بصلاحية،
     * لكن ماينفعش يتعمل عليه اعتماد لو اتفتح لمستخدمين مجهولين.
     */
    private function isPublicIp(string $host): bool
    {
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);

        // الاسم مش بيحل لـ IP — سيبه يعدّي، ممكن يكون دومين لسه بيتسجّل
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
