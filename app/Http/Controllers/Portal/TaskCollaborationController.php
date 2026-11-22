<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Concerns\HandlesTaskCollaboration;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;

/** التعليقات والمرفقات من البوابة — للمهام اللي الموظف يحق له يشوفها بس. */
class TaskCollaborationController extends PortalController
{
    use HandlesTaskCollaboration;

    protected function actor(): Model
    {
        return $this->employee();
    }

    protected function resolveTask(int $id): Task
    {
        return Task::visibleToEmployee($this->employee())->whereKey($id)->firstOrFail();
    }
}
