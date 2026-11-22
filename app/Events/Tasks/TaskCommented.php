<?php

namespace App\Events\Tasks;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * تعليق جديد على مهمة — بيتبعت بعد ما التعليق يتحفظ.
 *
 * $mentionIds = الموظفين المشار إليهم (@) — بيوصلهم إشعار مخصوص.
 */
class TaskCommented
{
    use Dispatchable;

    public function __construct(
        public Task $task,
        public TaskComment $comment,
        /** @var array<int, int> */
        public array $mentionIds = [],
        public ?Model $actor = null,
    ) {}
}
