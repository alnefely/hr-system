<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')?->id ?? $this->route('customer');

        return [
            'code'             => ['required', 'string', 'max:30', Rule::unique('customers', 'code')->ignore($customerId)],
            'name'             => ['required', 'string', 'max:150'],
            'company_name'     => ['nullable', 'string', 'max:150'],
            'phone'            => ['nullable', 'string', 'max:50'],
            'phone_country'    => ['nullable', 'string', 'max:10'],
            'email'            => ['nullable', 'email', 'max:100'],
            'tax_number'       => ['nullable', 'string', 'max:50'],
            'address'          => ['nullable', 'string', 'max:500'],
            'customer_type'    => ['nullable', 'in:individual,company'],
            'source'           => ['nullable', 'string', 'max:100'],
            'customer_source_id' => ['nullable', 'integer', 'exists:customer_sources,id'],
            'industry'         => ['nullable', 'string', 'max:100'],
            'contact_person'   => ['nullable', 'string', 'max:150'],
            'contact_position' => ['nullable', 'string', 'max:100'],
            'secondary_phone'  => ['nullable', 'string', 'max:50'],
            'secondary_phone_country' => ['nullable', 'string', 'max:10'],
            'website'          => ['nullable', 'url', 'max:200'],
            'credit_limit'     => ['nullable', 'numeric', 'min:0'],
            'payment_terms'    => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:1000'],
            'is_active'        => ['nullable', 'boolean'],
        ];
    }
}
