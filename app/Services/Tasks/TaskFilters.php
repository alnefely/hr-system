<?php

namespace App\Services\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * فلاتر قوائم المهام — نفس الفلاتر في لوحة الإدارة والبوابة ولوحة كانبان.
 *
 * كل فلتر بيتجاهل القيمة الغلط بدل ما يرمي خطأ (رابط قديم أو متلاعب فيه).
 */
class TaskFilters
{
    /** أسماء الفلاتر المقبولة في الرابط. */
    public const KEYS = ['q', 'project_id', 'employee_id', 'status', 'priority', 'label_id', 'due_from', 'due_to', 'scope', 'parent'];

    /** قيم خاصة لفلتر الحالة غير الحالات نفسها. */
    public const STATUS_OPEN = 'open';

    public const STATUS_OVERDUE = 'overdue';

    /**
     * كل الحالات — للشاشات اللي افتراضيها "المفتوحة" (البوابة): القيمة
     * الفاضية بتتشال وترجع للافتراضي، فلازم قيمة صريحة. apply() بتتجاهلها
     * زي أي قيمة مش حالة، فمفيش فلتر على الحالة.
     */
    public const STATUS_ALL = 'all';

    public static function fromRequest(Request $request): array
    {
        return array_filter($request->only(self::KEYS), fn ($v) => $v !== null && $v !== '');
    }

    public function apply(Builder $query, array $filters): Builder
    {
        $filters = array_filter($filters, fn ($v) => $v !== null && $v !== '');

        if (isset($filters['q'])) {
            // من غير escape لـ % و _: SQLite مالهاش escape افتراضي، وأقصى ضرر نتايج زيادة
            $term = '%' . mb_substr((string) $filters['q'], 0, 100) . '%';
            $query->where(fn (Builder $q) => $q->where('tasks.title', 'like', $term)->orWhere('tasks.code', 'like', $term));
        }

        if (isset($filters['project_id'])) {
            $filters['project_id'] === 'none'
                ? $query->whereNull('tasks.project_id')
                : $query->where('tasks.project_id', (int) $filters['project_id']);
        }

        if (isset($filters['employee_id'])) {
            $query->assignedTo((int) $filters['employee_id']);
        }

        $status = $filters['status'] ?? null;

        if ($status === self::STATUS_OPEN) {
            $query->open();
        } elseif ($status === self::STATUS_OVERDUE) {
            $query->overdue();
        } elseif ($status !== null && TaskStatus::tryFrom($status)) {
            $query->where('tasks.status', $status);
        }

        if (isset($filters['priority']) && TaskPriority::tryFrom($filters['priority'])) {
            $query->where('tasks.priority', $filters['priority']);
        }

        if (isset($filters['label_id'])) {
            $query->whereIn('tasks.id', fn ($s) => $s->select('task_id')->from('task_label_task')->where('task_label_id', (int) $filters['label_id']));
        }

        if (isset($filters['due_from']) && strtotime((string) $filters['due_from'])) {
            $query->where('tasks.due_at', '>=', date('Y-m-d 00:00:00', strtotime($filters['due_from'])));
        }

        if (isset($filters['due_to']) && strtotime((string) $filters['due_to'])) {
            $query->where('tasks.due_at', '<=', date('Y-m-d 23:59:59', strtotime($filters['due_to'])));
        }

        // المهام الرئيسية بس افتراضياً — الفرعية بتظهر جوّه المهمة الأم
        if (($filters['parent'] ?? 'root') === 'root') {
            $query->rootOnly();
        }

        return $query;
    }

    /** ترتيب افتراضي: المفتوح الأول، الأعجل، وبعدين الأقرب في التسليم. */
    public function defaultOrder(Builder $query): Builder
    {
        $open = implode("','", TaskStatus::openValues());

        return $query
            ->orderByRaw("CASE WHEN tasks.status IN ('{$open}') THEN 0 ELSE 1 END")
            ->orderByRaw("CASE tasks.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderByRaw('CASE WHEN tasks.due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('tasks.due_at')
            ->orderByDesc('tasks.id');
    }
}
