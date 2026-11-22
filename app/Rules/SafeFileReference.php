<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * مسار ملف من مدير الملفات (صورة، مرفق، مستند).
 *
 * الحقول دي كانت "أي نص" وبتتطبع جوّه src أو href. قيمة زي
 * `x" onerror="...` أو `javascript:...` كانت بتنفّذ كود في جلسة المشرف
 * اللي بيفتح الصفحة. المسموح: مسار نسبي (uploads/... أو /uploads/...)
 * أو رابط http/https — من غير علامات تنصيص أو أقواس.
 */
class SafeFileReference implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! self::isSafe($value)) {
            $fail(__('validation.safe_file_reference'));
        }
    }

    public static function isSafe(string $value): bool
    {
        $value = trim($value);

        if ($value === '' || preg_match('/["\'<>\s\\\\`]/u', $value)) {
            return false;
        }

        // رابط بيبدأ بـ // بياخد بروتوكول الصفحة ويروح لدومين تاني
        if (str_starts_with($value, '//')) {
            return false;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);

        return $scheme === null || $scheme === false || in_array(strtolower($scheme), ['http', 'https'], true);
    }
}
