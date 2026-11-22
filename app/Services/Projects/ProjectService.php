<?php

namespace App\Services\Projects;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Exceptions\InvalidStateException;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Task;
use App\Services\Tasks\TaskService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * عمليات المشاريع: الإنشاء (فاضي أو من قالب)، التعديل، الأعضاء، الحالة،
 * والحذف — لوحة الإدارة والبوابة بيستخدموا نفس الخدمة.
 *
 * مدير المشروع دايماً عضو بدور "مدير" في جدول الأعضاء، فقوائم "مشاريعي"
 * والصلاحيات بتقرا من مكان واحد.
 */
class ProjectService
{
    public function __construct(
        private ProjectAccess $access,
        private TaskService $tasks,
    ) {}

    /**
     * @param  array{name: string, branch_id: int, description?: ?string, color?: ?string, manager_id?: ?int,
     *               status?: string, priority?: string, start_date?: ?string, due_date?: ?string,
     *               members?: array<int, string>, template_id?: ?int}  $data
     *         members = [employee_id => role]
     */
    public function create(array $data, Model $actor): Project
    {
        $template = ! empty($data['template_id']) ? ProjectTemplate::findOrFail($data['template_id']) : null;

        return DB::transaction(function () use ($data, $actor, $template) {
            $project = Project::create($this->attributes($data) + [
                'branch_id'    => $data['branch_id'],
                'template_id'  => $template?->id,
                'creator_type' => $actor::class,
                'creator_id'   => $actor->getKey(),
            ]);

            $this->writeMembers($project, $data['members'] ?? []);

            if ($template) {
                $this->applyTemplate($project, $template, $actor);
            }

            $project->refreshStats();

            return $project;
        });
    }

    public function update(Project $project, array $data, Model $actor): Project
    {
        $this->authorize($this->access->canUpdate($project, $actor));

        DB::transaction(function () use ($project, $data) {
            $attributes = $this->attributes($data);

            if (isset($attributes['status'])) {
                $attributes += $this->statusSideEffects($project, ProjectStatus::from($attributes['status']));
            }

            $project->update($attributes);

            if (array_key_exists('members', $data)) {
                $this->writeMembers($project, $data['members'] ?? []);
            } elseif (array_key_exists('manager_id', $data)) {
                $this->writeMembers($project, $this->currentMembers($project));
            }
        });

        return $project;
    }

    public function changeStatus(Project $project, ProjectStatus $status, Model $actor): Project
    {
        $this->authorize($this->access->canUpdate($project, $actor));

        $project->update(['status' => $status->value] + $this->statusSideEffects($project, $status));

        return $project;
    }

    /** @param  array<int, string>  $members  [employee_id => role] */
    public function syncMembers(Project $project, array $members, Model $actor): void
    {
        $this->authorize($this->access->canUpdate($project, $actor));

        DB::transaction(fn () => $this->writeMembers($project, $members));
    }

    public function delete(Project $project, Model $actor): void
    {
        $this->authorize($this->access->canDelete($project, $actor));

        $project->delete();
    }

    public function restore(int $id): Project
    {
        $project = Project::onlyTrashed()->findOrFail($id);
        $project->restore();

        return $project;
    }

    // ========================================================
    //  القوالب
    // ========================================================

    /**
     * ينشئ مهام القالب في المشروع. المواعيد = بداية المشروع + الأيام
     * المكتوبة في القالب (start_offset_days / due_offset_days).
     */
    public function applyTemplate(Project $project, ProjectTemplate $template, Model $actor): int
    {
        $base = $project->start_date ? Carbon::parse($project->start_date) : today();
        $count = 0;

        $make = function (array $item, ?Task $parent) use (&$make, &$count, $project, $base, $actor) {
            $title = trim((string) ($item['title'] ?? ''));

            if ($title === '') {
                return;
            }

            $task = $this->tasks->create(array_filter([
                'title'             => $title,
                'description'       => $item['description'] ?? null,
                'project_id'        => $parent ? null : $project->id,
                'parent_id'         => $parent?->id,
                'branch_id'         => $project->branch_id,
                'priority'          => $item['priority'] ?? 'medium',
                'starts_at'         => isset($item['start_offset_days']) ? $base->copy()->addDays((int) $item['start_offset_days'])->setTime(9, 0) : null,
                'due_at'            => isset($item['due_offset_days']) ? $base->copy()->addDays((int) $item['due_offset_days'])->setTime(17, 0) : null,
                'estimated_minutes' => $item['estimated_minutes'] ?? null,
                'requires_review'   => $item['requires_review'] ?? true,
                'checklist'         => $item['checklist'] ?? [],
            ], fn ($v) => $v !== null), $actor);

            $count++;

            foreach ($item['subtasks'] ?? [] as $sub) {
                $make($sub, $task);
            }
        };

        foreach ($template->structure ?? [] as $item) {
            $make($item, null);
        }

        return $count;
    }

    /** يحفظ هيكل مهام المشروع (الرئيسية والفرعية والخطوات) كقالب جديد. */
    public function saveAsTemplate(Project $project, string $name, Model $actor, bool $global = false): ProjectTemplate
    {
        $base = Carbon::parse($project->start_date ?? $project->created_at)->startOfDay();

        $tasks = Task::query()
            ->where('project_id', $project->id)
            ->whereNull('parent_id')
            ->orderBy('position')->orderBy('id')
            ->with(['checklistItems:id,task_id,title,position', 'subtasks.checklistItems:id,task_id,title,position'])
            ->get();

        $toItem = fn (Task $t) => array_filter([
            'title'             => $t->title,
            'description'       => $t->description,
            'priority'          => $t->priority,
            'start_offset_days' => $t->starts_at ? (int) $base->diffInDays($t->starts_at->copy()->startOfDay(), false) : null,
            'due_offset_days'   => $t->due_at ? (int) $base->diffInDays($t->due_at->copy()->startOfDay(), false) : null,
            'estimated_minutes' => $t->estimated_minutes,
            'requires_review'   => $t->requires_review,
            'checklist'         => $t->checklistItems->pluck('title')->all(),
        ], fn ($v) => $v !== null && $v !== []);

        $structure = $tasks->map(fn (Task $t) => $toItem($t) + array_filter([
            'subtasks' => $t->subtasks->map($toItem)->values()->all(),
        ]))->values()->all();

        return ProjectTemplate::create([
            'branch_id'    => $global ? null : $project->branch_id,
            'name'         => $name,
            'description'  => $project->description,
            'structure'    => $structure,
            'is_active'    => true,
            'creator_type' => $actor::class,
            'creator_id'   => $actor->getKey(),
        ]);
    }

    // ========================================================
    //  مساعدات
    // ========================================================

    private function attributes(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'name', 'description', 'color', 'manager_id', 'status', 'priority', 'start_date', 'due_date',
        ]));
    }

    private function statusSideEffects(Project $project, ProjectStatus $status): array
    {
        return match ($status) {
            ProjectStatus::Completed => ['completed_at' => $project->completed_at ?? now()],
            default                  => ['completed_at' => null],
        };
    }

    /** @return array<int, string> */
    private function currentMembers(Project $project): array
    {
        return $project->members()->pluck('project_members.role', 'employees.id')->all();
    }

    /**
     * الأعضاء = القائمة دي بالظبط، ومدير المشروع دايماً عضو بدور مدير.
     * أي موظف مش موجود أو مفصول بيرمي خطأ بدل ما يتجاهل.
     *
     * @param  array<int, string>  $members  [employee_id => role]
     */
    private function writeMembers(Project $project, array $members): void
    {
        $members = collect($members)
            ->mapWithKeys(fn ($role, $id) => [(int) $id => ProjectMemberRole::tryFrom((string) $role)?->value ?? ProjectMemberRole::Member->value])
            ->filter(fn ($role, $id) => $id > 0);

        // المدير القديم اللي اتشال من الإدارة بيفضل عضو عادي
        $members = $members->map(fn ($role, $id) => $role === ProjectMemberRole::Manager->value && $id !== (int) $project->manager_id
            ? ProjectMemberRole::Member->value
            : $role);

        if ($project->manager_id) {
            $members[(int) $project->manager_id] = ProjectMemberRole::Manager->value;
        }

        $valid = Employee::withoutGlobalScopes()->whereNull('deleted_at')->where('status', '!=', 'terminated')
            ->whereIn('id', $members->keys())->pluck('id')->map(fn ($i) => (int) $i)->all();

        if (count($valid) !== $members->count()) {
            throw new InvalidStateException(__('project.errors.invalid_member'));
        }

        $project->members()->sync($members->map(fn ($role) => ['role' => $role])->all());
    }

    private function authorize(bool $allowed): void
    {
        if (! $allowed) {
            throw new InvalidStateException(__('project.errors.forbidden'));
        }
    }
}
