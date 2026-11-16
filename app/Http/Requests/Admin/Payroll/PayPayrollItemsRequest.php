<?php

namespace App\Http\Requests\Admin\Payroll;

use Illuminate\Foundation\Http\FormRequest;

/**
 * صرف بند أو بنود محددة.
 *
 * مفيش exists على كل id: كانت بتعمل استعلام لكل عنصر، والصرف نفسه
 * مقيّد ببنود المسير ده فأي id غريب بيتجاهل.
 */
class PayPayrollItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'payment_method' => ['nullable', 'in:cash,bank_transfer'],
        ];

        // صرف المحدد بس محتاج قائمة البنود؛ صرف بند واحد البند في الرابط
        if ($this->routeIs('admin.payrolls.pay-selected')) {
            $rules['item_ids']   = ['required', 'array'];
            $rules['item_ids.*'] = ['integer'];
        }

        return $rules;
    }
}
