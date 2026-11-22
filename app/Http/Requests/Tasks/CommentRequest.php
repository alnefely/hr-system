<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;

/** تعليق (مع ملفات اختيارية وإشارات). */
class CommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['mentions' => array_values(array_filter((array) $this->input('mentions', [])))]);
    }

    public function rules(): array
    {
        return [
            'body'       => ['nullable', 'string', 'max:5000', 'required_without:files'],
            'parent_id'  => ['nullable', 'integer'],
            'mentions'   => ['array', 'max:50'],
            'mentions.*' => ['integer'],
        ] + AttachmentRequest::fileRules(false);
    }

    public function attributes(): array
    {
        return ['body' => __('task.comments.title'), 'files.*' => __('task.attachments.title')];
    }
}
