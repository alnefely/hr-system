<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** تسجيل دفعة على اشتراك مورد (مع تجديد اختياري). */
class RecordVendorPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount'                => ['required', 'numeric', 'min:0.01'],
            'payment_date'          => ['required', 'date'],
            'payment_method'        => ['required', 'in:bank_transfer,card,cash,cheque'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'invoice_number'        => ['nullable', 'string', 'max:100'],
            'notes'                 => ['nullable', 'string', 'max:1000'],
            'renew_next_date'       => ['nullable', 'date'],
        ];
    }
}
