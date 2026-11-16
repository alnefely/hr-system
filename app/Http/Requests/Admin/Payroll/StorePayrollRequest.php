<?php

namespace App\Http\Requests\Admin\Payroll;

use App\Rules\AccessibleBranch;
use Illuminate\Foundation\Http\FormRequest;

/** إصدار مسير رواتب لشهر (لفرع واحد أو لكل الفروع). */
class StorePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year'      => ['required', 'integer', 'min:2020', 'max:2050'],
            'month'     => ['required', 'integer', 'min:1', 'max:12'],
            'branch_id' => ['nullable', 'exists:branches,id', new AccessibleBranch],
            'notes'     => ['nullable', 'string'],
        ];
    }
}
