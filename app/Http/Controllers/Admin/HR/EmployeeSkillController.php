<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmployeeSkillRequest;
use App\Models\EmployeeSkill;
use App\Services\HR\EmployeeSkillService;
use Exception;
use Illuminate\Http\Request;

class EmployeeSkillController extends Controller
{
    public function __construct(
        protected EmployeeSkillService $skillService
    ) {}

    /**
     * حفظ مهارة جديدة
     */
    public function store(EmployeeSkillRequest $request)
    {
        $skill = $this->skillService->create($request->validated());

        return redirect()->to(route('admin.employees.show', $skill->employee_id) . '#tab-skills')
            ->with('success', __('skill.messages.created'));
    }

    /**
     * تحديث بيانات المهارة
     */
    public function update(EmployeeSkillRequest $request, EmployeeSkill $skill)
    {
        $this->skillService->update($skill, $request->validated());

        return redirect()->to(route('admin.employees.show', $skill->employee_id) . '#tab-skills')
            ->with('success', __('skill.messages.updated'));
    }

    /**
     * نقل المهارة لسلة المحذوفات
     */
    public function destroy(EmployeeSkill $skill)
    {
        try {
            $employeeId = $skill->employee_id;
            $this->skillService->delete($skill);
            return redirect()->to(route('admin.employees.show', $employeeId) . '#tab-skills')
                ->with('success', __('skill.messages.deleted'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * استرجاع المهارة من السلة
     */
    public function restore(int $id)
    {
        $this->skillService->restore($id);

        return redirect()->back()->with('success', __('skill.messages.restored'));
    }

    /**
     * الحذف النهائي للمهارة
     */
    public function forceDelete(int $id)
    {
        try {
            $this->skillService->forceDelete($id);
            return redirect()->back()->with('success', __('skill.messages.force_deleted'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
