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
        if (! isset($event->task)) {
            return;
        }

        // القيم بتتحسب دلوقتي (حالة المهمة وقت الحدث)، والإرسال بعد الرد
        $id = (int) $event->task->id;
        $branchId = (int) $event->task->branch_id;
        $action = $event->task->trashed() ? 'deleted' : ($event instanceof TaskCreated ? 'created' : 'updated');

        \App\Support\Realtime::broadcast(fn () => TaskBoardChanged::dispatch($id, $branchId, $action), 'Task board');
    }
}
