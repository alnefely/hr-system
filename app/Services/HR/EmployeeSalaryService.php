<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use Illuminate\Database\Eloquent\Builder;

class EmployeeSalaryService
{
    /**
     * استعلام سلة محذوفات سجل الرواتب
     */
    public function trashQuery(int $employeeId): Builder
    {
        return EmployeeSalary::onlyTrashed()
            ->where('employee_id', $employeeId)
            ->with('approver:id,name');
    }

    /**
     * عدد الرواتب المحذوفة لموظف
     */
    public function trashCount(int $employeeId): int
    {
        return EmployeeSalary::onlyTrashed()->where('employee_id', $employeeId)->count();
    }

    /**
     * إضافة سجل تعديل راتب جديد
     */
    public function create(array $data): EmployeeSalary
    {
        $data['approved_by'] = auth('admin')->id();

        $salary = EmployeeSalary::create($data);

        $this->syncEmployeeBasicSalary($salary->employee_id);

        return $salary;
    }

    /**
     * تعديل سجل راتب
     */
    public function update(EmployeeSalary $salary, array $data): EmployeeSalary
    {
        $salary->update($data);

        $this->syncEmployeeBasicSalary($salary->employee_id);

        return $salary;
    }

    /**
     * نقل سجل الراتب لسلة المحذوفات (Soft Delete)
     */
    public function delete(EmployeeSalary $salary): bool
    {
        $employeeId = $salary->employee_id;
        $deleted = (bool) $salary->delete();

        $this->syncEmployeeBasicSalary($employeeId);

        return $deleted;
    }

    /**
     * استرجاع سجل الراتب من السلة
     */
    public function restore(int $id): bool
    {
        $salary = EmployeeSalary::onlyTrashed()->findOrFail($id);
        $restored = (bool) $salary->restore();

        $this->syncEmployeeBasicSalary($salary->employee_id);

        return $restored;
    }

    /**
     * الحذف النهائي لسجل الراتب
     */
    public function forceDelete(int $id): bool
    {
        $salary = EmployeeSalary::onlyTrashed()->findOrFail($id);
        $employeeId = $salary->employee_id;
        $deleted = (bool) $salary->forceDelete();

        $this->syncEmployeeBasicSalary($employeeId);

        return $deleted;
    }

    /**
     * مزامنة employees.basic_salary مع أحدث سجل راتب ساري.
     *
     * المسير بيقرا basic_salary من جدول الموظفين مش من تاريخ الرواتب،
     * فمن غير المزامنة دي كان المستخدم يسجّل علاوة والمسير يفضل
     * يصرف بالراتب القديم.
     *
     * "الساري" = أحدث سجل تاريخه النهارده أو قبله — عشان العلاوات
     * المجدولة للمستقبل ماتتطبقش قبل موعدها.
     */
    public function syncEmployeeBasicSalary(int $employeeId): void
    {
        $latest = EmployeeSalary::withoutGlobalScope('branch')
            ->where('employee_id', $employeeId)
            ->where('effective_date', '<=', now()->toDateString())
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();

        if (!$latest) {
            return;
        }

        Employee::withoutGlobalScope('branch')
            ->where('id', $employeeId)
            ->update(['basic_salary' => $latest->basic_salary]);
    }
}
