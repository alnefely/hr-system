<?php

namespace App\Http\Controllers\Links;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Services\Links\ClickRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * تحويل الروابط المختصرة.
 *
 * الشاشة دي عامة (من غير تسجيل دخول) وبتتنادى كتير، فكل حاجة
 * فيها متحسّبة: الرابط من الكاش، والتحويل مابيستناش أي كتابة ثقيلة.
 */
class RedirectController extends Controller
{
    public function __construct(
        protected ClickRecorder $recorder
    ) {}

    public function handle(Request $request, string $code)
    {
        $link = $this->resolve($code);

        if (! $link) {
            return response()->view('links.not-found', [], 404);
        }

        if (! $link->is_active) {
            return response()->view('links.unavailable', [
                'reason' => __('link.public.inactive_text'),
            ], 410);
        }

        if ($link->is_expired) {
            return response()->view('links.unavailable', [
                'reason' => __('link.public.expired_text'),
            ], 410);
        }

        // التحويل أهم من الإحصائية: أي فشل في التسجيل بيتبلع.
        // الحارس هنا مش جوه المسجّل بس — لو الاتصال نفسه فشل،
        // الاستثناء بيطلع قبل ما نوصل للـ try اللي جواها.
        try {
            $this->recorder->record($link->id, $request);
        } catch (\Throwable $e) {
            Log::warning('Link click recording failed: ' . $e->getMessage(), [
                'link_id' => $link->id,
            ]);
        }

        // 302 مش 301: الـ 301 بيتكاش في المتصفح للأبد، يعني العدّاد
        // بيقف بعد أول زيارة وماتقدرش تغيّر وجهة الرابط بعد كده.
        return redirect()->away($link->original_url, 302)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Referrer-Policy', 'no-referrer-when-downgrade');
    }

    /**
     * جلب الرابط بالكود.
     *
     * بنشيل عزل الفرع بالاسم لأن الزائر مش مشرف وماعندوش فرع.
     *
     * مهم: withoutGlobalScopes() الشاملة ماتنفعش هنا — كانت بتشيل
     * SoftDeletingScope كمان، فالرابط المحذوف يفضل بيحوّل.
     */
    private function resolve(string $code): ?Link
    {
        $ttl = (int) config('links.cache_ttl', 21600);

        $cached = Cache::remember('link:' . $code, $ttl, function () use ($code) {
            $link = Link::withoutGlobalScope('branch')
                ->where('short_code', $code)
                ->first();

            // بنكاش المصفوفة مش الموديل عشان الكاش مايحملش حالة قديمة
            return $link?->only([
                'id', 'short_code', 'original_url', 'is_active',
                'expires_at', 'max_clicks', 'clicks_count',
            ]);
        });

        if (! $cached) {
            return null;
        }

        // السقف لازم يتحسب على رقم حقيقي.
        //
        // العمود في قاعدة البيانات بيتأخّر لحد ما links:flush يشتغل،
        // فبنجمع عليه النقرات اللي لسه في Redis — من غير كده رابط
        // سقفه ١٠٠ ممكن يعدّي ٢٠٠ جوه نافذة التفريغ.
        //
        // الاستعلام ده للروابط اللي ليها سقف بس، وهي النادرة.
        if (! empty($cached['max_clicks'])) {
            $stored = (int) Link::withoutGlobalScope('branch')
                ->whereKey($cached['id'])
                ->value('clicks_count');

            $cached['clicks_count'] = $stored + $this->pendingClicks((int) $cached['id']);
        }

        return (new Link)->forceFill($cached);
    }

    /** النقرات اللي لسه في Redis وماتفرّغتش لقاعدة البيانات. */
    private function pendingClicks(int $linkId): int
    {
        try {
            $pending = Redis::connection('cache')
                ->hget(ClickRecorder::COUNTER_KEY, $linkId . ':clicks');

            return (int) $pending;
        } catch (\Throwable $e) {
            // Redis واقع: بنكمل بالرقم المخزَّن بدل ما نوقّع التحويل
            return 0;
        }
    }
}
