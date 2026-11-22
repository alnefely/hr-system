<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;

/** رفع مرفقات على مهمة — الحجم والأنواع من config/tasks.php. */
class AttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public static function fileRules(bool $required = true): array
    {
        $config = config('tasks.attachments');

        return [
            'files'   => [$required ? 'required' : 'nullable', 'array', 'max:' . $config['max_files']],
            'files.*' => ['file', 'max:' . $config['max_kb'], 'mimes:' . implode(',', $config['mimes'])],
        ];
    }

    public function rules(): array
    {
        return self::fileRules();
    }

    public function attributes(): array
    {
        return ['files' => __('task.attachments.title'), 'files.*' => __('task.attachments.title')];
    }
}
