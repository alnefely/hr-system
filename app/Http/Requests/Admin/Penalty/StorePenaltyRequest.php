<?php

namespace App\Http\Requests\Admin\Penalty;

use App\Models\Employee;
use App\Rules\AccessibleEmployee;
use App\Services\HR\PayrollRules;
use Illuminate\Foundation\Http\FormRequest;

class StorePenaltyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'      => ['required', new AccessibleEmployee],
            'penalty_type'     => ['required', 'in:days_deduction,amount_deduction,warning'],
            'days_count'       => ['nullable', 'numeric', 'min:0'],
            'deduction_amount' => ['nullable', 'numeric', 'min:0'],
            'penalty_date'     => ['required', 'date'],
            'reason'           => ['required', 'string', 'max:1000'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * البيانات الجاهزة للحفظ حسب نوع الجزاء.
     *
     * خصم الأيام بيتحوّل لمبلغ وقت التسجيل بسعر اليوم الحالي، والمبلغ ده
     * هو اللي بيتعرض ويتعمد ويدخل المسير — حتى لو الراتب اتغيّر بعدها.
     */
    public function penaltyData(): array
    {
        $data = $this->validated();

        switch ($data['penalty_type']) {
            case 'days_deduction':
                $data['days_count'] = $data['days_count'] ?? 1;
                $employee = Employee::find($data['employee_id']);
                $data['deduction_amount'] = $employee
                    ? round(PayrollRules::dailyRate($employee) * $data['days_count'], 2)
                    : 0;
                break;

            case 'amount_deduction':
                $data['days_count'] = 0;
                $data['deduction_amount'] = $data['deduction_amount'] ?? 0;
                break;

            default: // إنذار
                $data['days_count'] = 0;
                $data['deduction_amount'] = 0;
        }

        return $data;
    }
}
