<?php

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email:filter'],
            'password' => ['required', 'string'],
        ];
    }

    /** مفتاح عدّاد المحاولات: الإيميل + الـ IP. */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('email')) . '|' . $this->ip());
    }
}
