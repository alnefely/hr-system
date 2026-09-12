<?php

namespace App\Services\Links;

/**
 * قراءة الجهاز والنظام والمتصفح من الـ User-Agent.
 *
 * مكتوب يدوي بدل حزمة خارجية — محتاجين ٣ قيم بس، والدقة المطلوبة
 * إحصائية مش تشخيصية. الترتيب مهم: المتصفحات اللي بتقلّد غيرها
 * (Edge بيكتب Chrome، Chrome بيكتب Safari) لازم تتفحص الأول.
 */
class UserAgentParser
{
    public function parse(?string $userAgent): array
    {
        $ua = (string) $userAgent;

        return [
            'device_type' => $this->device($ua),
            'os'          => $this->os($ua),
            'browser'     => $this->browser($ua),
        ];
    }

    private function device(string $ua): string
    {
        $lower = mb_strtolower($ua);

        // التابلت الأول: أغلب أجهزته بتكتب mobile كمان
        if (str_contains($lower, 'ipad')
            || str_contains($lower, 'tablet')
            || (str_contains($lower, 'android') && ! str_contains($lower, 'mobile'))) {
            return 'tablet';
        }

        if (str_contains($lower, 'mobile')
            || str_contains($lower, 'iphone')
            || str_contains($lower, 'ipod')
            || str_contains($lower, 'android')
            || str_contains($lower, 'windows phone')) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function os(string $ua): string
    {
        $map = [
            'Windows'   => ['windows nt', 'win64', 'windows phone'],
            'iOS'       => ['iphone', 'ipad', 'ipod'],
            'macOS'     => ['macintosh', 'mac os x'],
            'Android'   => ['android'],
            'Linux'     => ['linux', 'ubuntu', 'fedora'],
            'Chrome OS' => ['cros'],
        ];

        $lower = mb_strtolower($ua);

        // iOS قبل macOS: الآيباد بيكتب Mac OS X كمان
        foreach ($map as $name => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($lower, $needle)) {
                    return $name;
                }
            }
        }

        return 'Unknown';
    }

    private function browser(string $ua): string
    {
        $lower = mb_strtolower($ua);

        // الترتيب حرج: كل واحد من دول بيكتب اسم اللي بعده في الـ UA
        $map = [
            'Edge'    => ['edg/', 'edge/', 'edga/', 'edgios/'],
            'Opera'   => ['opr/', 'opera'],
            'Samsung' => ['samsungbrowser'],
            'Firefox' => ['firefox/', 'fxios/'],
            'Chrome'  => ['chrome/', 'crios/', 'chromium'],
            'Safari'  => ['safari/'],
            'IE'      => ['msie', 'trident/'],
        ];

        foreach ($map as $name => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($lower, $needle)) {
                    return $name;
                }
            }
        }

        return 'Unknown';
    }
}
