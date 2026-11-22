<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

use function Illuminate\Support\defer;

/**
 * البث اللحظي (Pusher) بعد ما الرد يرجع للمستخدم.
 *
 * الأحداث ShouldBroadcastNow (مفيش worker شغال طول الوقت)، فالبث كان
 * بيتعمل جوّه الطلب نفسه: اتصال HTTP لكل مستلم بمهلة 5 ثواني. تغيير
 * حالة مهمة عليها 4 أشخاص كان ممكن ياخد 20+ ثانية لو Pusher بطيء.
 *
 * defer() بيأجّل الإرسال لبعد الرد (طلب ويب)، أو لآخر الأمر (artisan)،
 * أو لآخر الـ job — والمستخدم مايستناش. always: يتبعت حتى لو الأمر
 * خلص بخطأ. وفشل البث عمره ما بيأثر على العملية الأصلية.
 *
 * في الاختبارات: withoutDefer() عشان يتنفذ فوراً.
 */
final class Realtime
{
    public static function broadcast(callable $dispatch, string $context): void
    {
        if (config('broadcasting.default') === 'null') {
            return;
        }

        defer(function () use ($dispatch, $context) {
            try {
                $dispatch();
            } catch (\Throwable $e) {
                Log::warning($context . ' broadcast failed: ' . $e->getMessage());
            }
        }, always: true);
    }
}
