<?php

namespace App\Http\Requests\Admin\Advance;

use App\Rules\AccessibleEmployee;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'        => ['required', new AccessibleEmployee],
            'amount'             => ['required', 'numeric', 'min:1'],
            'installments_count' => ['required', 'integer', 'min:1'],
            'advance_date'       => ['required', 'date'],
            'start_date'         => ['required', 'date'],
            'reason'             => ['nullable', 'string'],
            'notes'              => ['nullable', 'string'],
        ];
    }
}
