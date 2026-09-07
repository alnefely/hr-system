<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeSalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'    => ['required', 'exists:employees,id'],
            'basic_salary'   => ['required', 'numeric', 'min:0'],
            'allowances'     => ['nullable', 'numeric', 'min:0'],
            'currency'       => ['required', 'string', 'max:10'],
            'effective_date' => ['required', 'date'],
            'change_reason'  => ['required', 'in:hiring,promotion,annual_raise,settlement,other'],
            'notes'          => ['nullable', 'string'],
        ];
    }
}
