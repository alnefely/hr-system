<?php

namespace App\Events;

use App\Models\AdminNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * بث إشعار جديد لمشرف بعينه.
 *
 * القناة خاصة بالمشرف (admin.{id}) مش بالفرع — الفرع بيتحدد وقت إنشاء
 * الإشعار في NotificationService، فوقت البث بنبعت للمستلم مباشرة.
 * ده أأمن: مفيش احتمال إن مشرف يشترك في قناة فرع ويشوف إشعارات غيره.
 */
class AdminNotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AdminNotification $notification,
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.' . $this->notification->admin_id)];
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
            'unread_count' => AdminNotification::forAdmin($this->notification->admin_id)->unread()->count(),
        ];
    }
}
