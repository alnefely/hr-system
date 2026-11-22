<?php

namespace App\Events\Tasks;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * نسبة إنجاز المهمة اتحدّثت.
 *
 * بيتبعت بعد ما المعاملة (transaction) تخلص — الإشعارات والبث اللحظي
 * (Pusher) بيسمعوا الحدث ده.
 */
class TaskProgressUpdated
{
    use Dispatchable;

    public function __construct(
        public Task $task,
        public int $from,
        public int $to,
        public ?Model $actor = null,
    ) {}
}
