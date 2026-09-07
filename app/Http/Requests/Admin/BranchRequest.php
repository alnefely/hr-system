<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branchId = $this->route('branch')?->id ?? $this->route('branch');

        return [
            'name'      => ['required', 'string', 'max:150'],
            'code'      => ['nullable', 'string', 'max:50', Rule::unique('branches', 'code')->ignore($branchId)],
            'country'   => ['required', 'string', 'max:100'],
            'city'      => ['nullable', 'string', 'max:100'],
            'address'   => ['nullable', 'string', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'email'     => ['nullable', 'email', 'max:100'],
            'currency'  => ['required', 'string', 'max:10'],
            'timezone'  => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
