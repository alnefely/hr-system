<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class TaskAssigneesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['assignee_ids' => array_values(array_filter((array) $this->input('assignee_ids', [])))]);
    }

    public function rules(): array
    {
        return [
            'assignee_ids'   => ['array', 'max:50'],
            'assignee_ids.*' => ['integer'],
        ];
    }
}
