<?php

namespace App\Services\Role;

use App\Models\Admin;
use App\Models\Role as RoleModel;
use App\Services\Cache\CacheService;
use App\Services\Cache\Keys\RoleCache;

/**
 * كل حاجة ليها علاقة بصلاحيات المشرفين.
 *
 * المصدر الوحيد لتعريف الصلاحيات — أي صلاحية جديدة بتتضاف في map().
 *
 * في الـ Blade استخدم الاختصارات (admin_can, permissions...)،
 * وفي الـ PHP استخدم الكلاس مباشرة.
 */
class RoleService
{
    /**
     * ذاكرة داخل نفس الـ Request.
     *
     * admin_can() بتتنادى أكتر من ١٢ مرة في الصفحة الواحدة،
     * ومن غير ده كنا هنضرب الكاش في كل مرة.
     */
    protected ?array $roles = null;

    /** الدور اللي $roles متحسوبة له. */
    protected ?int $rolesFor = null;

    public function __construct(
        protected CacheService $cache
    ) {}

    // ========================================================
    //  تعريف الصلاحيات
    // ========================================================

    /**
     * كل مجموعات الصلاحيات.
     *
     * المفتاح  = اسم المجموعة (يستخدم في الـ HTML كـ data-group)
     * label    = مفتاح الترجمة
     * items    = الصلاحيات جوّاها
     */
    public function map(): array
    {
        return [
            'dashboard' => [
                'label' => 'dashboard.title',
                'items' => ['dashboard_show'],
            ],
            'filemanager' => [
                'label' => 'lfm.title',
                // الحذف وإعادة التسمية والنقل صلاحية منفصلة: "عرض" كانت بتسمح
                // لأي مشرف فرع يمسح مستندات الفروع التانية من المجلد المشترك.
                'items' => ['filemanager_show', 'filemanager_delete'],
            ],
            'admin' => [
                'label' => 'admin.title',
                'items' => [
                    'admin_show',
                    'admin_create',
                    'admin_update',
                    'admin_delete',
                ],
            ],
            'profile' => [
                'label' => 'admin.profile',
                'items' => [
                    'admin_profile_show',
                    'admin_profile_update',
                ],
            ],
            'role' => [
                'label' => 'role.title',
                'items' => [
                    'role_show',
                    'role_create',
                    'role_update',
                    'role_delete',
                ],
            ],
            'notification' => [
                'label' => 'notification.title',
                'items' => [
                    'notification_show',
                ],
            ],
            'activity' => [
                'label' => 'activity.title',
                'items' => [
                    'activity_log_show',
                    'activity_log_show_all',
                    'activity_log_export',
                ],
            ],
            'setting' => [
                'label' => 'setting.title',
                'items' => [
                    'setting_show',
                    'setting_update',
                    'setting_clear_cache',
                ],
            ],
            'branch' => [
                'label' => 'branch.title',
                'items' => [
                    'branch_show',
                    'branch_create',
                    'branch_update',
                    'branch_delete',
                    'branch_trash',
                    'branch_restore',
                    'branch_force_delete',
                ],
            ],
            'job_title' => [
                'label' => 'job_title.title',
                'items' => [
                    'job_title_show',
                    'job_title_create',
                    'job_title_update',
                    'job_title_delete',
                    'job_title_trash',
                    'job_title_restore',
                    'job_title_force_delete',
                ],
            ],
            'employee' => [
                'label' => 'employee.title',
                'items' => [
                    'employee_show',
                    'employee_create',
                    'employee_update',
                    'employee_delete',
                    'employee_trash',
                    'employee_restore',
                    'employee_force_delete',
                    'employee_salary_view',
                    'employee_bank_view',
                    // تفعيل/إيقاف دخول الموظف لبوابته وإعادة تعيين كلمة مروره
                    'employee_portal_manage',
                ],
            ],
            'salary' => [
                'label' => 'salary.title',
                'items' => [
                    'salary_show',
                    'salary_create',
                    'salary_update',
                    'salary_delete',
                    'salary_trash',
                    'salary_restore',
                    'salary_force_delete',
                ],
            ],
            'document' => [
                'label' => 'document.title',
                'items' => [
                    'document_show',
                    'document_create',
                    'document_update',
                    'document_delete',
                    'document_trash',
                    'document_restore',
                    'document_force_delete',
                ],
            ],
            'skill' => [
                'label' => 'skill.title',
                'items' => [
                    'skill_show',
                    'skill_create',
                    'skill_update',
                    'skill_delete',
                    'skill_trash',
                    'skill_restore',
                    'skill_force_delete',
                ],
            ],
            'leave' => [
                'label' => 'leave.title',
                'items' => [
                    'leave_show',
                    'leave_create',
                    'leave_approve',
                    'leave_reject',
                    'leave_delete',
                    'leave_trash',
                    'leave_restore',
                    'leave_force_delete',
                    'leave_balance_manage',
                ],
            ],
            'permission' => [
                'label' => 'permission.title',
                'items' => [
                    'permission_show',
                    'permission_create',
                    'permission_approve',
                    'permission_reject',
                    'permission_delete',
                    'permission_trash',
                    'permission_restore',
                    'permission_force_delete',
                ],
            ],
            'penalty' => [
                'label' => 'penalty.title',
                'items' => [
                    'penalty_show',
                    'penalty_create',
                    'penalty_update',
                    'penalty_approve',
                    'penalty_reject',
                    'penalty_delete',
                    'penalty_trash',
                    'penalty_restore',
                    'penalty_force_delete',
                ],
            ],
            'reward' => [
                'label' => 'reward.title',
                'items' => [
                    'reward_show',
                    'reward_create',
                    'reward_update',
                    'reward_approve',
                    'reward_reject',
                    'reward_delete',
                    'reward_trash',
                    'reward_restore',
                    'reward_force_delete',
                ],
            ],
            'advance' => [
                'label' => 'advance.title',
                'items' => [
                    'advance_show',
                    'advance_create',
                    'advance_update',
                    'advance_approve',
                    'advance_reject',
                    'advance_delete',
                    'advance_trash',
                    'advance_restore',
                    'advance_force_delete',
                ],
            ],
            'payroll' => [
                'label' => 'payroll.title',
                'items' => [
                    'payroll_show',
                    'payroll_create',
                    'payroll_update',
                    'payroll_approve',
                    'payroll_reject',
                    'payroll_pay',
                    'payroll_delete',
                    'payroll_trash',
                    'payroll_restore',
                    'payroll_force_delete',
                ],
            ],
            'report' => [
                'label' => 'report.title',
                'items' => [
                    'report_employee_statement',
                    'report_salary_audit',
                    'report_financial_pl',
                    'report_documents',
                ],
            ],
            'expense' => [
                'label' => 'expense.title',
                'items' => [
                    'expense_show',
                    'expense_create',
                    'expense_update',
                    'expense_delete',
                    'expense_trash',
                    'expense_restore',
                    'expense_force_delete',
                    'expense_balance_manage',
                    'expense_backdate',
                ],
            ],
            'expense_category' => [
                'label' => 'expense_category.title',
                'items' => [
                    'expense_category_show',
                    'expense_category_create',
                    'expense_category_update',
                    'expense_category_delete',
                    'expense_category_trash',
                    'expense_category_restore',
                    'expense_category_force_delete',
                ],
            ],
            'vendor' => [
                'label' => 'vendor.title',
                'items' => [
                    'vendor_show',
                    'vendor_create',
                    'vendor_update',
                    'vendor_delete',
                    'vendor_trash',
                    'vendor_restore',
                    'vendor_force_delete',
                ],
            ],
            'vendor_subscription' => [
                'label' => 'vendor_subscription.title',
                'items' => [
                    'vendor_subscription_show',
                    'vendor_subscription_create',
                    'vendor_subscription_update',
                    'vendor_subscription_delete',
                    'vendor_subscription_trash',
                    'vendor_subscription_restore',
                    'vendor_subscription_force_delete',
                ],
            ],
            'customer' => [
                'label' => 'customer.title',
                'items' => [
                    'customer_show',
                    'customer_create',
                    'customer_update',
                    'customer_delete',
                    'customer_trash',
                    'customer_restore',
                    'customer_force_delete',
                ],
            ],
            'customer_source' => [
                'label' => 'customer_source.title',
                'items' => [
                    'customer_source_show',
                    'customer_source_create',
                    'customer_source_update',
                    'customer_source_delete',
                    'customer_source_trash',
                    'customer_source_restore',
                    'customer_source_force_delete',
                ],
            ],
            'sales_channel' => [
                'label' => 'sales_channel.title',
                'items' => [
                    'sales_channel_show',
                    'sales_channel_create',
                    'sales_channel_update',
                    'sales_channel_delete',
                    'sales_channel_trash',
                    'sales_channel_restore',
                    'sales_channel_force_delete',
                ],
            ],
            'sale' => [
                'label' => 'sale.title',
                'items' => [
                    'sale_show',
                    'sale_create',
                    'sale_update',
                    'sale_delete',
                    'sale_trash',
                    'sale_restore',
                    'sale_force_delete',
                ],
            ],
            'link' => [
                'label' => 'link.title',
                'items' => [
                    'link_show',
                    'link_create',
                    'link_update',
                    'link_delete',
                    'link_trash',
                    'link_restore',
                    'link_force_delete',
                    'link_stats',
                ],
            ],
        ];
    }

    /**
     * كل الصلاحيات في ليستة واحدة مسطّحة.
     *
     *   ['dashboard_show', 'admin_show', ...]
     */
    public function all(): array
    {
        return array_values(array_unique(
            array_merge(...array_column($this->map(), 'items'))
        ));
    }

    /**
     * الصلاحيات جاهزة للعرض في شاشة الأدوار.
     *
     *   [
     *     'admin' => [
     *         'label'       => 'المشرفين',
     *         'permissions' => [['value' => 'admin_show', 'label' => 'عرض'], ...],
     *     ],
     *   ]
     */
    public function grouped(): array
    {
        $grouped = [];

        foreach ($this->map() as $key => $group) {
            $grouped[$key] = [
                'label'       => __($group['label']),
                'permissions' => array_map(fn (string $permission) => [
                    'value' => $permission,
                    'label' => $this->label($permission),
                ], $group['items']),
            ];
        }

        return $grouped;
    }

    /**
     * اسم الإجراء المعروض جنب الصلاحية (عرض / إضافة / تعديل / حذف / استرجاع / سلة / إلخ).
     */
    protected function label(string $permission): string
    {
        // التطابق الكامل له الأولوية: البحث بالتضمين تحت بيخلط بين
        // صلاحيات زي activity_log_show و activity_log_show_all.
        $exact = "role.action.{$permission}";
        if (__($exact) !== $exact) {
            return __($exact);
        }

        foreach (['force_delete', 'restore', 'trash', 'salary_view', 'bank_view', 'balance_manage', 'statement', 'backdate', 'approve', 'reject', 'pay', 'update', 'delete', 'create', 'show'] as $action) {
            if (str_contains($permission, $action)) {
                return __("role.action.{$action}");
            }
        }

        return __("role.action.{$permission}");
    }

    // ========================================================
    //  المشرف الحالي
    // ========================================================

    /**
     * المشرف الحالي.
     */
    public function admin(): ?Admin
    {
        return auth('admin')->user();
    }

    /**
     * صلاحيات المشرف الحالي.
     *
     * متكاشة لكل دور على حدة (مش كل الأدوار في مفتاح واحد).
     */
    public function roles(): array
    {
        $roleId = $this->admin()?->role_id;

        // الذاكرة مربوطة بالدور: الكلاس singleton، ولو المستخدم اتغيّر جوّه
        // نفس العملية (اختبارات، queue، octane) مانرجّعش صلاحيات اللي قبله.
        if ($this->roles !== null && $this->rolesFor === $roleId) {
            return $this->roles;
        }

        $this->rolesFor = $roleId;

        if ($roleId === null) {
            return $this->roles = [];
        }

        return $this->roles = $this->cache->remember(
            RoleCache::class,
            RoleCache::role($roleId),
            fn () => RoleModel::find($roleId)?->roles ?? []
        );
    }

    /**
     * اسم الدور — متكاش مع باقي مفاتيح الأدوار (بيتلغي مع RoleCache).
     * بيتقرا في النافبار في كل صفحة، فمش عايزين lazy load للعلاقة.
     */
    public function roleName(int|string|null $roleId): string
    {
        if ($roleId === null || $roleId === '') {
            return '';
        }

        return (string) $this->cache->remember(
            RoleCache::class,
            RoleCache::roleName($roleId),
            fn () => RoleModel::whereKey($roleId)->value('name') ?? ''
        );
    }

    /**
     * هل المشرف الحالي يملك الصلاحية دي؟
     */
    public function can(string $permission): bool
    {
        return in_array($permission, $this->roles(), true);
    }
}
