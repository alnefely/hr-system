<?php

namespace App\Http\Requests\Admin\Expense;

use App\Rules\AccessibleBranch;
use Illuminate\Foundation\Http\FormRequest;

/** تحديد مخصص العهدة لفرع في شهر. */
class UpdatePettyCashBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id'        => ['required', 'exists:branches,id', new AccessibleBranch],
            'year'             => ['required', 'integer', 'min:2020', 'max:2100'],
            'month'            => ['required', 'integer', 'min:1', 'max:12'],
            'allocated_amount' => ['required', 'numeric', 'min:0'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ];
    }
}
