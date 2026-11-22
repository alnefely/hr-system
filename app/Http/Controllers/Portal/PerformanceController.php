<?php

namespace App\Http\Controllers\Portal;

use App\Models\Task;
use App\Services\Tasks\TaskMetricsService;
use Illuminate\Http\Request;

/**
 * أدائي في المهام — وللمدير المباشر: أداء فريقه.
 *
 * الموظف بيشوف أرقام المهام المكلّف بيها هو بس. المدير بيشوف كمان جدول
 * فريقه (الموظفين اللي هو مديرهم المباشر) على مهامهم.
 */
class PerformanceController extends PortalController
{
    public function index(Request $request, TaskMetricsService $metrics)
    {
        $me = $this->employee();
        $period = TaskMetricsService::period($request->query('period'), $request->query('from'), $request->query('to'));
        $mine = Task::query()->assignedTo($me->id);

        $teamIds = $me->subordinates()->pluck('id')->map(fn ($i) => (int) $i)->all();
        $team = null;

        if ($teamIds !== []) {
            $teamScope = Task::query()->whereIn('tasks.id', fn ($q) => $q->select('task_id')->from('task_assignees')
                ->where('status', 'active')->whereIn('employee_id', $teamIds));

            $team = [
                'summary'   => $metrics->summary($teamScope, $period['from'], $period['to']),
                'employees' => $metrics->employees($teamScope, $period['from'], $period['to'], $teamIds),
                'overdue'   => $metrics->mostOverdue($teamScope),
            ];
        }

        return view('portal.tasks.performance', [
            'period'  => $period,
            'summary' => $metrics->summary($mine, $period['from'], $period['to']),
            'trend'   => $metrics->weeklyTrend($mine),
            'breakdown' => $metrics->openBreakdown($mine),
            'team'    => $team,
        ]);
    }
}
