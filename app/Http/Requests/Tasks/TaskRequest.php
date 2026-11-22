<?php

namespace App\Http\Requests\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskProgressInterval;
use App\Enums\TaskProgressSource;
use Illuminate\Foundation\Http\FormRequest;

/**
 * بيانات المهمة (إنشاء وتعديل) — نفس القواعد في لوحة الإدارة والبوابة.
 *
 * الصلاحية الفعلية (مين يعدّل، مين يتكلّف، أي مشروع) في TaskService/TaskAccess؛
 * هنا شكل البيانات بس.
 */
class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // checkbox مش متعلّم = مش مبعوت خالص
        $this->merge([
            'requires_review' => $this->boolean('requires_review'),
            'assignee_ids'    => array_values(array_filter((array) $this->input('assignee_ids', []))),
            'label_ids'       => array_values(array_filter((array) $this->input('label_ids', []))),
        ]);
    }

    public function rules(): array
    {
        return [
            'title'                      => ['required', 'string', 'max:255'],
            'description'                => ['nullable', 'string', 'max:20000'],
            'branch_id'                  => ['nullable', 'integer', 'exists:branches,id'],
            'project_id'                 => ['nullable', 'integer'],
            'parent_id'                  => ['nullable', 'integer'],
            'priority'                   => ['required', TaskPriority::rule()],
            'starts_at'                  => ['nullable', 'date'],
            'due_at'                     => ['nullable', 'date', 'after_or_equal:starts_at'],
            'estimated_hours'            => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'requires_review'            => ['boolean'],
            'progress_source'            => ['required', TaskProgressSource::rule()],
            'progress_update_interval'   => ['required', TaskProgressInterval::rule()],
            'progress_update_every_days' => ['nullable', 'required_if:progress_update_interval,custom', 'integer', 'min:1', 'max:365'],
            'assignee_ids'               => ['array', 'max:50'],
            'assignee_ids.*'             => ['integer'],
            'label_ids'                  => ['array', 'max:20'],
            'label_ids.*'                => ['integer', 'exists:task_labels,id'],
            'checklist'                  => ['nullable', 'array', 'max:50'],
            'checklist.*'                => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title'                      => __('task.fields.title'),
            'description'                => __('task.fields.description'),
            'priority'                   => __('task.fields.priority'),
            'starts_at'                  => __('task.fields.starts_at'),
            'due_at'                     => __('task.fields.due_at'),
            'estimated_hours'            => __('task.fields.estimated_hours'),
            'progress_update_every_days' => __('task.fields.progress_update_every_days'),
        ];
    }

    /** البيانات بالشكل اللي TaskService مستنيه (الساعات ← دقايق). */
    public function forService(): array
    {
        $data = $this->validated();

        if (array_key_exists('estimated_hours', $data)) {
            $data['estimated_minutes'] = $data['estimated_hours'] !== null ? (int) round($data['estimated_hours'] * 60) : null;
            unset($data['estimated_hours']);
        }

        return $data;
    }
}
