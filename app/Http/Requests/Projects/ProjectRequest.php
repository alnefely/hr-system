<?php

namespace App\Http\Requests\Projects;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use Illuminate\Foundation\Http\FormRequest;

/**
 * بيانات المشروع — لوحة الإدارة والبوابة (مدير المشروع).
 *
 * الأعضاء بيتبعتوا قائمتين: members[] (عضو) و viewers[] (مشاهد)، وبيتحوّلوا
 * لـ [employee_id => role] في forService(). المدير بيتضاف تلقائياً في الخدمة.
 */
class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'members' => array_values(array_filter((array) $this->input('members', []))),
            'viewers' => array_values(array_filter((array) $this->input('viewers', []))),
        ]);
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:20000'],
            'color'       => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'branch_id'   => ['nullable', 'integer', 'exists:branches,id'],
            'manager_id'  => ['nullable', 'integer', 'exists:employees,id'],
            'status'      => ['required', ProjectStatus::rule()],
            'priority'    => ['required', TaskPriority::rule()],
            'start_date'  => ['nullable', 'date'],
            'due_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'members'     => ['array', 'max:200'],
            'members.*'   => ['integer'],
            'viewers'     => ['array', 'max:200'],
            'viewers.*'   => ['integer'],
            'template_id' => ['nullable', 'integer', 'exists:project_templates,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'       => __('project.fields.name'),
            'start_date' => __('project.fields.start_date'),
            'due_date'   => __('project.fields.due_date'),
            'color'      => __('project.fields.color'),
        ];
    }

    public function forService(): array
    {
        $data = $this->validated();

        $members = [];
        foreach ($data['viewers'] ?? [] as $id) {
            $members[(int) $id] = ProjectMemberRole::Viewer->value;
        }
        foreach ($data['members'] ?? [] as $id) {
            $members[(int) $id] = ProjectMemberRole::Member->value;
        }

        unset($data['viewers']);
        $data['members'] = $members;

        return $data;
    }
}
