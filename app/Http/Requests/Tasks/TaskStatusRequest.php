<?php

namespace App\Http\Requests\Tasks;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;

class TaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', TaskStatus::rule()],
            'note'   => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function status(): TaskStatus
    {
        return TaskStatus::from($this->validated('status'));
    }
}
