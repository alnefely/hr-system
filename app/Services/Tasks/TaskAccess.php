<?php

namespace App\Services\Tasks;

use App\Enums\TaskAssigneeStatus;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;

/**
 * مين يقدر يعمل إيه على المهمة — المصدر الوحيد للقواعد.
 *
 * لوحة الإدارة والبوابة بيسألوا نفس الكلاس، فمفيش قاعدة بتتكتب مرتين.
 *
 * المشرف: بالصلاحيات (task_update، task_assign...) + عزل الفرع اللي
 *          بيتطبّق تلقائياً على الاستعلام.
 * الموظف: بدوره في المهمة:
 *   - اللي أنشأها / مدير المشروع  ← تعديل، تكليف، حذف، اعتماد
 *   - المكلّف                     ← تغيير الحالة، الخطوات، النسبة، اعتذار
 *   - المدير المباشر للمكلّف      ← متابعة واعتماد (مراجع)
 *
 * الدوال اللي بتسأل عن المكلّفين بتستخدم assignments لو محمّلة
 * (من غير استعلام) — الشاشات بتحمّلها مرة واحدة مع المهمة.
 */
class TaskAccess
{
    // ========================================================
    //  أدوار الموظف في المهمة
    // ========================================================

    public function isCreator(Task $task, Model $actor): bool
    {
        return $task->creator_type === $actor::class && (int) $task->creator_id === (int) $actor->getKey();
    }

    public function isActiveAssignee(Task $task, Employee $employee): bool
    {
        if ($task->relationLoaded('assignments')) {
            return $task->assignments->contains(fn ($a) => (int) $a->employee_id === (int) $employee->id
                && $a->status === TaskAssigneeStatus::Active->value);
        }

        return $task->assignments()
            ->where('employee_id', $employee->id)
            ->where('status', TaskAssigneeStatus::Active->value)
            ->exists();
    }

    public function isProjectManager(Task $task, Employee $employee): bool
    {
        if (! $task->project_id) {
            return false;
        }

        $managerId = $task->relationLoaded('project')
            ? $task->project?->manager_id
            : Project::withoutGlobalScopes()->whereKey($task->project_id)->value('manager_id');

        return $managerId !== null && (int) $managerId === (int) $employee->id;
    }

    /** الموظف مدير مباشر لحد من المكلّفين. */
    public function managesAnAssignee(Task $task, Employee $employee): bool
    {
        if ($task->relationLoaded('assignments') && $task->assignments->every(fn ($a) => $a->relationLoaded('employee'))) {
            return $task->assignments->contains(fn ($a) => $a->status === TaskAssigneeStatus::Active->value
                && (int) $a->employee?->manager_id === (int) $employee->id);
        }

        return $task->assignments()
            ->where('status', TaskAssigneeStatus::Active->value)
            ->whereIn('employee_id', fn ($q) => $q->select('id')->from('employees')->where('manager_id', $employee->id))
            ->exists();
    }

    /** صاحب القرار في المهمة (أنشأها أو مدير مشروعها). */
    protected function owns(Task $task, Employee $employee): bool
    {
        return $this->isCreator($task, $employee) || $this->isProjectManager($task, $employee);
    }

    // ========================================================
    //  الصلاحيات
    // ========================================================

    public function canView(Task $task, Model $actor): bool
    {
        if ($actor instanceof Admin) {
            return admin_can('task_show');
        }

        return $actor instanceof Employee
            && Task::visibleToEmployee($actor)->whereKey($task->id)->exists();
    }

    /** تعديل البيانات (العنوان، الوصف، المواعيد، الأولوية، طريقة حساب النسبة). */
    public function canUpdate(Task $task, Model $actor): bool
    {
        if ($actor instanceof Admin) {
            return admin_can('task_update');
        }

        return $actor instanceof Employee && $this->owns($task, $actor);
    }

    public function canAssign(Task $task, Model $actor): bool
    {
        if ($actor instanceof Admin) {
            return admin_can('task_assign');
        }

        return $actor instanceof Employee && $this->owns($task, $actor);
    }

    public function canChangeStatus(Task $task, Model $actor): bool
    {
        if ($actor instanceof Admin) {
            return admin_can('task_update');
        }

        return $actor instanceof Employee && (
            $this->owns($task, $actor)
            || $this->isActiveAssignee($task, $actor)
            || $this->managesAnAssignee($task, $actor)
        );
    }

    /**
     * يقدر يقفل المهمة كمكتملة من غير ما تعدّي على المراجعة؟
     * (اللي أنشأها، مدير المشروع، المدير المباشر للمكلّف، أو مشرف بصلاحية).
     */
    public function canApprove(Task $task, Model $actor): bool
    {
        if ($actor instanceof Admin) {
            return admin_can('task_review') || admin_can('task_update');
        }

        return $actor instanceof Employee && (
            $this->owns($task, $actor) || $this->managesAnAssignee($task, $actor)
        );
    }

    /** الخطوات، المهام الفرعية، وتحديث نسبة الإنجاز. */
    public function canWork(Task $task, Model $actor): bool
    {
        if ($actor instanceof Admin) {
            return admin_can('task_update');
        }

        return $actor instanceof Employee && (
            $this->owns($task, $actor) || $this->isActiveAssignee($task, $actor)
        );
    }

    public function canDelete(Task $task, Model $actor): bool
    {
        if ($actor instanceof Admin) {
            return admin_can('task_delete');
        }

        return $actor instanceof Employee && $this->isCreator($task, $actor);
    }

    /** الاعتذار للمكلّف بس — واللي أنشأ المهمة مايعتذرش عن مهمته. */
    public function canDecline(Task $task, Model $actor): bool
    {
        return $actor instanceof Employee
            && $this->isActiveAssignee($task, $actor)
            && ! $this->isCreator($task, $actor);
    }

    /** كل الصلاحيات مرة واحدة — للشاشات (أزرار تظهر وتختفي). */
    public function abilities(Task $task, Model $actor): array
    {
        return [
            'update'  => $this->canUpdate($task, $actor),
            'assign'  => $this->canAssign($task, $actor),
            'status'  => $this->canChangeStatus($task, $actor),
            'approve' => $this->canApprove($task, $actor),
            'work'    => $this->canWork($task, $actor),
            'delete'  => $this->canDelete($task, $actor),
            'decline' => $this->canDecline($task, $actor),
        ];
    }

    // ========================================================
    //  مين ينفع يتكلّف
    // ========================================================

    /**
     * الموظفين اللي ينفع يتكلّفوا بمهمة في الفرع/المشروع ده:
     * موظفين نفس الفرع النشطين + أعضاء المشروع ومديره (حتى لو من فرع تاني).
     *
     * استعلام واحد (whereIn subqueries)، ومن غير Global Scope عشان
     * أعضاء المشروع من الفروع التانية يظهروا لمشرف الفرع.
     */
    public function assignableEmployeesQuery(int $branchId, ?int $projectId = null)
    {
        return Employee::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('status', '!=', 'terminated')
            ->where(function ($q) use ($branchId, $projectId) {
                $q->where('branch_id', $branchId);

                if ($projectId) {
                    $q->orWhereIn('id', fn ($s) => $s->select('employee_id')->from('project_members')->where('project_id', $projectId))
                        ->orWhereIn('id', fn ($s) => $s->select('manager_id')->from('projects')->where('id', $projectId)->whereNotNull('manager_id'));
                }
            });
    }
}
