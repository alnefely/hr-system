<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $saleId = $this->route('sale')?->id ?? $this->route('sale');

        return [
            'invoice_number'   => ['required', 'string', 'max:50', Rule::unique('sales', 'invoice_number')->ignore($saleId)],
            'customer_id'      => ['nullable', 'exists:customers,id'],
            'sale_type'        => ['required', 'in:external_project,app_service,direct_sale'],
            'sales_channel_id' => ['nullable', 'exists:sales_channels,id'],
            'sales_channel'    => ['nullable', 'string', 'max:100'],
            'branch_id'        => ['required', 'exists:branches,id'],
            'sale_date'        => ['required', 'date'],
            'subtotal'         => ['required', 'numeric', 'min:0'],
            'tax_amount'       => ['nullable', 'numeric', 'min:0'],
            'discount_amount'  => ['nullable', 'numeric', 'min:0'],
            'total_amount'     => ['required', 'numeric', 'min:0'],
            'currency'         => ['nullable', 'string', 'max:10'],
            'paid_amount'      => ['nullable', 'numeric', 'min:0'],
            'due_amount'       => ['nullable', 'numeric', 'min:0'],
            'payment_status'   => ['required', 'in:paid,partially_paid,unpaid'],
            'payment_method'   => ['required', 'in:bank_transfer,cash,card,cheque'],
            'attachment'       => ['nullable', 'string', 'max:500'],
            'notes'            => ['nullable', 'string', 'max:1000'],
            'items'            => ['nullable', 'array'],
            'items.*.item_name'=> ['nullable', 'string', 'max:200'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit_price'=> ['nullable', 'numeric', 'min:0'],
            'items.*.total_price'=> ['nullable', 'numeric', 'min:0'],
        ];
    }
}
