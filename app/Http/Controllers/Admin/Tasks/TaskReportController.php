<?php

namespace App\Http\Controllers\Admin\Tasks;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Services\Export\ExcelExportService;
use App\Services\Tasks\TaskMetricsService;
use App\Support\Duration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * تقارير المهام والأداء (لوحة الشركة) — صلاحية task_report.
 *
 * عزل الفروع تلقائي: مشرف الفرع بيشوف أرقام فرعه بس.
 */
class TaskReportController extends Controller
{
    public function __construct(
        private TaskMetricsService $metrics,
    ) {}

    public function index(Request $request)
    {
        $period = TaskMetricsService::period($request->query('period'), $request->query('from'), $request->query('to'));
        $scope = $this->scope($request);

        return view('admin.tasks.reports', [
            'period'    => $period,
            'summary'   => $this->metrics->summary($scope, $period['from'], $period['to']),
            'trend'     => $this->metrics->weeklyTrend($scope),
            'breakdown' => $this->metrics->openBreakdown($scope),
            'employees' => $this->metrics->employees($scope, $period['from'], $period['to']),
            'projects'  => $request->filled('project_id') ? collect() : $this->metrics->projects(Project::query()),
            'overdue'   => $this->metrics->mostOverdue($scope),
            'projectOptions' => Project::orderBy('name')->get(['id', 'name']),
            'projectId' => $request->integer('project_id') ?: null,
        ]);
    }

    /** تصدير جدول أداء الموظفين Excel بنفس الفترة والفلتر. */
    public function export(Request $request)
    {
        $period = TaskMetricsService::period($request->query('period'), $request->query('from'), $request->query('to'));
        $rows = $this->metrics->employees($this->scope($request), $period['from'], $period['to'])
            ->map(fn ($r) => [
                $r['employee']->name,
                $r['employee']->employee_code,
                $r['open'],
                $r['overdue'],
                $r['completed'],
                $r['on_time_rate'] === null ? '-' : $r['on_time_rate'] . '%',
                Duration::format($r['minutes']),
                $r['returned'],
            ])->all();

        return ExcelExportService::download(
            __('task.reports.employees_title'),
            [
                __('task.reports.employee'), __('employee.fields.employee_code'), __('task.reports.open'), __('task.reports.overdue'),
                __('task.reports.completed'), __('task.reports.on_time'), __('task.reports.time_logged'), __('task.reports.returned'),
            ],
            $rows,
            null,
            'tasks-performance-' . $period['from']->format('Ymd') . '-' . $period['to']->format('Ymd'),
            [__('task.reports.period') => $period['from']->format('Y-m-d') . ' → ' . $period['to']->format('Y-m-d')],
        );
    }

    private function scope(Request $request): Builder
    {
        return Task::query()->when($request->integer('project_id'), fn ($q, $id) => $q->where('tasks.project_id', $id));
    }
}
