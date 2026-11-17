<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * دخول الموظف برقم الهاتف الأساسي + كلمة المرور.
 *
 * مكوّن الهاتف (intl-tel-input) بيبعت الرقم بصيغة دولية موحّدة (+2010...)
 * — نفس الصيغة اللي بيتخزّن بيها في ملف الموظف، فالمقارنة مباشرة.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'    => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ];
    }

    public function phone(): string
    {
        // لو المتصفح ماشغّلش الـ JS وبعت الرقم زي ما اتكتب (01021464469)
        // بيتوحّد لنفس صيغة التخزين (+201021464469).
        return (string) \App\Support\PhoneNumber::toE164((string) $this->input('phone'), 'EG');
    }

    /** عدّاد المحاولات: الرقم + الـ IP. */
    public function throttleKey(): string
    {
        return 'portal-login|' . Str::lower($this->phone()) . '|' . $this->ip();
    }
}
