<?php

namespace App\Services\Notification;

use App\Events\EmployeeNotificationCreated;
use App\Models\EmployeeNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * إشعارات بوابة الموظف: حفظ + بث لحظي على قناته الخاصة.
 *
 * نفس قواعد NotificationService بتاع المشرفين: الإشعار مايوقعش العملية
 * الأصلية، وفشل Pusher مابيمنعش الحفظ.
 */
class EmployeeNotificationService
{
    public function send(
        int $employeeId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        ?Model $causer = null,
        array $data = [],
        string $icon = 'fa-solid fa-bell',
        string $level = 'info',
    ): ?EmployeeNotification {
        try {
            $notification = EmployeeNotification::create([
                'employee_id' => $employeeId,
                'type'        => $type,
                'title'       => mb_substr($title, 0, 191),
                'body'        => $body !== null ? mb_substr($body, 0, 1000) : null,
                'url'         => $url,
                'icon'        => $icon,
                'level'       => $level,
                'data'        => $data ?: null,
                'causer_type' => $causer ? $causer::class : null,
                'causer_id'   => $causer?->getKey(),
                'causer_name' => $causer?->name,
            ]);

            $this->broadcast($notification);

            return $notification;
        } catch (\Throwable $e) {
            Log::error('EmployeeNotificationService failed: ' . $e->getMessage(), [
                'employee_id' => $employeeId,
                'type'        => $type,
            ]);

            return null;
        }
    }

    protected function broadcast(EmployeeNotification $notification): void
    {
        // بعد الرد — مايأخرش الطلب (App\Support\Realtime)
        \App\Support\Realtime::broadcast(fn () => EmployeeNotificationCreated::dispatch($notification), 'Employee notification');
    }

    public function unreadCount(int $employeeId): int
    {
        return EmployeeNotification::where('employee_id', $employeeId)->unread()->count();
    }
}
