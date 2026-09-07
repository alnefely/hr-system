<?php

namespace App\Support;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;

/**
 * بيحدد فرع المشرف الحالي لأغراض عزل البيانات.
 *
 * منفصل عن الـ trait عشان الحالة (الكاش) تبقى مشتركة بين كل الموديلات
 * في نفس الـ request — لو كانت static جوّه trait، كل موديل كان هياخد
 * نسخته الخاصة، وكمان PHP 8.3 بتعتبر ده deprecated.
 */
class BranchScopeResolver
{
    /** false = لسه ماتحسبش · null = مفيش عزل · int = رقم الفرع */
    protected static int|null|false $branchId = false;

    /**
     * فرع المشرف الحالي، أو null لو المفروض يشوف كل الفروع.
     *
     * بيرجع null لما: مفيش مشرف مسجّل دخول (CLI، seeders)، أو المشرف
     * رئيسي (main)، أو مش مربوط بموظف له فرع.
     *
     * ملاحظة: الفرع بيتجاب باستعلام خام على جدول employees مش عبر العلاقة
     * $admin->employee — لأن العلاقة بتستعلم موديل Employee اللي عليه نفس
     * الـ Global Scope، فتدخل في recursion لا نهائي.
     */
    public static function branchId(): ?int
    {
        if (static::$branchId !== false) {
            return static::$branchId;
        }

        // بنخزّن null الأول عشان أي استعلام جوّه الدالة مايرجّعش ينادينا.
        static::$branchId = null;

        if (!auth('admin')->check()) {
            return null;
        }

        /** @var Admin|null $admin */
        $admin = auth('admin')->user();

        if (!$admin || $admin->main || !$admin->employee_id) {
            return null;
        }

        $branchId = DB::table('employees')
            ->where('id', $admin->employee_id)
            ->value('branch_id');

        return static::$branchId = $branchId ? (int) $branchId : null;
    }

    /** تصفير الكاش — للاختبارات وتبديل المستخدم داخل نفس العملية. */
    public static function forget(): void
    {
        static::$branchId = false;
    }
}
