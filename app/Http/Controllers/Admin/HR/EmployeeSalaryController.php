<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmployeeSalaryRequest;
use App\Models\EmployeeSalary;
use App\Services\HR\EmployeeSalaryService;
use Exception;
use Illuminate\Http\Request;

class EmployeeSalaryController extends Controller
{
    public function __construct(
        protected EmployeeSalaryService $salaryService
    ) {}

    /**
     * حفظ سجل تعديل راتب جديد
     */
    public function store(EmployeeSalaryRequest $request)
    {
        $salary = $this->salaryService->create($request->validated());

        return redirect()->to(route('admin.employees.show', $salary->employee_id) . '#tab-salary')
            ->with('success', __('salary.messages.created'));
    }

    /**
     * تحديث سجل راتب
     */
    public function update(EmployeeSalaryRequest $request, EmployeeSalary $salary)
    {
        $this->salaryService->update($salary, $request->validated());

        return redirect()->to(route('admin.employees.show', $salary->employee_id) . '#tab-salary')
            ->with('success', __('salary.messages.updated'));
    }

    /**
     * نقل سجل الراتب لسلة المحذوفات
     */
    public function destroy(EmployeeSalary $salary)
    {
        try {
            $employeeId = $salary->employee_id;
            $this->salaryService->delete($salary);
            return redirect()->to(route('admin.employees.show', $employeeId) . '#tab-salary')
                ->with('success', __('salary.messages.deleted'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * استرجاع سجل الراتب
     */
    public function restore(int $id)
    {
        $this->salaryService->restore($id);

        return redirect()->back()->with('success', __('salary.messages.restored'));
    }

    /**
     * الحذف النهائي لسجل الراتب
     */
    public function forceDelete(int $id)
    {
        try {
            $this->salaryService->forceDelete($id);
            return redirect()->back()->with('success', __('salary.messages.force_deleted'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
