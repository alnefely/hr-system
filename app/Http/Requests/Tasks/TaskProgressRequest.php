<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class TaskProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'note'     => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['progress' => __('task.fields.progress'), 'note' => __('task.fields.note')];
    }
}
