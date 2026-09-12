<?php

namespace App\Http\Requests\Admin\Link;

use App\Rules\SafeUrl;
use App\Services\Links\ShortCodeGenerator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return admin_can('link_update');
    }

    public function rules(): array
    {
        return [
            'original_url' => ['required', 'string', 'max:4000', new SafeUrl],
            'short_code'   => ['nullable', 'string',
                'min:' . ShortCodeGenerator::MIN_LENGTH,
                'max:' . ShortCodeGenerator::maxLength(),
                'regex:/^[A-Za-z0-9_-]+$/'],
            'title'        => ['nullable', 'string', 'max:255'],
            'notes'        => ['nullable', 'string', 'max:2000'],
            'branch_id'    => ['nullable', 'exists:branches,id'],
            'is_active'    => ['nullable', 'boolean'],

            // مافيش after:now هنا: رابط قديم منتهي المفروض ينفع يتحفظ
            // من غير ما المستخدم يضطر يغيّر تاريخه.
            'expires_at'   => ['nullable', 'date'],
            'max_clicks'   => ['nullable', 'integer', 'min:1', 'max:10000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'short_code.regex' => __('link.errors.code_format', [
                'min' => ShortCodeGenerator::MIN_LENGTH,
                'max' => ShortCodeGenerator::maxLength(),
            ]),
        ];
    }
}
