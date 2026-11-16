<?php

namespace App\Http\Requests\Admin\Advance;

use Illuminate\Foundation\Http\FormRequest;

/** سداد نقدي مباشر على سلفة. */
class RecordAdvancePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'repayment_date' => ['required', 'date'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ];
    }
}
