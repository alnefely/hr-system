<?php

namespace App\Http\Requests\Admin;

use App\Rules\AccessibleBranch;
use App\Rules\AccessibleEmployee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        // Normalize hire date
        if ($this->filled('hired_at') && !$this->filled('hire_date')) {
            $merge['hire_date'] = $this->input('hired_at');
        }

        // Normalize probation end date
        if ($this->has('probation_end_at') && !$this->filled('probation_end_date')) {
            $merge['probation_end_date'] = $this->input('probation_end_at');
        }

        // Normalize employment type to contract type
        if ($this->filled('employment_type') && !$this->filled('contract_type')) {
            $merge['contract_type'] = $this->input('employment_type');
        }

        // Normalize email to personal_email
        if ($this->filled('email') && !$this->filled('personal_email')) {
            $merge['personal_email'] = $this->input('email');
        }

        // Normalize img to avatar
        if ($this->filled('img') && !$this->filled('avatar')) {
            $merge['avatar'] = $this->input('img');
        }

        // Normalize bank account
        if ($this->filled('bank_account') && !$this->filled('bank_account_number')) {
            $merge['bank_account_number'] = $this->input('bank_account');
        }

        // Normalize code to employee_code
        if ($this->filled('code') && !$this->filled('employee_code')) {
            $merge['employee_code'] = $this->input('code');
        }

        // الأرقام بصيغة دولية موحّدة — الهاتف الأساسي هو اسم دخول البوابة،
        // والتوحيد قبل التحقق عشان قاعدة "مايتكررش" تقارن نفس الصيغة.
        foreach (['phone', 'alt_phone', 'emergency_contact_phone'] as $field) {
            if ($this->filled($field)) {
                $merge[$field] = \App\Support\PhoneNumber::toE164(
                    $this->input($field),
                    $this->input($field . '_country') ?: 'EG'
                );
            }
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id ?? $this->route('employee');

        return [
            // البيانات الشخصية
            'name'                            => ['required', 'string', 'max:150'],
            'national_id'                     => ['nullable', 'string', 'max:50', Rule::unique('employees', 'national_id')->ignore($employeeId)],
            'birth_date'                      => ['nullable', 'date'],
            'gender'                          => ['nullable', 'in:male,female'],
            'marital_status'                  => ['nullable', 'string', 'max:50'],
            'nationality'                     => ['nullable', 'string', 'max:100'],
            'img'                             => ['nullable', 'string', 'max:255', new \App\Rules\SafeFileReference],
            'avatar'                          => ['nullable', 'string', 'max:255', new \App\Rules\SafeFileReference],

            // بيانات التواصل
            // اسم الدخول لبوابة الموظف — لازم يبقى فريد (شامل المحذوفين)
            'phone'                           => ['required', 'string', 'max:50', Rule::unique('employees', 'phone')->ignore($employeeId)],
            'phone_country'                   => ['nullable', 'string', 'max:10'],
            'alt_phone'                       => ['nullable', 'string', 'max:50'],
            'alt_phone_country'               => ['nullable', 'string', 'max:10'],
            'email'                           => ['nullable', 'email', 'max:100'],
            'personal_email'                  => ['nullable', 'email', 'max:100'],
            'emergency_contact_name'          => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone'         => ['nullable', 'string', 'max:50'],
            'emergency_contact_phone_country' => ['nullable', 'string', 'max:10'],
            'address'                         => ['nullable', 'string'],

            // البيانات الوظيفية
            'code'                            => ['nullable', 'string', 'max:50', Rule::unique('employees', 'employee_code')->ignore($employeeId)],
            'employee_code'                   => ['nullable', 'string', 'max:50', Rule::unique('employees', 'employee_code')->ignore($employeeId)],
            'branch_id'                       => ['required', 'exists:branches,id', new AccessibleBranch],
            'job_title_id'                    => ['required', 'exists:job_titles,id'],
            'manager_id'                      => ['nullable', new AccessibleEmployee, Rule::notIn([$employeeId])],
            'hire_date'                       => ['required', 'date'],
            'hired_at'                        => ['nullable', 'date'],
            'probation_end_date'              => ['nullable', 'date'],
            'probation_end_at'                => ['nullable', 'date'],
            'contract_type'                   => ['nullable', 'in:full_time,part_time,contractor,freelancer'],
            'employment_type'                 => ['nullable', 'in:full_time,part_time,contractor,freelancer'],
            'status'                          => ['required', 'in:active,on_leave,terminated'],
            'notes'                           => ['nullable', 'string'],

            // البيانات المالية
            'salary'                          => ['nullable', 'numeric', 'min:0'],
            'basic_salary'                    => ['nullable', 'numeric', 'min:0'],
            'bank_name'                       => ['nullable', 'string', 'max:100'],
            'bank_account'                    => ['nullable', 'string', 'max:100'],
            'bank_account_number'             => ['nullable', 'string', 'max:100'],
            'insurance_number'                => ['nullable', 'string', 'max:100'],

            // إعدادات الموارد البشرية والدوام
            'annual_leave_entitlement'        => ['nullable', 'numeric', 'min:0'],
            'monthly_permission_hours_limit'  => ['nullable', 'numeric', 'min:0'],
            'daily_work_hours'                => ['nullable', 'numeric', 'min:0'],
            'custom_daily_rate'               => ['nullable', 'numeric', 'min:0'],
            'custom_hourly_rate'              => ['nullable', 'numeric', 'min:0'],

            // البدلات الشهرية الثابتة
            'allowances'                      => ['nullable', 'array'],
            'allowances.*.name'               => ['nullable', 'string', 'max:150'],
            'allowances.*.amount'             => ['nullable', 'numeric', 'min:0'],

            // دخول بوابة الموظف: كلمة المرور اختيارية — لو اتكتبت الدخول بيتفعّل
            // (أو بتتعاد تعيينها لو مفعّل). حسابات لوحة التحكم منفصلة تماماً
            // وبتتدار من شاشة المشرفين.
            'portal_password'                 => ['nullable', ...\App\Models\Employee::portalPasswordRules()],

            // المستندات والوثائق
            'documents'                       => ['nullable', 'array'],
            // الـ id لازم يبقى مستند تابع لنفس الموظف — من غير الشرط
            // ده حد يقدر يبعت id بتاع موظف تاني ويعدّل مستنداته.
            'documents.*.id'                  => [
                'nullable', 'integer',
                Rule::exists('employee_documents', 'id')->where(
                    fn ($q) => $q->where('employee_id', $this->route('employee')?->id)
                ),
            ],
            'documents.*.document_type'       => ['nullable', 'string', 'max:50'],
            'documents.*.title'               => ['nullable', 'string', 'max:150'],
            'documents.*.file_path'           => ['nullable', 'string', 'max:255', new \App\Rules\SafeFileReference],
            'documents.*.issue_date'          => ['nullable', 'date'],
            'documents.*.expiry_date'         => ['nullable', 'date', 'after_or_equal:documents.*.issue_date'],
            'documents.*.is_verified'         => ['nullable', 'boolean'],
        ];
    }
}
