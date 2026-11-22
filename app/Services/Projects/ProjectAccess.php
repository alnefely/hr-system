<?php

namespace App\Services\Projects;

use App\Enums\ProjectMemberRole;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

/**
 * مين يقدر يعمل إيه على المشروع.
 *
 * المشرف: بالصلاحيات (project_update...) + عزل الفرع التلقائي.
 * الموظف: بدوره في المشروع:
 *   - مدير المشروع      ← تعديل البيانات والحالة والأعضاء
 *   - عضو              ← يشوف المشروع ويضيف مهام فيه
 *   - مشاهد            ← يشوف بس
 *
 * إنشاء المشاريع من لوحة الإدارة بس — الموظف بيدير المشروع اللي اتعيّن مديره.
 *
 * الدوال بتستخدم members لو محمّلة (من غير استعلام).
 */
class ProjectAccess
{
    public function roleOf(Project $project, Employee $employee): ?ProjectMemberRole
    {
        if ((int) $project->manager_id === (int) $employee->id) {
            return ProjectMemberRole::Manager;
        }

        $role = $project->relationLoaded('members')
            ? $project->members->firstWhere('id', $employee->id)?->pivot?->role
            : $project->members()->where('employees.id', $employee->id)->value('project_members.role');

        return ProjectMemberRole::tryFrom((string) $role);
    }

    public function canView(Project $project, Model $actor): bool
    {
        if ($actor instanceof Admin) {
            return admin_can('project_show');
        }

        return $actor instanceof Employee && $this->roleOf($project, $actor) !== null;
    }

    public function canUpdate(Project $project, Model $actor): bool
    {
        if ($actor instanceof Admin) {
            return admin_can('project_update');
        }

        return $actor instanceof Employee && $this->roleOf($project, $actor) === ProjectMemberRole::Manager;
    }

    /** إضافة مهام في المشروع (المدير والأعضاء — مش المشاهدين). */
    public function canAddTasks(Project $project, Model $actor): bool
    {
        if ($actor instanceof Admin) {
            return admin_can('task_create');
        }

        return $actor instanceof Employee
            && in_array($this->roleOf($project, $actor), [ProjectMemberRole::Manager, ProjectMemberRole::Member], true);
    }

    public function canDelete(Project $project, Model $actor): bool
    {
        return $actor instanceof Admin && admin_can('project_delete');
    }

    public function abilities(Project $project, Model $actor): array
    {
        return [
            'update'    => $this->canUpdate($project, $actor),
            'add_tasks' => $this->canAddTasks($project, $actor),
            'delete'    => $this->canDelete($project, $actor),
            'template'  => $actor instanceof Admin && admin_can('project_template_manage'),
        ];
    }
}
