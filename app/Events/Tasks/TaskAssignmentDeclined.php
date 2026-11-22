<?php

namespace App\Events\Tasks;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * موظف اعتذر عن المهمة.
 *
 * بيتبعت بعد ما المعاملة (transaction) تخلص — الإشعارات والبث اللحظي
 * (Pusher) بيسمعوا الحدث ده.
 */
class TaskAssignmentDeclined
{
    use Dispatchable;

    public function __construct(
        public Task $task,
        public int $employeeId,
        public ?string $reason,
        public ?Model $actor = null,
    ) {}
}
