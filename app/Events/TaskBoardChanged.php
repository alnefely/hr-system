<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * تغيير على مهمة ← لوحة المهام المفتوحة عند المشرفين بتتحدّث لحظياً.
 *
 * بيتبعت على قناتين: قناة فرع المهمة (لمشرفي الفرع) وقناة "all"
 * (للمشرفين اللي بيشوفوا كل الفروع). الرسالة فيها رقم المهمة بس —
 * الشاشة بتطلب الكارت من السيرفر بالفلاتر بتاعتها والصلاحيات بتتفحص هناك.
 */
class TaskBoardChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $taskId,
        public int $branchId,
        public string $action = 'updated',
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tasks-board.' . $this->branchId),
            new PrivateChannel('tasks-board.all'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.changed';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return ['task_id' => $this->taskId, 'action' => $this->action];
    }
}
