<?php

namespace App\Http\Requests\Admin\Expense;

use App\Rules\AccessibleBranch;
use App\Rules\AccessibleEmployee;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return admin_can('expense_create');
    }

    public function rules(): array
    {
        return [
            'title'               => ['required', 'string', 'max:255'],
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'branch_id'           => ['required', 'exists:branches,id', new AccessibleBranch],
            'employee_id'         => ['nullable', new AccessibleEmployee],
            'amount'              => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'currency'            => ['nullable', 'string', 'max:10'],
            'expense_date'        => ['required', 'date'],
            'payment_method'      => ['required', 'in:cash,bank_transfer,card'],
            'invoice_number'      => ['nullable', 'string', 'max:100'],
            'attachment'          => ['nullable', 'string', 'max:1000'],
            'notes'               => ['nullable', 'string', 'max:2000'],
        ];
    }
}
