<?php

namespace App\Events\Tasks;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * مهمة جديدة اتعملت.
 *
 * بيتبعت بعد ما المعاملة (transaction) تخلص — الإشعارات والبث اللحظي
 * (Pusher) بيسمعوا الحدث ده.
 */
class TaskCreated
{
    use Dispatchable;

    public function __construct(
        public Task $task,
        public ?Model $actor = null,
    ) {}
}
