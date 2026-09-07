<?php

namespace App\Services\HR;

use App\Models\EmployeeSkill;
use Illuminate\Database\Eloquent\Builder;

class EmployeeSkillService
{
    /**
     * استعلام سلة محذوفات المهارات
     */
    public function trashQuery(int $employeeId): Builder
    {
        return EmployeeSkill::onlyTrashed()->where('employee_id', $employeeId);
    }

    /**
     * عدد المهارات في سلة المحذوفات
     */
    public function trashCount(int $employeeId): int
    {
        return EmployeeSkill::onlyTrashed()->where('employee_id', $employeeId)->count();
    }

    /**
     * إضافة مهارة جديدة
     */
    public function create(array $data): EmployeeSkill
    {
        $data['is_core'] = isset($data['is_core']) ? (bool) $data['is_core'] : false;

        return EmployeeSkill::create($data);
    }

    /**
     * تعديل بيانات المهارة
     */
    public function update(EmployeeSkill $skill, array $data): EmployeeSkill
    {
        $data['is_core'] = isset($data['is_core']) ? (bool) $data['is_core'] : false;

        $skill->update($data);

        return $skill;
    }

    /**
     * نقل المهارة لسلة المحذوفات (Soft Delete)
     */
    public function delete(EmployeeSkill $skill): bool
    {
        return (bool) $skill->delete();
    }

    /**
     * استرجاع المهارة من السلة
     */
    public function restore(int $id): bool
    {
        $skill = EmployeeSkill::onlyTrashed()->findOrFail($id);

        return (bool) $skill->restore();
    }

    /**
     * الحذف النهائي للمهارة
     */
    public function forceDelete(int $id): bool
    {
        $skill = EmployeeSkill::onlyTrashed()->findOrFail($id);

        return (bool) $skill->forceDelete();
    }
}
