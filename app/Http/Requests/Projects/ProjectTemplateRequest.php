<?php

namespace App\Http\Requests\Projects;

use App\Enums\TaskPriority;
use Illuminate\Foundation\Http\FormRequest;

/**
 * القالب بيتبعت صفوف: tasks[i][title|priority|start_offset_days|due_offset_days|checklist|subtasks]
 * — checklist نص (كل خطوة في سطر)، و subtasks JSON مخفي بيحفظ المهام
 * الفرعية اللي جاية من "حفظ كقالب" (الفورم مابيعدّلهاش).
 */
class ProjectTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                      => ['required', 'string', 'max:191'],
            'description'               => ['nullable', 'string', 'max:5000'],
            'is_active'                 => ['boolean'],
            'tasks'                     => ['array', 'max:200'],
            'tasks.*.title'             => ['nullable', 'string', 'max:255'],
            'tasks.*.priority'          => ['nullable', TaskPriority::rule()],
            'tasks.*.start_offset_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'tasks.*.due_offset_days'   => ['nullable', 'integer', 'min:0', 'max:3650'],
            'tasks.*.checklist'         => ['nullable', 'string', 'max:5000'],
            'tasks.*.subtasks'          => ['nullable', 'json', 'max:20000'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => __('project.fields.template_name')];
    }

    /** الصفوف الفاضية بتتشال، والخطوات بتتقسّم سطور. */
    public function structure(): array
    {
        return collect($this->validated('tasks') ?? [])
            ->filter(fn ($row) => trim((string) ($row['title'] ?? '')) !== '')
            ->map(fn ($row) => array_filter([
                'title'             => trim($row['title']),
                'priority'          => $row['priority'] ?? 'medium',
                'start_offset_days' => isset($row['start_offset_days']) ? (int) $row['start_offset_days'] : null,
                'due_offset_days'   => isset($row['due_offset_days']) ? (int) $row['due_offset_days'] : null,
                'checklist'         => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($row['checklist'] ?? ''))))),
                'subtasks'          => ! empty($row['subtasks']) ? (array) json_decode($row['subtasks'], true) : null,
            ], fn ($v) => $v !== null && $v !== []))
            ->values()
            ->all();
    }
}
