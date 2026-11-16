<?php

namespace App\Rules;

use App\Support\BranchScopeResolver;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;

/**
 * الفرع المُرسَل لازم يكون فرع المشرف الحالي.
 *
 * الـ Global Scope في ScopedToBranch بيحمي القراءة بس. قاعدة
 * exists:branches,id بتعدّي أي فرع موجود، فمشرف الفرع كان يقدر يسجّل
 * فاتورة أو مصروف أو موظف على فرع تاني بمجرد تغيير القيمة في الفورم.
 *
 * المشرف الرئيسي (أو أي مشرف مش مربوط بفرع) مابيتقيّدش.
 *
 * الاستخدام: ['required', 'exists:branches,id', new AccessibleBranch]
 */
class AccessibleBranch implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $scoped = BranchScopeResolver::branchId();

        if ($scoped === null || $value === null || $value === '') {
            return;
        }

        if ((int) $value !== $scoped) {
            $fail('validation.branch_not_accessible')->translate();
        }
    }

    /**
     * للحقول الاختيارية اللي null فيها معناها "كل الفروع":
     * مشرف الفرع لازم يحدد فرعه — سجل عام مش من حقه يعمله، ولو
     * عمله مش هيقدر يشوفه بعد كده.
     */
    public static function requiredForScopedAdmin(): RequiredIf
    {
        return Rule::requiredIf(fn () => BranchScopeResolver::branchId() !== null);
    }
}
