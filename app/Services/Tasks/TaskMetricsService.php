<?php

namespace App\Services\Tasks;

use App\Enums\TaskAssigneeStatus;
use App\Enums\TaskReviewStatus;
use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * مقاييس الأداء للتقارير ولوحات المتابعة.
 *
 * كل الدوال بتاخد $scope = استعلام المهام اللي المستخدم يشوفها (عزل الفرع
 * أو مهام الموظف) فنفس الحسابات بتشتغل للشركة وللفريق وللموظف.
 *
 * التعريفات (مكتوبة في الشاشة كمان):
 *  - في الميعاد: اكتملت قبل أو وقت موعد التسليم (من المهام اللي ليها موعد).
 *  - مدة الإنجاز: من بداية الشغل (أو الإنشاء) لحد الإكمال.
 *  - دقة التقدير: الوقت الفعلي ÷ المتوقع للمهام المكتملة اللي ليها تقدير.
 *  - إعادة العمل: نسبة المراجعات اللي رجعت للتعديل من كل القرارات.
 *
 * الحسابات اللي بتختلف بين MySQL و SQLite (فروق التواريخ) بتتعمل في PHP
 * على أعمدة قليلة، والباقي تجميع في قاعدة البيانات.
 */
class TaskMetricsService
{
    private const MAX_COMPLETED_ROWS = 20000;

    /** الفترة من الرابط (preset أو from/to) — الافتراضي الشهر الحالي. */
    public static function period(?string $preset, ?string $from = null, ?string $to = null): array
    {
        $today = today();

        [$start, $end] = match ($preset) {
            'last_month'   => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            'last_30'      => [$today->copy()->subDays(29), $today->copy()],
            'this_quarter' => [$today->copy()->firstOfQuarter(), $today->copy()],
            'this_year'    => [$today->copy()->startOfYear(), $today->copy()],
            'custom'       => [self::date($from) ?? $today->copy()->startOfMonth(), self::date($to) ?? $today->copy()],
            default        => [$today->copy()->startOfMonth(), $today->copy()],
        };

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        return [
            'preset' => in_array($preset, ['last_month', 'last_30', 'this_quarter', 'this_year', 'custom'], true) ? $preset : 'this_month',
            'from'   => $start->startOfDay(),
            'to'     => $end->endOfDay(),
        ];
    }

    private static function date(?string $value): ?Carbon
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) ? Carbon::parse($value) : null;
    }

    // ========================================================
    //  المؤشرات الرئيسية
    // ========================================================

    public function summary(Builder $scope, Carbon $from, Carbon $to): array
    {
        $completed = $this->completedIn($scope, $from, $to);
        $withDue = $completed->filter(fn ($t) => $t->due_at !== null);
        $onTime = $withDue->filter(fn ($t) => $t->completed_at->lte($t->due_at));
        $cycles = $completed->map(fn ($t) => ($t->started_at ?? $t->created_at)->diffInMinutes($t->completed_at, true));
        // دقة التقدير: المهام اللي ليها تقدير واتسجّل عليها وقت فعلاً
        $estimated = $completed->filter(fn ($t) => $t->estimated_minutes > 0 && $t->spent_minutes > 0);

        $open = TaskStatus::openValues();
        $now = (clone $scope)->toBase()
            ->selectRaw('SUM(CASE WHEN status IN (?, ?, ?) THEN 1 ELSE 0 END) as open', $open)
            ->selectRaw('SUM(CASE WHEN status IN (?, ?, ?) AND due_at IS NOT NULL AND due_at < ? THEN 1 ELSE 0 END) as overdue', [...$open, now()])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_review', [TaskStatus::InReview->value])
            ->first();

        $reviews = DB::table('task_reviews')
            ->whereIn('task_id', $this->ids($scope))
            ->whereBetween('decided_at', [$from, $to])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as returned', [TaskReviewStatus::Rejected->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved', [TaskReviewStatus::Approved->value])
            ->first();
        $decided = (int) $reviews->returned + (int) $reviews->approved;

        return [
            'created'          => (clone $scope)->whereBetween('tasks.created_at', [$from, $to])->count(),
            'completed'        => $completed->count(),
            'open'             => (int) ($now->open ?? 0),
            'overdue'          => (int) ($now->overdue ?? 0),
            'in_review'        => (int) ($now->in_review ?? 0),
            'on_time_rate'     => $withDue->isEmpty() ? null : (int) round($onTime->count() / $withDue->count() * 100),
            'on_time_base'     => $withDue->count(),
            'avg_cycle_minutes'=> $cycles->isEmpty() ? null : (int) round($cycles->avg()),
            'estimate_ratio'   => $estimated->sum('estimated_minutes') > 0
                ? (int) round($estimated->sum('spent_minutes') / $estimated->sum('estimated_minutes') * 100) : null,
            'rework_rate'      => $decided === 0 ? null : (int) round((int) $reviews->returned / $decided * 100),
            'time_logged'      => (int) DB::table('task_time_entries')
                ->whereIn('task_id', $this->ids($scope))
                ->whereNotNull('ended_at')
                ->whereBetween('started_at', [$from, $to])
                ->sum('minutes'),
        ];
    }

    /** المهام المكتملة في الفترة — أعمدة قليلة للحسابات في PHP. */
    private function completedIn(Builder $scope, Carbon $from, Carbon $to): Collection
    {
        return (clone $scope)
            ->where('tasks.status', TaskStatus::Completed->value)
            ->whereBetween('tasks.completed_at', [$from, $to])
            ->limit(self::MAX_COMPLETED_ROWS)
            ->get(['tasks.id', 'tasks.created_at', 'tasks.started_at', 'tasks.completed_at', 'tasks.due_at', 'tasks.estimated_minutes', 'tasks.spent_minutes']);
    }

    /**
     * المكتمل أسبوعياً لآخر $weeks أسابيع (للرسم البياني).
     *
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function weeklyTrend(Builder $scope, int $weeks = 8): array
    {
        $start = now()->startOfWeek(Carbon::SATURDAY)->subWeeks($weeks - 1)->startOfDay();

        $dates = (clone $scope)
            ->where('tasks.status', TaskStatus::Completed->value)
            ->where('tasks.completed_at', '>=', $start)
            ->limit(self::MAX_COMPLETED_ROWS)
            ->pluck('tasks.completed_at');

        $buckets = array_fill(0, $weeks, 0);
        foreach ($dates as $date) {
            $index = intdiv((int) $start->diffInDays(Carbon::parse($date)), 7);
            if ($index >= 0 && $index < $weeks) {
                $buckets[$index]++;
            }
        }

        return [
            'labels' => collect(range(0, $weeks - 1))->map(fn ($i) => $start->copy()->addWeeks($i)->format('m-d'))->all(),
            'values' => $buckets,
        ];
    }

    /** المهام المفتوحة حسب الحالة والأولوية. */
    public function openBreakdown(Builder $scope): array
    {
        $rows = (clone $scope)->toBase()
            ->whereIn('status', TaskStatus::openValues())
            ->selectRaw('status, priority, COUNT(*) as total')
            ->groupBy('status', 'priority')
            ->get();

        return [
            'status'   => $rows->groupBy('status')->map->sum('total')->all(),
            'priority' => $rows->groupBy('priority')->map->sum('total')->all(),
        ];
    }

    // ========================================================
    //  أداء الموظفين
    // ========================================================

    /**
     * جدول الموظفين: المفتوح، المتأخر، المكتمل في الفترة، نسبة الالتزام،
     * الوقت المسجّل، والمرتجع من المراجعة.
     *
     * ٥ استعلامات تجميعية مهما كان عدد الموظفين.
     *
     * @param  array<int, int>|null  $employeeIds  تحديد الموظفين (فريق المدير مثلاً)
     */
    public function employees(Builder $scope, Carbon $from, Carbon $to, ?array $employeeIds = null): Collection
    {
        $ids = $this->ids($scope);
        $open = TaskStatus::openValues();

        $assignments = fn () => DB::table('task_assignees as ta')
            ->join('tasks', 'tasks.id', '=', 'ta.task_id')
            ->whereIn('ta.task_id', $ids)
            ->where('ta.status', TaskAssigneeStatus::Active->value)
            ->whereNull('tasks.deleted_at')
            ->when($employeeIds !== null, fn ($q) => $q->whereIn('ta.employee_id', $employeeIds));

        $current = $assignments()
            ->selectRaw('ta.employee_id')
            ->selectRaw('SUM(CASE WHEN tasks.status IN (?, ?, ?) THEN 1 ELSE 0 END) as open', $open)
            ->selectRaw('SUM(CASE WHEN tasks.status IN (?, ?, ?) AND tasks.due_at IS NOT NULL AND tasks.due_at < ? THEN 1 ELSE 0 END) as overdue', [...$open, now()])
            ->groupBy('ta.employee_id')
            ->get()->keyBy('employee_id');

        $done = $assignments()
            ->where('tasks.status', TaskStatus::Completed->value)
            ->whereBetween('tasks.completed_at', [$from, $to])
            ->get(['ta.employee_id', 'tasks.completed_at', 'tasks.due_at'])
            ->groupBy('employee_id');

        $time = DB::table('task_time_entries')
            ->whereIn('task_id', $ids)
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$from, $to])
            ->when($employeeIds !== null, fn ($q) => $q->whereIn('employee_id', $employeeIds))
            ->selectRaw('employee_id, SUM(minutes) as minutes')
            ->groupBy('employee_id')
            ->pluck('minutes', 'employee_id');

        $returned = DB::table('task_reviews')
            ->whereIn('task_id', $ids)
            ->where('status', TaskReviewStatus::Rejected->value)
            ->where('submitter_type', Employee::class)
            ->whereBetween('decided_at', [$from, $to])
            ->selectRaw('submitter_id, COUNT(*) as total')
            ->groupBy('submitter_id')
            ->pluck('total', 'submitter_id');

        $people = $employeeIds ?? $current->keys()->merge($done->keys())->merge($time->keys())->unique()->all();

        $names = Employee::withoutGlobalScopes()->whereIn('id', $people)->get(['id', 'name', 'employee_code'])->keyBy('id');

        return collect($people)
            ->map(function ($id) use ($names, $current, $done, $time, $returned) {
                $completed = $done->get($id, collect());
                $withDue = $completed->filter(fn ($r) => $r->due_at !== null);
                $onTime = $withDue->filter(fn ($r) => Carbon::parse($r->completed_at)->lte(Carbon::parse($r->due_at)));

                return [
                    'employee'     => $names->get($id),
                    'open'         => (int) ($current[$id]->open ?? 0),
                    'overdue'      => (int) ($current[$id]->overdue ?? 0),
                    'completed'    => $completed->count(),
                    'on_time_rate' => $withDue->isEmpty() ? null : (int) round($onTime->count() / $withDue->count() * 100),
                    'minutes'      => (int) ($time[$id] ?? 0),
                    'returned'     => (int) ($returned[$id] ?? 0),
                ];
            })
            ->filter(fn ($row) => $row['employee'] !== null)
            ->sortBy([['overdue', 'desc'], ['open', 'desc']])
            ->values();
    }

    // ========================================================
    //  المشاريع والمتأخر
    // ========================================================

    /** المشاريع الجارية بنسبتها والمتأخر فيها. */
    public function projects(Builder $projectScope): Collection
    {
        $projects = (clone $projectScope)
            ->whereIn('status', ['planning', 'active', 'on_hold'])
            ->with('manager:id,name')
            ->orderBy('due_date')
            ->limit(50)
            ->get(['id', 'code', 'name', 'status', 'progress', 'tasks_count', 'completed_tasks_count', 'due_date', 'manager_id', 'completed_at']);

        $overdue = Task::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereIn('project_id', $projects->pluck('id'))
            ->overdue()
            ->selectRaw('project_id, COUNT(*) as total')
            ->groupBy('project_id')
            ->pluck('total', 'project_id');

        return $projects->each(fn (Project $p) => $p->setAttribute('overdue_count', (int) ($overdue[$p->id] ?? 0)));
    }

    /** أكتر المهام تأخيراً. */
    public function mostOverdue(Builder $scope, int $limit = 10): Collection
    {
        return (clone $scope)->overdue()
            ->with(['assignees:id,name,avatar', 'project:id,name'])
            ->orderBy('tasks.due_at')
            ->limit($limit)
            ->get(['tasks.id', 'tasks.code', 'tasks.title', 'tasks.due_at', 'tasks.status', 'tasks.priority', 'tasks.progress', 'tasks.project_id', 'tasks.starts_at', 'tasks.created_at']);
    }

    /** أرقام المهام في النطاق كاستعلام فرعي (مش قائمة في الذاكرة). */
    private function ids(Builder $scope)
    {
        return (clone $scope)->select('tasks.id');
    }
}
