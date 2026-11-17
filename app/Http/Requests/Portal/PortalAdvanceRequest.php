<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

/** طلب سلفة من الموظف لنفسه — تاريخ الطلب هو النهارده. */
class PortalAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount'             => ['required', 'numeric', 'min:1'],
            'installments_count' => ['required', 'integer', 'min:1', 'max:36'],
            'start_date'         => ['required', 'date', 'after_or_equal:today'],
            'reason'             => ['required', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'amount'             => __('portal.advances.amount'),
            'installments_count' => __('portal.advances.installments'),
            'start_date'         => __('portal.advances.start'),
            'reason'             => __('portal.fields.reason'),
        ];
    }
}
