<?php

namespace App\Events\Tasks;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * حالة المهمة اتغيّرت.
 *
 * بيتبعت بعد ما المعاملة (transaction) تخلص — الإشعارات والبث اللحظي
 * (Pusher) بيسمعوا الحدث ده.
 */
class TaskStatusChanged
{
    use Dispatchable;

    public function __construct(
        public Task $task,
        public string $from,
        public string $to,
        public ?Model $actor = null,
    ) {}
}
