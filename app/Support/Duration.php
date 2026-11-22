<?php

namespace App\Support;

/** عرض المدد بالدقائق بشكل مقروء: 150 ← "2س 30د". */
class Duration
{
    public static function format(?int $minutes): string
    {
        $minutes = max(0, (int) $minutes);
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return match (true) {
            $h === 0 => __('task.time.m', ['m' => $m]),
            $m === 0 => __('task.time.h', ['h' => $h]),
            default  => __('task.time.hm', ['h' => $h, 'm' => $m]),
        };
    }
}
