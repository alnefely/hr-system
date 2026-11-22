<?php

namespace App\Services\Tasks;

use App\Enums\TaskStatus;
use App\Exceptions\InvalidStateException;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * لوحة كانبان والتقويم — لوحة الإدارة والبوابة.
 *
 * الأداء: استعلامين بس للوحة كلها (المفتوحة + المكتملة مؤخراً) مع
 * eager loading، والعدادات على الكروت مخزّنة على المهمة نفسها.
 */
class TaskBoardService
{
    /** أقصى عدد كروت في العمود الواحد — الباقي من شاشة القائمة. */
    public const COLUMN_LIMIT = 60;

    /** المكتملة بتظهر لو خلصت في آخر كام يوم. */
    public const COMPLETED_DAYS = 14;

    public const CARD_RELATIONS = ['project:id,name', 'assignees:id,name,avatar', 'labels:id,name,color'];

    public function __construct(
        private TaskService $tasks,
    ) {}

    /** الأعمدة بالترتيب: جديدة، قيد التنفيذ، مراجعة، مكتملة (الملغية مش على اللوحة). */
    public static function columns(): array
    {
        return [TaskStatus::New, TaskStatus::InProgress, TaskStatus::InReview, TaskStatus::Completed];
    }

    /**
     * @return array<string, array{status: TaskStatus, tasks: Collection, total: int}>
     */
    /**
     * كل عمود ليه حد أقصى لوحده، والعدد اللي فوقه هو العدد الحقيقي.
     *
     * قبل كده كان فيه حد واحد (180) للأعمدة المفتوحة كلها مرتّب بالـ position،
     * فعمود مزحوم كان بياكل الحد ويبان التاني فاضي، والعدد كان للمحمّل بس.
     * دلوقتي: استعلام كروت لكل عمود (4) + استعلام عدد مجمّع واحد.
     */
    public function board(Builder $query): array
    {
        // من غير type: بيتنادى على Eloquent Builder (الكروت) وعلى Query Builder (العدد)
        $recentlyCompleted = fn ($q) => $q->where('tasks.completed_at', '>=', now()->subDays(self::COMPLETED_DAYS));

        // العدد الحقيقي لكل عمود في استعلام واحد
        $open = TaskStatus::openValues();
        // select و reorder صريحين: الاستعلام الجاي ممكن يكون عليه select('tasks.*') أو ترتيب
        $counts = (clone $query)->toBase()
            ->where(fn ($q) => $q->whereIn('tasks.status', $open)
                ->orWhere(fn ($c) => $recentlyCompleted($c->where('tasks.status', TaskStatus::Completed->value))))
            ->reorder()
            ->select('tasks.status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('tasks.status')
            ->pluck('total', 'status');

        $board = [];

        foreach (self::columns() as $status) {
            $cards = (clone $query)->where('tasks.status', $status->value)->with(self::CARD_RELATIONS)->limit(self::COLUMN_LIMIT);

            $cards = $status === TaskStatus::Completed
                ? $recentlyCompleted($cards)->latest('tasks.completed_at')
                : $cards->orderBy('tasks.position')
                    ->orderByRaw("CASE tasks.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
                    ->orderByRaw('CASE WHEN tasks.due_at IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('tasks.due_at');

            $board[$status->value] = [
                'status' => $status,
                'tasks'  => $cards->get(),
                'total'  => (int) ($counts[$status->value] ?? 0),
            ];
        }

        return $board;
    }

    /**
     * نقل كارت: تغيير الحالة (بنفس قواعد الخدمة) + ترتيب العمود.
     *
     * @param  array<int, int>  $order  أرقام المهام في العمود الجديد بالترتيب (من الشاشة)
     * @param  Builder  $visible  المهام اللي المستخدم يشوفها — الترتيب مايلمسش غيرها
     */
    public function move(Task $task, TaskStatus $to, Model $actor, array $order, Builder $visible, ?string $note = null): Task
    {
        if ($to->value !== $task->status) {
            if ($to === TaskStatus::Cancelled) {
                throw new InvalidStateException(__('task.errors.forbidden'));
            }

            $this->tasks->changeStatus($task, $to, $actor, $note);
            $task->refresh();
        }

        $this->reorder($order, $to, $visible);

        return $task;
    }

    /** ترتيب العمود باستعلام واحد (CASE) للمهام الظاهرة اللي في نفس الحالة بس. */
    private function reorder(array $order, TaskStatus $status, Builder $visible): void
    {
        $ids = array_values(array_unique(array_map('intval', array_slice($order, 0, self::COLUMN_LIMIT))));

        if ($ids === []) {
            return;
        }

        $allowed = (clone $visible)->whereIn('tasks.id', $ids)->where('tasks.status', $status->value)
            ->pluck('tasks.id')->map(fn ($id) => (int) $id)->all();
        $ids = array_values(array_intersect($ids, $allowed));

        if ($ids === []) {
            return;
        }

        $cases = implode(' ', array_map(fn ($id, $pos) => 'WHEN ' . (int) $id . ' THEN ' . (int) $pos, $ids, array_keys($ids)));

        DB::table('tasks')->whereIn('id', $ids)->update(['position' => DB::raw("CASE id {$cases} END")]);
    }

    // ========================================================
    //  التقويم
    // ========================================================

    /** الشهر المطلوب من الرابط (?month=2026-11) أو الشهر الحالي. */
    public static function month(?string $value): Carbon
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}$/', $value)) {
            [$y, $m] = array_map('intval', explode('-', $value));

            if ($m >= 1 && $m <= 12 && $y >= 2000 && $y <= 2100) {
                return Carbon::create($y, $m, 1)->startOfDay();
            }
        }

        return now()->startOfMonth()->startOfDay();
    }

    /**
     * أيام شبكة الشهر (من السبت للجمعة) ومهام كل يوم حسب موعد التسليم.
     *
     * @return array{weeks: array<int, array<int, Carbon>>, tasks: Collection<string, Collection>, month: Carbon}
     */
    public function calendar(Builder $query, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth()->startOfWeek(Carbon::SATURDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::FRIDAY);

        $tasks = (clone $query)
            ->whereBetween('tasks.due_at', [$start, $end->copy()->endOfDay()])
            ->where('tasks.status', '!=', TaskStatus::Cancelled->value)
            ->with(['assignees:id,name,avatar'])
            ->orderBy('tasks.due_at')
            ->limit(1000)
            ->get(['tasks.id', 'tasks.code', 'tasks.title', 'tasks.status', 'tasks.priority', 'tasks.due_at', 'tasks.progress', 'tasks.starts_at', 'tasks.created_at'])
            ->groupBy(fn (Task $t) => $t->due_at->toDateString());

        $weeks = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $weeks[intdiv((int) $start->diffInDays($day), 7)][] = $day->copy();
        }

        return ['weeks' => $weeks, 'tasks' => $tasks, 'month' => $month];
    }
}
