<?php

namespace App\Support;

/** عرض المدد بالدقائق بشكل مقروء: 150 ← "2س 30د". */
class Duration
{
    public static function format(?int $minutes): string
    {
        $minutes = max(0, (int) $minutes);

        // يوم أو أكتر: بالأيام والساعات (600 ساعة مش مقروءة)
        if ($minutes >= 24 * 60) {
            return __('task.time.dh', ['d' => intdiv($minutes, 24 * 60), 'h' => intdiv($minutes % (24 * 60), 60)]);
        }

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return match (true) {
            $h === 0 => __('task.time.m', ['m' => $m]),
            $m === 0 => __('task.time.h', ['h' => $h]),
            default  => __('task.time.hm', ['h' => $h, 'm' => $m]),
        };
    }
}
