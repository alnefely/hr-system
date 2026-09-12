<?php

namespace App\Services\Links;

use App\Models\Link;
use RuntimeException;

/**
 * توليد أكواد الروابط المختصرة.
 *
 * الكود عشوائي مش تسلسلي عشان محدش يقدر يعدّ روابط الشركة أو
 * يخمّن رابط موجود بتغيير رقم.
 */
class ShortCodeGenerator
{
    /**
     * من غير 0 O 1 l I — الحروف دي بتتلخبط عند الكتابة اليدوية
     * أو القراءة من ورق.
     */
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    /**
     * أكواد محجوزة.
     *
     * دي مسارات المشروع الفعلية — لو حد اختصر رابط بكود اسمه admin
     * الراوت هيتلخبط. القايمة دي لازم تتحدّث لو اتضافت مسارات جديدة.
     */
    private const RESERVED = [
        'admin', 'filemanager', 'login', 'logout', 'password', 'home',
        's', 'api', 'assets', 'storage', 'up', 'health',
        'links', 'link', 'stats', 'qr', 'dashboard', 'settings',
    ];

    /**
     * كود فريد جديد.
     *
     * 57 حرف × 7 خانات ≈ 1.95 كوادريليون احتمال، فالتصادم عملياً
     * شبه مستحيل — الـ retry موجود للاحتياط مش للاستخدام المتوقّع.
     */
    public function generate(?int $length = null, int $maxAttempts = 6): string
    {
        $length = $length ?? (int) config('links.code_length', 7);
        $max    = strlen(self::ALPHABET) - 1;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $code = '';

            for ($i = 0; $i < $length; $i++) {
                $code .= self::ALPHABET[random_int(0, $max)];
            }

            if ($this->isAvailable($code)) {
                return $code;
            }

            // بعد ٣ محاولات فاشلة زوّد الطول بدل ما تفضل تلف على نفس المساحة
            if ($attempt === 2) {
                $length++;
            }
        }

        throw new RuntimeException(__('link.errors.generate_failed'));
    }

    /** الكود متاح؟ */
    public function isAvailable(string $code): bool
    {
        if (in_array(strtolower($code), self::RESERVED, true)) {
            return false;
        }

        // withoutGlobalScopes ضروري: تفرّد الكود عام على مستوى النظام،
        // مش لكل فرع — من غيرها مشرف فرع مش هيشوف كود فرع تاني
        // فيتولّد نفس الكود مرتين ويقع على الـ unique index.
        //
        // withTrashed كمان: الكود المحذوف لسه محجوز، عشان رابط قديم
        // اتشيّر مايفتحش وجهة جديدة لو الكود اتعاد استخدامه.
        //
        // ملاحظة: مافيش whereRaw('BINARY ...') — العمود نفسه utf8mb4_bin
        // فالمقارنة case-sensitive أصلاً، و BINARY كان هيمنع MySQL من
        // استخدام الـ unique index (full scan على أكتر استعلام تكراراً).
        return ! Link::withTrashed()
            ->withoutGlobalScopes()
            ->where('short_code', $code)
            ->exists();
    }

    /** الحد الأدنى لطول الكود المخصص. */
    public const MIN_LENGTH = 3;

    /**
     * الحد الأقصى — نفس طول الكود المولّد.
     *
     * مصدر واحد: الحقل في الشاشة والـ FormRequest بيقرأوا من هنا،
     * فلو الإعداد اتغيّر التلاتة بيتغيّروا مع بعض.
     */
    public static function maxLength(): int
    {
        return max(self::MIN_LENGTH, (int) config('links.code_length', 7));
    }

    /**
     * تحقّق من كود مخصص كتبه المستخدم.
     *
     * بيرجع ليستة أخطاء — فاضية يعني الكود سليم.
     */
    public function validateCustom(string $code): array
    {
        $errors = [];

        $pattern = '/^[A-Za-z0-9_-]{' . self::MIN_LENGTH . ',' . self::maxLength() . '}$/';

        if (! preg_match($pattern, $code)) {
            $errors[] = __('link.errors.code_format', [
                'min' => self::MIN_LENGTH,
                'max' => self::maxLength(),
            ]);
        }

        if (in_array(strtolower($code), self::RESERVED, true)) {
            $errors[] = __('link.errors.code_reserved');
        }

        if (empty($errors) && ! $this->isAvailable($code)) {
            $errors[] = __('link.errors.code_taken');
        }

        return $errors;
    }
}
