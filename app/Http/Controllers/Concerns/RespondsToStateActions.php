<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\InvalidStateException;
use Closure;
use Illuminate\Http\RedirectResponse;

/**
 * تنفيذ عملية من خدمة وترجمة نتيجتها لرد.
 *
 * الخدمات بترمي InvalidStateException برسالة مترجمة لو العملية مش
 * مسموحة؛ الكنترولر بيعرضها للمستخدم من غير ما يعرف القاعدة نفسها.
 */
trait RespondsToStateActions
{
    protected function attempt(Closure $action, string $successKey): RedirectResponse
    {
        try {
            $action();
        } catch (InvalidStateException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', __($successKey));
    }
}
