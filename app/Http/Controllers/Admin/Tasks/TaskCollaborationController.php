<?php

namespace App\Http\Controllers\Admin\Tasks;

use App\Http\Controllers\Concerns\HandlesTaskCollaboration;
use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;

/** التعليقات والمرفقات من لوحة الإدارة — عزل الفروع بيتطبّق على المهمة تلقائياً. */
class TaskCollaborationController extends Controller
{
    use HandlesTaskCollaboration;

    protected function actor(): Model
    {
        return admin();
    }

    protected function resolveTask(int $id): Task
    {
        return Task::findOrFail($id);
    }
}
