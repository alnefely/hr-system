<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $jobTitleId = $this->route('job_title')?->id ?? $this->route('job_title');

        return [
            'name'        => ['required', 'string', 'max:150', Rule::unique('job_titles', 'name')->ignore($jobTitleId)],
            'description' => ['nullable', 'string'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }
}
