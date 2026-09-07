<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'         => ['required', 'exists:employees,id'],
            'skill_name'          => ['required', 'string', 'max:150'],
            'proficiency_level'   => ['required', 'in:beginner,intermediate,advanced,expert'],
            'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'is_core'             => ['nullable', 'boolean'],
            'evaluation_source'   => ['required', 'in:self,manager'],
        ];
    }
}
