<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class VendorSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'             => ['required', 'string', 'max:200'],
            'vendor_id'         => ['required', 'exists:vendors,id'],
            'branch_id'         => ['nullable', 'exists:branches,id'],
            'type'              => ['required', 'in:subscription,contract,one_time'],
            'billing_cycle'     => ['required', 'in:monthly,quarterly,semi_annually,annually,one_time'],
            'cost'              => ['required', 'numeric', 'min:0'],
            'currency'          => ['required', 'string', 'max:10'],
            'start_date'        => ['required', 'date'],
            'next_billing_date' => ['nullable', 'date'],
            'auto_renew'        => ['nullable', 'boolean'],
            'status'            => ['required', 'in:active,pending,expired,cancelled'],
            'attachment'        => ['nullable', 'string', 'max:500'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ];
    }
}
