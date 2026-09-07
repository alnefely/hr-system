<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sourceId = $this->route('customer_source')?->id ?? $this->route('customer_source');

        return [
            'code'        => ['required', 'string', 'max:30', Rule::unique('customer_sources', 'code')->ignore($sourceId)],
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order'  => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code'        => __('customer_source.fields.code'),
            'name'        => __('customer_source.fields.name'),
            'description' => __('customer_source.fields.description'),
            'sort_order'  => __('customer_source.fields.sort_order'),
        ];
    }
}
