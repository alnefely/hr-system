<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * إجراء محتاج سبب مكتوب (رفض، إلغاء اعتماد...).
 *
 * اسم الحقل بيختلف من شاشة للتانية، فالكلاس الفرعي بيحدده في $field.
 */
abstract class ReasonRequest extends FormRequest
{
    /** اسم حقل السبب في الفورم. */
    protected string $field = 'rejection_reason';

    /** أقل طول للسبب. */
    protected int $min = 1;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            $this->field => ['required', 'string', 'min:' . $this->min, 'max:1000'],
        ];
    }

    public function reason(): string
    {
        return (string) $this->validated($this->field);
    }
}
