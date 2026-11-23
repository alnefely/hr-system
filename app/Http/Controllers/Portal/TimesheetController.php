<?php

namespace App\Http\Controllers\Portal;

use App\Models\TaskTimeEntry;
use App\Services\Tasks\TaskTimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * سجل وقتي: الوقت اللي الموظف سجّله في فترة (حسب اليوم والمهمة)،
 * وإيقاف العدّاد الشغّال من الشريط العلوي في أي صفحة.
 *
 * الفترة: ?from=&to= (الافتراضي الأسبوع الحالي سبت ← جمعة).
 * ?week= القديم لسه شغال (أسبوع اليوم ده).
 */
class TimesheetController extends PortalController
{
    /** أقصى طول للفترة — أكتر من كده الصفحة بتتقل من غير فايدة. */
    private const MAX_DAYS = 366;

    /** لحد الطول ده الأعمدة يوم بيوم، وبعده شهر بشهر. */
    private const DAILY_UP_TO = 31;

    public function index(Request $request, TaskTimeService $time)
    {
        [$from, $to] = $this->timesheetPeriod($request);
        $length = (int) $from->diffInDays($to) + 1;

        $entries = TaskTimeEntry::query()
            ->where('employee_id', $this->employee()->id)
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$from, $to->copy()->endOfDay()])
            ->with('task:id,code,title')
            ->orderBy('started_at')
            ->get();

        return view('portal.time.index', [
            'from'     => $from,
            'to'       => $to,
            'length'   => $length,
            'buckets'  => $this->buckets($entries, $from, $to, $length),
            'byTask'   => $entries->groupBy('task_id')->map(fn ($items) => ['task' => $items->first()->task, 'minutes' => $items->sum('minutes')])->sortByDesc('minutes'),
            'entries'  => $entries->sortByDesc('started_at'),
            'total'    => $entries->sum('minutes'),
            // الأسهم: نفس طول الفترة لورا / لقدام
            'prev'     => ['from' => $from->copy()->subDays($length)->toDateString(), 'to' => $from->copy()->subDay()->toDateString()],
            'next'     => ['from' => $to->copy()->addDay()->toDateString(), 'to' => $to->copy()->addDays($length)->toDateString()],
            'running'  => $time->running($this->employee())?->load('task:id,title'),
        ]);
    }

    /**
     * أعمدة الرسم: يوم بيوم لحد شهر، وبعدها شهر بشهر.
     *
     * @return array<int, array{label: string, minutes: int, current: bool}>
     */
    private function buckets(\Illuminate\Support\Collection $entries, Carbon $from, Carbon $to, int $length): array
    {
        if ($length <= self::DAILY_UP_TO) {
            $byDay = $entries->groupBy(fn ($e) => $e->started_at->toDateString())->map->sum('minutes');

            return collect(range(0, $length - 1))->map(function ($i) use ($from, $byDay, $length) {
                $day = $from->copy()->addDays($i);

                return [
                    // أسبوع أو أقل: اسم اليوم، أكتر: رقم اليوم/الشهر
                    'label'   => $length <= 7 ? $day->translatedFormat('D') : $day->format('j/n'),
                    'minutes' => (int) ($byDay[$day->toDateString()] ?? 0),
                    'current' => $day->isToday(),
                ];
            })->all();
        }

        $byMonth = $entries->groupBy(fn ($e) => $e->started_at->format('Y-m'))->map->sum('minutes');
        $buckets = [];

        for ($month = $from->copy()->startOfMonth(); $month->lte($to); $month->addMonth()) {
            $buckets[] = [
                'label'   => $month->translatedFormat('M y'),
                'minutes' => (int) ($byMonth[$month->format('Y-m')] ?? 0),
                'current' => $month->isSameMonth(now()),
            ];
        }

        return $buckets;
    }

    public function stop(Request $request, TaskTimeService $time)
    {
        $time->stop($this->employee(), $request->validate(['note' => ['nullable', 'string', 'max:500']])['note'] ?? null);

        return back()->with('success', __('task.messages.timer_stopped'));
    }

    /**
     * الفترة المطلوبة: from/to، أو أسبوع ?week=، أو الأسبوع الحالي.
     * معكوسة ← بتتبدّل، وأطول من MAX_DAYS ← بتتقص من الآخر.
     *
     * @return array{0: Carbon, 1: Carbon}  بداية اليوم الأول وبداية اليوم الأخير
     */
    private function timesheetPeriod(Request $request): array
    {
        $from = $this->parseDate($request->query('from'));
        $to = $this->parseDate($request->query('to'));

        if ($from === null && $to === null) {
            $week = ($this->parseDate($request->query('week')) ?? now())->startOfWeek(Carbon::SATURDAY)->startOfDay();

            return [$week, $week->copy()->addDays(6)];
        }

        // حقل واحد بس ← يوم واحد
        $from ??= $to->copy();
        $to ??= $from->copy();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($from->diffInDays($to) >= self::MAX_DAYS) {
            $to = $from->copy()->addDays(self::MAX_DAYS - 1);
        }

        return [$from, $to];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        // تاريخ حقيقي بس (2026-02-31 مايتحوّلش لمارس من غير ما ناخد بالنا)
        if (! is_string($value) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        return Carbon::createFromFormat('!Y-m-d', $value);
    }
}
