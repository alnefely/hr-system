<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalesChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $channelId = $this->route('sales_channel')?->id ?? $this->route('sales_channel');

        return [
            'code'      => ['required', 'string', 'max:30', Rule::unique('sales_channels', 'code')->ignore($channelId)],
            'name'      => ['required', 'string', 'max:150'],
            'type'      => ['required', 'string', 'max:100'],
            'notes'     => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
