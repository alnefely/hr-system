<?php

namespace App\Events\Tasks;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * بيانات المهمة اتعدّلت (عنوان، مواعيد، أولوية...).
 *
 * بيتبعت بعد ما المعاملة (transaction) تخلص — الإشعارات والبث اللحظي
 * (Pusher) بيسمعوا الحدث ده.
 */
class TaskUpdated
{
    use Dispatchable;

    public function __construct(
        public Task $task,
        public ?Model $actor = null,
    ) {}
}
