<?php

namespace App\Http\Requests\Admin\Payroll;

use Illuminate\Foundation\Http\FormRequest;

/** تعديل تفاصيل بند مسير: البدلات والخصومات سطر سطر. */
class UpdatePayrollItemDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'allowance_details'          => ['nullable', 'array'],
            'allowance_details.*.title'  => ['required', 'string', 'max:150'],
            'allowance_details.*.amount' => ['required', 'numeric', 'min:0'],
            'deduction_details'          => ['nullable', 'array'],
            'deduction_details.*.title'  => ['required', 'string', 'max:150'],
            'deduction_details.*.amount' => ['required', 'numeric', 'min:0'],
            'payment_method'             => ['required', 'in:cash,bank_transfer'],
            'notes'                      => ['nullable', 'string'],
        ];
    }
}
