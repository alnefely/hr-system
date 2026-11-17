<?php

namespace App\Support;

/**
 * توحيد أرقام الهواتف لصيغة دولية (+201021464469).
 *
 * مكوّن الهاتف (intl-tel-input) بيبعت الصيغة دي أصلاً، لكن ده خط الدفاع في
 * السيرفر: لو الـ JS ماشتغلش، أو بيانات قديمة اتسجّلت بالصيغة المحلية
 * ("10 21464469" أو "01021464469")، الرقم بيتوحّد قبل الحفظ وقبل الدخول —
 * رقم الهاتف هو اسم دخول بوابة الموظف فلازم يبقى بصيغة واحدة.
 */
class PhoneNumber
{
    /** نفس دول مكوّن الهاتف (onlyCountries) — ISO2 => كود الاتصال */
    public const DIAL_CODES = [
        'EG' => '20',  'SA' => '966', 'AE' => '971', 'BH' => '973',
        'KW' => '965', 'QA' => '974', 'OM' => '968', 'JO' => '962',
        'LB' => '961', 'MA' => '212', 'TN' => '216', 'DZ' => '213',
        'SY' => '963', 'IQ' => '964', 'YE' => '967', 'SD' => '249',
    ];

    public static function toE164(?string $number, ?string $country = 'EG'): ?string
    {
        if ($number === null || trim($number) === '') {
            return $number;
        }

        $number = trim($number);
        $digits = preg_replace('/\D+/', '', $number);

        if ($digits === '') {
            return $number;
        }

        // دولي بالفعل: +20... أو 0020...
        if (str_starts_with($number, '+')) {
            return '+' . $digits;
        }
        if (str_starts_with($digits, '00')) {
            return '+' . substr($digits, 2);
        }

        $dial = self::DIAL_CODES[strtoupper((string) $country)] ?? self::DIAL_CODES['EG'];

        // مكتوب بكود الدولة من غير + (201021464469)
        if (str_starts_with($digits, $dial) && strlen($digits) > strlen($dial) + 8) {
            return '+' . $digits;
        }

        // محلي: الصفر الأول (بادئة الاتصال المحلي) مالوش مكان في الصيغة الدولية
        return '+' . $dial . ltrim($digits, '0');
    }
}
