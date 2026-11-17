<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * إنهاء جلسة لوحة واحدة (الإدارة أو بوابة الموظف) من غير التأثير على التانية.
 *
 * اللوحتين بحارسين مستقلين (admin / employee) لكن في نفس كوكي الجلسة.
 * invalidate() بتمسح الجلسة كلها، فلو نفس المتصفح داخل اللوحتين الخروج من
 * واحدة كان هيخرج من التانية. هنا: لو اللوحة التانية لسه مسجّلة بنغيّر معرّف
 * الجلسة بس (ضد سرقة الجلسة)، وإلا بنمسحها بالكامل زي العادي.
 */
class PanelSession
{
    public static function end(Request $request, string $otherGuard): void
    {
        if (auth($otherGuard)->check()) {
            $request->session()->migrate(true);
        } else {
            $request->session()->invalidate();
        }

        $request->session()->regenerateToken();
    }
}
