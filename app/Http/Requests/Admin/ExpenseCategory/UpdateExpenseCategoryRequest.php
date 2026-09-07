<?php

namespace App\Http\Requests\Admin\ExpenseCategory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return admin_can('expense_category_update');
    }

    public function rules(): array
    {
        $id = $this->route('expense_category')?->id;

        return [
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['nullable', 'string', 'max:50', 'unique:expense_categories,code,' . $id],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }
}
