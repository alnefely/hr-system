<?php

namespace App\Http\Requests\Admin;

use App\Rules\AccessibleEmployee;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** قيمة افتراضية للنوع عشان الحقل مايبقاش إجبارياً على المستخدم. */
    protected function prepareForValidation(): void
    {
        if (!$this->filled('document_type')) {
            $this->merge(['document_type' => 'other']);
        }
    }

    public function rules(): array
    {
        return [
            'employee_id'   => ['required', new AccessibleEmployee],
            // النوع مش إجباري من المستخدم: لو مابعتش بنحط 'other'
            // في prepareForValidation عشان العمود NOT NULL.
            'document_type' => ['required', 'string', 'max:100'],
            'title'         => ['nullable', 'string', 'max:200'],
            'file_path'     => ['required', 'string', 'max:255', new \App\Rules\SafeFileReference],
            'issue_date'    => ['nullable', 'date'],
            'expiry_date'   => ['nullable', 'date'],
            'is_verified'   => ['nullable', 'boolean'],
            'notes'         => ['nullable', 'string'],
        ];
    }
}
