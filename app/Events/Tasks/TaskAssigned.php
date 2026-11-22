<?php

namespace App\Events\Tasks;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * موظفين اتكلّفوا بالمهمة.
 *
 * بيتبعت بعد ما المعاملة (transaction) تخلص — الإشعارات والبث اللحظي
 * (Pusher) بيسمعوا الحدث ده.
 */
class TaskAssigned
{
    use Dispatchable;

    public function __construct(
        public Task $task,
        /** @var array<int, int> */
        public array $employeeIds,
        public ?Model $actor = null,
    ) {}
}
