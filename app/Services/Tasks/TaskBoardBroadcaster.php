<?php

namespace App\Services\Tasks;

use App\Events\TaskBoardChanged;
use App\Events\Tasks\TaskAssigned;
use App\Events\Tasks\TaskAssignmentDeclined;
use App\Events\Tasks\TaskCommented;
use App\Events\Tasks\TaskCreated;
use App\Events\Tasks\TaskProgressUpdated;
use App\Events\Tasks\TaskStatusChanged;
use App\Events\Tasks\TaskUpdated;
use Illuminate\Support\Facades\Log;

/**
 * أي حدث على مهمة ← بث للوحة المهام المباشرة.
 *
 * البث مايوقعش العملية الأصلية لو Pusher وقع، ومابيشتغلش أصلاً لو
 * البث مقفول (null) — زي باقي الإشعارات.
 */
class TaskBoardBroadcaster
{
    public static function events(): array
    {
        return [
            TaskCreated::class, TaskUpdated::class, TaskStatusChanged::class, TaskProgressUpdated::class,
            TaskAssigned::class, TaskAssignmentDeclined::class, TaskCommented::class,
        ];
    }

    public function handle(object $event): void
    {
        if (config('broadcasting.default') === 'null' || ! isset($event->task)) {
            return;
        }

        try {
            TaskBoardChanged::dispatch(
                (int) $event->task->id,
                (int) $event->task->branch_id,
                $event->task->trashed() ? 'deleted' : ($event instanceof TaskCreated ? 'created' : 'updated'),
            );
        } catch (\Throwable $e) {
            Log::warning('Task board broadcast failed: ' . $e->getMessage());
        }
    }
}
