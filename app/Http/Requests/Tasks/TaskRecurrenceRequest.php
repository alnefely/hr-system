<?php

namespace App\Http\Requests\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskRecurrenceFrequency;
use Illuminate\Foundation\Http\FormRequest;

class TaskRecurrenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'requires_review' => $this->boolean('requires_review'),
            'is_active'       => $this->has('is_active') ? $this->boolean('is_active') : true,
            'assignee_ids'    => array_values(array_filter((array) $this->input('assignee_ids', []))),
            'weekdays'        => array_values(array_filter((array) $this->input('weekdays', []), fn ($v) => $v !== null && $v !== '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'title'             => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string', 'max:20000'],
            'priority'          => ['required', TaskPriority::rule()],
            'branch_id'         => ['nullable', 'integer', 'exists:branches,id'],
            'project_id'        => ['nullable', 'integer'],
            'assignee_ids'      => ['array', 'max:50'],
            'assignee_ids.*'    => ['integer'],
            'checklist'         => ['nullable', 'array', 'max:50'],
            'checklist.*'       => ['nullable', 'string', 'max:500'],
            'requires_review'   => ['boolean'],
            'estimated_hours'   => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'frequency'         => ['required', TaskRecurrenceFrequency::rule()],
            'interval'          => ['required', 'integer', 'min:1', 'max:52'],
            'weekdays'          => ['array', 'required_if:frequency,weekly'],
            'weekdays.*'        => ['integer', 'between:0,6'],
            'month_day'         => ['nullable', 'required_if:frequency,monthly', 'integer', 'between:1,31'],
            'run_time'          => ['required', 'date_format:H:i'],
            'duration_days'     => ['required', 'integer', 'min:0', 'max:365'],
            'starts_on'         => ['required', 'date'],
            'ends_on'           => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_active'         => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title'     => __('task.fields.title'),
            'weekdays'  => __('task.recurrence.weekdays'),
            'month_day' => __('task.recurrence.month_day'),
            'run_time'  => __('task.recurrence.run_time'),
            'starts_on' => __('task.recurrence.starts_on'),
            'ends_on'   => __('task.recurrence.ends_on'),
        ];
    }

    public function forService(): array
    {
        $data = $this->validated();
        $data['estimated_minutes'] = isset($data['estimated_hours']) ? (int) round($data['estimated_hours'] * 60) : null;
        unset($data['estimated_hours']);

        return $data;
    }
}
