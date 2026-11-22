<?php

namespace App\Http\Controllers\Portal;

use App\Models\TaskTimeEntry;
use App\Services\Tasks\TaskTimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * سجل وقتي: الوقت اللي الموظف سجّله في أسبوع (حسب اليوم والمهمة)،
 * وإيقاف العدّاد الشغّال من الشريط العلوي في أي صفحة.
 */
class TimesheetController extends PortalController
{
    public function index(Request $request, TaskTimeService $time)
    {
        $week = $this->week($request->query('week'));
        $end = $week->copy()->addDays(6)->endOfDay();

        $entries = TaskTimeEntry::query()
            ->where('employee_id', $this->employee()->id)
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$week, $end])
            ->with('task:id,code,title')
            ->orderBy('started_at')
            ->get();

        $days = collect(range(0, 6))->map(fn ($i) => $week->copy()->addDays($i));

        return view('portal.time.index', [
            'week'     => $week,
            'days'     => $days,
            'byDay'    => $entries->groupBy(fn ($e) => $e->started_at->toDateString())->map->sum('minutes'),
            'byTask'   => $entries->groupBy('task_id')->map(fn ($items) => ['task' => $items->first()->task, 'minutes' => $items->sum('minutes')])->sortByDesc('minutes'),
            'entries'  => $entries->sortByDesc('started_at'),
            'total'    => $entries->sum('minutes'),
            'running'  => $time->running($this->employee())?->load('task:id,title'),
        ]);
    }

    public function stop(Request $request, TaskTimeService $time)
    {
        $time->stop($this->employee(), $request->validate(['note' => ['nullable', 'string', 'max:500']])['note'] ?? null);

        return back()->with('success', __('task.messages.timer_stopped'));
    }

    /** بداية الأسبوع (السبت) من ?week=YYYY-MM-DD أو الأسبوع الحالي. */
    private function week(?string $value): Carbon
    {
        $date = is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) ? Carbon::parse($value) : now();

        return $date->startOfWeek(Carbon::SATURDAY)->startOfDay();
    }
}
