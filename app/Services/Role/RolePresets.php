<?php

namespace App\Services\Role;

/**
 * أدوار جاهزة حسب التخصص.
 *
 * المصدر الوحيد لتعريفها: الـ seeder بيستخدمه للتنصيب الجديد، والـ SQL
 * بتاع السيرفر الحالي متولّد منه، والاختبارات بتتأكد إن كل صلاحية فيه
 * موجودة فعلاً في RoleService::map().
 *
 * مبدأ التصميم — فصل المهام: الحركة المالية بتعدّي على أكتر من شخص.
 *
 *   مسير الرواتب:  الموارد البشرية بتصدره ← المدير العام بيعتمده ← المحاسب بيصرفه
 *   الجزاء/المكافأة/السلفة: أخصائي الموارد البشرية أو مدير الفرع بيسجّل
 *                            ← مدير الموارد البشرية بيعتمد
 *   العهدة: مسؤول العهدة بيسجّل المصروف ← المحاسب بيحدد المخصص الشهري
 *
 * الحذف النهائي (force_delete) مش في أي دور جاهز — للصلاحية الكاملة بس.
 *
 * العزل بالفرع مش جزء من الدور: أي مشرف مربوط بموظف في فرع بيشوف فرعه
 * بس تلقائياً. فدور "مدير فرع" بيتربط بحساب مربوط بموظف في الفرع ده.
 *
 * الأدوار دي نقطة بداية — بتتعدّل من شاشة الأدوار زي أي دور.
 */
final class RolePresets
{
    /** الأساسيات اللي أي مستخدم محتاجها. */
    private const BASE = [
        'dashboard_show',
        'notification_show',
        'admin_profile_show',
        'admin_profile_update',
    ];

    /**
     * @return array<string, array{name: string, description: string, permissions: array<int, string>}>
     */
    public static function all(): array
    {
        return [
            'general_manager' => [
                'name'        => 'المدير العام',
                'description' => 'متابعة واعتماد كل الأقسام: يعتمد المسيرات والطلبات ويشوف كل التقارير. لا يدير المشرفين والأدوار وإعدادات النظام، ولا يحذف نهائياً.',
                'permissions' => self::generalManager(),
            ],

            'branch_manager' => [
                'name'        => 'مدير فرع',
                'description' => 'إدارة يومية لفرعه: يعتمد الإجازات والأذونات، يسجّل الجزاءات والمكافآت والسلف للمراجعة، ويتابع المصروفات والمبيعات. يُربط حسابه بموظف في الفرع فيرى فرعه فقط.',
                'permissions' => self::branchManager(),
            ],

            'hr_manager' => [
                'name'        => 'مدير الموارد البشرية',
                'description' => 'كامل وحدة الموارد البشرية: الموظفين والرواتب والمستندات، اعتماد الإجازات والجزاءات والمكافآت والسلف، وإصدار المسير (اعتماده للمدير العام وصرفه للمحاسب).',
                'permissions' => self::hrManager(),
            ],

            'hr_officer' => [
                'name'        => 'أخصائي موارد بشرية',
                'description' => 'إدخال بيانات الموظفين وطلباتهم (إجازات، أذونات، جزاءات، مكافآت، سلف) للمراجعة. بدون اعتماد أو حذف أو اطلاع على الرواتب.',
                'permissions' => self::hrOfficer(),
            ],

            'accountant' => [
                'name'        => 'المحاسب',
                'description' => 'المصروفات والعهد (بما فيها المخصص الشهري والتسجيل بأثر رجعي)، الموردين والاشتراكات، صرف المسيرات المعتمدة، سداد السلف، تحصيل الفواتير، والتقارير المالية.',
                'permissions' => self::accountant(),
            ],

            'petty_cash_officer' => [
                'name'        => 'مسؤول العهدة',
                'description' => 'تسجيل مصروفات العهدة النقدية لفرعه ومتابعة رصيدها. بدون تحديد المخصص أو الحذف أو التسجيل بتاريخ قديم.',
                'permissions' => self::pettyCashOfficer(),
            ],

            'sales_officer' => [
                'name'        => 'مسؤول المبيعات',
                'description' => 'العملاء والفواتير والتحصيل، والروابط المختصرة وإحصائياتها. بدون حذف.',
                'permissions' => self::salesOfficer(),
            ],

            'auditor' => [
                'name'        => 'مراجع / مدقق',
                'description' => 'اطلاع كامل للقراءة فقط على كل الأقسام والتقارير وسجل الحركات وتصديره، بدون أي تعديل.',
                'permissions' => self::auditor(),
            ],
        ];
    }

    // ========================================================
    //  تعريف كل دور
    // ========================================================

    private static function generalManager(): array
    {
        $excluded = [
            // إدارة النظام نفسه — للصلاحية الكاملة
            'admin_create', 'admin_update', 'admin_delete',
            'role_create', 'role_update', 'role_delete',
            'setting_update', 'setting_clear_cache',
        ];

        return self::make(array_filter(
            app(RoleService::class)->all(),
            fn (string $p) => ! in_array($p, $excluded, true) && ! str_ends_with($p, '_force_delete')
        ));
    }

    private static function branchManager(): array
    {
        return self::make([
            'filemanager_show',
            'activity_log_show',
            ...self::actions('employee', ['show']),
            ...self::actions('document', ['show']),
            ...self::actions('skill', ['show']),
            ...self::actions('leave', ['show', 'create', 'approve', 'reject']),
            ...self::actions('permission', ['show', 'create', 'approve', 'reject']),
            ...self::actions('penalty', ['show', 'create']),
            ...self::actions('reward', ['show', 'create']),
            ...self::actions('advance', ['show', 'create']),
            ...self::actions('expense', ['show', 'create', 'update']),
            ...self::actions('expense_category', ['show']),
            ...self::actions('customer', ['show']),
            ...self::actions('sale', ['show']),
            'report_employee_statement',
            'report_documents',
        ]);
    }

    private static function hrManager(): array
    {
        $manage = ['show', 'create', 'update', 'delete', 'trash', 'restore'];

        return self::make([
            'filemanager_show',
            'activity_log_show',
            ...self::actions('branch', $manage),
            ...self::actions('job_title', $manage),
            ...self::actions('employee', $manage),
            'employee_salary_view',
            'employee_bank_view',
            'employee_portal_manage',
            ...self::actions('salary', $manage),
            ...self::actions('document', $manage),
            ...self::actions('skill', $manage),
            ...self::actions('leave', ['show', 'create', 'approve', 'reject', 'delete', 'trash', 'restore']),
            'leave_balance_manage',
            ...self::actions('permission', ['show', 'create', 'approve', 'reject', 'delete', 'trash', 'restore']),
            ...self::actions('penalty', ['show', 'create', 'update', 'approve', 'reject', 'delete', 'trash', 'restore']),
            ...self::actions('reward', ['show', 'create', 'update', 'approve', 'reject', 'delete', 'trash', 'restore']),
            // السداد والإعفاء (advance_update) للمحاسب
            ...self::actions('advance', ['show', 'create', 'approve', 'reject', 'delete', 'trash', 'restore']),
            // الاعتماد للمدير العام والصرف للمحاسب
            ...self::actions('payroll', ['show', 'create', 'update', 'delete', 'trash', 'restore']),
            'report_employee_statement',
            'report_salary_audit',
            'report_documents',
        ]);
    }

    private static function hrOfficer(): array
    {
        return self::make([
            'filemanager_show',
            ...self::actions('employee', ['show', 'create', 'update']),
            ...self::actions('document', ['show', 'create', 'update']),
            ...self::actions('skill', ['show', 'create', 'update']),
            ...self::actions('leave', ['show', 'create']),
            ...self::actions('permission', ['show', 'create']),
            ...self::actions('penalty', ['show', 'create', 'update']),
            ...self::actions('reward', ['show', 'create', 'update']),
            ...self::actions('advance', ['show', 'create']),
            'report_employee_statement',
            'report_documents',
        ]);
    }

    private static function accountant(): array
    {
        $manage = ['show', 'create', 'update', 'delete', 'trash', 'restore'];

        return self::make([
            'filemanager_show',
            'activity_log_show',
            ...self::actions('expense', $manage),
            'expense_balance_manage',
            'expense_backdate',
            ...self::actions('expense_category', $manage),
            ...self::actions('vendor', $manage),
            ...self::actions('vendor_subscription', $manage),
            // صرف المسير المعتمد بس — الإصدار للموارد البشرية والاعتماد للمدير العام
            ...self::actions('payroll', ['show', 'pay']),
            // سداد وإعفاء السلف
            ...self::actions('advance', ['show', 'update']),
            ...self::actions('employee', ['show']),
            'employee_salary_view',
            'employee_bank_view',
            ...self::actions('salary', ['show']),
            ...self::actions('customer', ['show']),
            // تسجيل التحصيل على الفواتير
            ...self::actions('sale', ['show', 'update']),
            'report_financial_pl',
            'report_salary_audit',
        ]);
    }

    private static function pettyCashOfficer(): array
    {
        return self::make([
            'filemanager_show',
            ...self::actions('expense', ['show', 'create', 'update']),
            ...self::actions('expense_category', ['show']),
        ]);
    }

    private static function salesOfficer(): array
    {
        return self::make([
            'filemanager_show',
            ...self::actions('customer', ['show', 'create', 'update']),
            ...self::actions('customer_source', ['show']),
            ...self::actions('sales_channel', ['show']),
            ...self::actions('sale', ['show', 'create', 'update']),
            ...self::actions('link', ['show', 'create', 'update']),
            'link_stats',
        ]);
    }

    private static function auditor(): array
    {
        return self::make([
            ...array_filter(app(RoleService::class)->all(), fn (string $p) => str_ends_with($p, '_show')),
            'activity_log_show_all',
            'activity_log_export',
            'employee_salary_view',
            'employee_bank_view',
            'report_employee_statement',
            'report_salary_audit',
            'report_financial_pl',
            'report_documents',
            'link_stats',
        ]);
    }

    // ========================================================
    //  مساعدات
    // ========================================================

    /** ['show', 'create'] للموديول leave → ['leave_show', 'leave_create'] */
    private static function actions(string $module, array $actions): array
    {
        return array_map(fn (string $action) => "{$module}_{$action}", $actions);
    }

    /** الأساسيات + الصلاحيات، من غير تكرار، وبنفس ترتيب RoleService. */
    private static function make(array $permissions): array
    {
        $wanted = array_unique([...self::BASE, ...$permissions]);
        $ordered = app(RoleService::class)->all();

        // الترتيب بتاع الخريطة عشان شاشة الأدوار تعرضها مرتبة، وأي صلاحية
        // مش موجودة في الخريطة بتفضل في الآخر عشان الاختبار يكشفها.
        return array_values(array_merge(
            array_values(array_intersect($ordered, $wanted)),
            array_values(array_diff($wanted, $ordered)),
        ));
    }
}
