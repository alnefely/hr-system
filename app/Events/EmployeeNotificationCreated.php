<?php

namespace App\Events;

use App\Models\EmployeeNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * بث إشعار جديد لموظف بعينه في البوابة.
 *
 * القناة خاصة بالموظف (employee.{id}) — نفس فكرة admin.{id}.
 * ShouldBroadcastNow لنفس سبب إشعارات المشرفين: مفيش worker شغال طول
 * الوقت، والفشل بيتمسك في EmployeeNotificationService.
 */
class EmployeeNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public EmployeeNotification $notification,
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('employee.' . $this->notification->employee_id)];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'notification' => $this->notification->toPayload(),
            'unread_count' => EmployeeNotification::where('employee_id', $this->notification->employee_id)->unread()->count(),
        ];
    }
}
