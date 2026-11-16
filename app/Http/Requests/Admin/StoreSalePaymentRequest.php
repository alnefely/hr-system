<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** تسجيل دفعة على فاتورة. */
class StoreSalePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_date'     => ['required', 'date'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_method'   => ['required', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ];
    }
}
