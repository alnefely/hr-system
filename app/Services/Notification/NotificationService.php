<?php

namespace App\Services\Notification;

use App\Enums\NotificationType;
use App\Events\AdminNotificationCreated;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Services\Role\RoleService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * إنشاء وبث الإشعارات.
 *
 * المسؤول الوحيد عن الإشعارات، فكل القواعد في مكان واحد:
 *  - مين يستقبل إيه (حسب الصلاحية والفرع).
 *  - عدم إشعار الشخص بفعل نفسه.
 *  - البث مايوقعش العملية الأصلية لو Pusher وقع.
 */
class NotificationService
{
    public function __construct(
        protected RoleService $roles,
    ) {}

    /**
     * المشرف اللي عمل الحدث — عشان مايوصلوش إشعار بحاجة عملها بنفسه.
     *
     * الطلب الجاي من بوابة الموظف صاحبه الموظف، حتى لو نفس المتصفح داخل
     * لوحة الإدارة كمان (الحارسين في نفس الجلسة). من غير الفحص ده المشرف
     * كان بيتستبعد من إشعار طلب الموظف كأنه هو اللي قدّمه.
     */
    public static function actingAdminId(): ?int
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;   // الأوامر المجدولة مالهاش مشرف
        }

        if (request()?->routeIs('portal.*')) {
            return null;
        }

        return auth('admin')->id();
    }

    /**
     * إشعار كل المشرفين المؤهّلين لنوع معيّن.
     *
     * المؤهّل = عنده صلاحية النوع ده، وتابع لنفس الفرع (أو مدير عام).
     *
     * @return int عدد الإشعارات اللي اتبعتت
     */
    public function notifyEligible(
        NotificationType $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        ?int $branchId = null,
        array $data = [],
        ?int $excludeAdminId = null,
    ): int {
        $recipients = $this->eligibleRecipients($type, $branchId, $excludeAdminId);

        foreach ($recipients as $admin) {
            $this->send($admin->id, $type, $title, $body, $url, $branchId, $data);
        }

        return $recipients->count();
    }

    /** إشعار مشرف واحد بعينه. */
    public function send(
        int $adminId,
        NotificationType $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        ?int $branchId = null,
        array $data = [],
    ): ?AdminNotification {
        try {
            // صاحب الحدث: المشرف من لوحة الإدارة بس (طلب البوابة صاحبه الموظف)
            $causer = self::actingAdminId() ? auth('admin')->user() : null;

            $notification = AdminNotification::create([
                'admin_id'    => $adminId,
                'branch_id'   => $branchId,
                'type'        => $type->value,
                'title'       => $title,
                'body'        => $body,
                'url'         => $url,
                'icon'        => $type->icon(),
                'level'       => $type->level(),
                'data'        => $data ?: null,
                'causer_id'   => $causer?->id,
                'causer_name' => $causer?->name,
            ]);

            $this->broadcast($notification);

            return $notification;
        } catch (\Throwable $e) {
            // الإشعار مايوقعش العملية الأصلية — طلب الإجازة يتسجّل حتى لو
            // الإشعار فشل.
            Log::error('NotificationService failed: ' . $e->getMessage(), [
                'admin_id' => $adminId,
                'type'     => $type->value,
            ]);

            return null;
        }
    }

    /**
     * المشرفون المؤهّلون لاستقبال نوع معيّن.
     *
     * @return Collection<int, Admin>
     */
    public function eligibleRecipients(NotificationType $type, ?int $branchId = null, ?int $excludeAdminId = null): Collection
    {
        // ذاكرة داخل نفس العملية: الأمر المجدول بيبعت نفس النوع لفروع كتير
        // عشرات المرات (فاتورة متأخرة لكل فاتورة). بنحمّل المشرفين اللي عندهم
        // الصلاحية مرة واحدة لكل نوع (من غير الفرع في المفتاح)، والفلترة على
        // الفرع والمستبعد بتتم في PHP.
        $withPermission = $this->recipientsMemo[$type->value] ??= $this->loadAdminsWithPermission($type);

        return $withPermission
            ->filter(function (Admin $admin) use ($branchId, $excludeAdminId) {
                if ($excludeAdminId !== null && $admin->id === $excludeAdminId) {
                    return false;
                }

                // المدير العام بيستقبل كل حاجة
                if ($admin->main) {
                    return true;
                }

                // مشرف الفرع بيستقبل إشعارات فرعه بس
                if ($branchId === null) {
                    return true;
                }

                // المشرف اللي مش مربوط بموظف مش مقيّد بفرع (نفس قاعدة
                // BranchScopeResolver) — بيشوف كل الفروع فيستقبل إشعاراتها.
                $adminBranch = $this->adminBranchId($admin);

                return $adminBranch === null || $adminBranch === $branchId;
            })
            ->values();
    }

    /** @var array<string, Collection<int, Admin>> */
    protected array $recipientsMemo = [];

    /** @return Collection<int, Admin> */
    protected function loadAdminsWithPermission(NotificationType $type): Collection
    {
        $permission = $type->requiredPermission();

        // فرع كل مشرف بيتجاب مع المشرفين في استعلام واحد بدل استعلام لكل
        // مشرف. من غير Global Scopes: لو اللي عامل الحدث مشرف فرع، عزل
        // الفروع كان هيخفي موظفين الفروع التانية ويطلّع فروع المستلمين null.
        return Admin::with([
                'role',
                'employee' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'branch_id'),
            ])
            ->get()
            ->filter(fn (Admin $admin) => $this->hasPermission($admin, $permission))
            ->values();
    }

    /** هل المشرف عنده الصلاحية دي؟ (من غير ما نعتمد على المستخدم الحالي) */
    protected function hasPermission(Admin $admin, string $permission): bool
    {
        $permissions = $admin->role?->roles ?? [];

        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        return in_array($permission, $permissions, true);
    }

    /** فرع المشرف عبر الموظف المرتبط بيه. */
    protected function adminBranchId(Admin $admin): ?int
    {
        if (!$admin->employee_id) {
            return null;
        }

        // العلاقة متحمّلة مسبقاً في loadEligibleRecipients
        $branchId = $admin->employee?->branch_id;

        return $branchId ? (int) $branchId : null;
    }

    /** البث اللحظي — فشله مايأثرش على حفظ الإشعار. */
    protected function broadcast(AdminNotification $notification): void
    {
        // بعد الرد — مايأخرش الطلب (App\Support\Realtime)
        \App\Support\Realtime::broadcast(fn () => AdminNotificationCreated::dispatch($notification), 'Notification');
    }

    /** عدّاد غير المقروء لمشرف. */
    public function unreadCount(int $adminId): int
    {
        return AdminNotification::forAdmin($adminId)->unread()->count();
    }

    /**
     * عدّاد غير المقروء متذكّر طول الـ Request.
     *
     * النافبار والرئيسية الاتنين محتاجينه في نفس الصفحة — كانوا بيعملوا
     * COUNT مرتين. بنخزّنه في attributes بتاعة الـ Request نفسه، فالطلب
     * الجاي بيحسبه من جديد (مفيش بيانات قديمة بين الطلبات).
     */
    public static function unreadCountForRequest(?int $adminId): int
    {
        if (!$adminId) {
            return 0;
        }

        $attributes = request()->attributes;
        $key = 'notifications.unread.' . $adminId;

        if (!$attributes->has($key)) {
            $attributes->set($key, AdminNotification::forAdmin($adminId)->unread()->count());
        }

        return (int) $attributes->get($key);
    }
}
