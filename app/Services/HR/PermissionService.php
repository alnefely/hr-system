<?php

namespace App\Services\HR;

use App\Enums\ApprovalStatus;
use App\Enums\NotificationType;
use App\Models\EmployeePermission;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;

/**
 * طلبات الاستئذان — مشتركة بين لوحة الإدارة وبوابة الموظف.
 *
 * الاعتماد والرفض في ApprovalService.
 */
class PermissionService
{
    public function __construct(
        private NotificationService $notifications,
    ) {}

    /**
     * عدد الساعات من وقت الخروج والعودة — مش من إدخال المستخدم: كان ممكن
     * يسجّل إذن من 9 لـ 10 ويكتب 8 ساعات، والمسير يخصم على أساسها.
     */
    public static function hoursBetween(string $start, string $end): float
    {
        return round(Carbon::createFromFormat('H:i', $start)->floatDiffInHours(Carbon::createFromFormat('H:i', $end)), 2);
    }

    /**
     * @param  array{employee_id: int, permission_date: string, start_time: string, end_time: string, reason?: ?string}  $data
     */
    public function create(array $data): EmployeePermission
    {
        $permission = EmployeePermission::create([
            'status'      => ApprovalStatus::Pending->value,
            'hours_count' => self::hoursBetween($data['start_time'], $data['end_time']),
        ] + $data);

        $this->notifyRequested($permission);

        return $permission;
    }

    /** إشعار المسؤولين بطلب استئذان جديد محتاج مراجعة. */
    private function notifyRequested(EmployeePermission $permission): void
    {
        $employee = $permission->employee;

        $this->notifications->notifyEligible(
            type: NotificationType::PermissionRequested,
            title: __('notification.messages.permission_requested.title'),
            body: __('notification.messages.permission_requested.body', [
                'employee' => $employee?->name ?? '-',
                'date'     => $permission->permission_date?->format('Y-m-d'),
                'from'     => $permission->start_time,
                'to'       => $permission->end_time,
            ]),
            url: route('admin.permissions.index', ['employee_id' => $permission->employee_id]),
            branchId: $employee?->branch_id,
            data: ['permission_id' => $permission->id],
            excludeAdminId: admin()?->id,
        );
    }
}
