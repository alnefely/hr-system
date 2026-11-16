<?php

namespace App\Http\Requests\Admin\Payroll;

use Illuminate\Foundation\Http\FormRequest;

/** تعديل جماعي لبدلات وخصومات بنود المسير. */
class UpdatePayrollItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'                  => ['required', 'array'],
            'items.*.allowances'     => ['nullable', 'numeric', 'min:0'],
            'items.*.deductions'     => ['nullable', 'numeric', 'min:0'],
            'items.*.payment_method' => ['required', 'in:cash,bank_transfer'],
            'items.*.notes'          => ['nullable', 'string'],
        ];
    }
}
