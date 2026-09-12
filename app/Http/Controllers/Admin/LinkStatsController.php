<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Models\LinkDailyStat;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * شاشة إحصائيات الرابط.
 *
 * كل الأرقام من link_daily_stats — مفيش أي COUNT(*) على
 * link_clicks. العدّ المباشر بينهار بعد ملايين الصفوف، وكمان
 * النقرات الخام بتتحذف بعد ٩٠ يوم بينما التجميع بيفضل.
 */
class LinkStatsController extends Controller
{
    public function show(Request $request, Link $link)
    {
        [$from, $to] = $this->period($request);

        // الصفوف دي أساس كل حاجة في الشاشة
        $stats = LinkDailyStat::where('link_id', $link->id)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();

        return view('admin.links.stats', [
            'link'      => $link,
            'from'      => $from->toDateString(),
            'to'        => $to->toDateString(),
            'summary'   => $this->summary($link, $stats),
            'timeline'  => $this->timeline($stats, $from, $to),
            'countries' => $this->mergeJson($stats, 'by_country'),
            'devices'   => $this->mergeJson($stats, 'by_device'),
            'browsers'  => $this->mergeJson($stats, 'by_browser'),
            'referrers' => $this->mergeJson($stats, 'by_referrer'),
        ]);
    }

    /** الفترة المختارة — الافتراضي آخر ٣٠ يوم. */
    private function period(Request $request): array
    {
        $from = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->subDays(29)->startOfDay();

        $to = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->startOfDay()
            : now()->startOfDay();

        // لو المستخدم عكس التواريخ بنصلّحها بدل نتيجة فاضية
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function summary(Link $link, $stats): array
    {
        return [
            // الإجمالي الكلي من عمود الرابط نفسه (بيشمل كل التاريخ،
            // حتى الأيام اللي نقراتها الخام اتحذفت)
            'total_clicks'  => (int) $link->clicks_count,
            'unique_clicks' => (int) $link->unique_clicks_count,

            // وأرقام الفترة المختارة من التجميع
            'period_clicks' => (int) $stats->sum('clicks'),
            'period_unique' => (int) $stats->sum('unique_clicks'),
            'bot_clicks'    => (int) $stats->sum('bot_clicks'),
            'best_day'      => $stats->sortByDesc('clicks')->first()?->date?->toDateString(),
            'daily_average' => $stats->count() ? round($stats->sum('clicks') / $stats->count(), 1) : 0,
        ];
    }

    /**
     * السلسلة الزمنية.
     *
     * الأيام اللي مافيهاش نقرات مالهاش صفوف، فبنملاها بأصفار —
     * من غير كده الشارت بيوصل النقط ويرسم خط كذّاب.
     */
    private function timeline($stats, Carbon $from, Carbon $to): array
    {
        $byDate = $stats->keyBy(fn ($row) => $row->date->toDateString());

        $labels = [];
        $clicks = [];
        $unique = [];

        // سقف ٣٦٥ يوم: نطاق أطول بيبقى غير مقروء في الشارت
        $cursor = $from->copy();
        $guard  = 0;

        while ($cursor->lte($to) && $guard++ < 365) {
            $key = $cursor->toDateString();
            $row = $byDate->get($key);

            $labels[] = $key;
            $clicks[] = $row ? (int) $row->clicks : 0;
            $unique[] = $row ? (int) $row->unique_clicks : 0;

            $cursor->addDay();
        }

        return ['labels' => $labels, 'clicks' => $clicks, 'unique' => $unique];
    }

    /**
     * دمج توزيعات كل الأيام في ليستة واحدة.
     *
     * كل صف بيحمل توزيع يومه، فبنجمعهم ونرتّبهم ونرجّع أعلى ١٠.
     */
    private function mergeJson($stats, string $column): array
    {
        $merged = [];

        foreach ($stats as $row) {
            foreach (($row->{$column} ?? []) as $key => $count) {
                $merged[$key] = ($merged[$key] ?? 0) + (int) $count;
            }
        }

        arsort($merged);

        return array_slice($merged, 0, 10, true);
    }
}
