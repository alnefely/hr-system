<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * آخر طريقة عرض للمهام (قائمة / لوحة / تقويم) — بتتحفظ في cookie على
 * المتصفح، cookie منفصلة لكل لوحة (البوابة والإدارة).
 *
 * القاعدة:
 *  - كل شاشة عرض بتسجّل نفسها لما تتفتح.
 *  - القائمة لما تتفتح "من غير أي حاجة في الرابط" (القائمة الجانبية،
 *    زرار الرجوع) بتحوّل لآخر عرض.
 *  - رابط فيه فلتر (مثلاً كارت "متأخرة") بيفتح القائمة زي ما هو — اللوحة
 *    والتقويم مابيفلتروش بالحالة، فالتحويل كان هيضيّع الفلتر.
 *  - زرار "قائمة" في المبدّل بيبعت view=list (اختيار صريح)، فمابيرجعش للوحة.
 */
trait RemembersTaskView
{
    /** @var array<int, string> */
    private array $taskViews = ['index', 'board', 'calendar'];

    abstract protected function taskViewArea(): string;

    /** هل العرض ده متاح للمستخدم الحالي؟ (اللوحة في الإدارة بصلاحية) */
    protected function taskViewAllowed(string $view): bool
    {
        return true;
    }

    protected function rememberTaskView(string $view): void
    {
        Cookie::queue($this->taskViewCookie(), $view, 60 * 24 * 365);
    }

    /** تحويل لآخر عرض لو القائمة اتفتحت من غير أي معاملات. */
    protected function redirectToRememberedTaskView(Request $request): ?RedirectResponse
    {
        if ($request->query() !== []) {
            return null;
        }

        $view = $request->cookie($this->taskViewCookie());

        if (! in_array($view, ['board', 'calendar'], true) || ! $this->taskViewAllowed($view)) {
            return null;
        }

        return redirect()->route($this->taskViewArea() . '.tasks.' . $view);
    }

    private function taskViewCookie(): string
    {
        return 'tasks_view_' . $this->taskViewArea();
    }
}
