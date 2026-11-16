<?php

namespace App\Rules;

use App\Models\Employee;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * الموظف موجود وتابع لفرع المشرف الحالي.
 *
 * بديل exists:employees,id — دي بتستعلم بالـ query builder مباشرة
 * فبتتجاهل الـ Global Scope، ومشرف الفرع كان يقدر يسجّل إجازة أو سلفة
 * أو جزاء لموظف في فرع تاني.
 *
 * الاستعلام هنا عبر الموديل، فالعزل بيتطبّق تلقائياً. withTrashed عشان
 * السلوك يفضل زي exists القديمة (اللي كانت بتقبل المحذوف ناعماً).
 */
class AccessibleEmployee implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || ! Employee::withTrashed()->whereKey((int) $value)->exists()) {
            $fail('validation.exists')->translate();
        }
    }
}
