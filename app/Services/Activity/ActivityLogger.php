<?php

namespace App\Services\Activity;

use App\Jobs\RecordActivityJob;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * المسؤول الوحيد عن كتابة سجلات الحركة.
 *
 * كل الكتابة بتعدّي من هنا عشان:
 *  - التسجيل مايكسرش العملية الأصلية أبداً (أي خطأ بيتبلع ويتسجّل في اللوج).
 *  - نقدر نوقف التسجيل مؤقتاً في العمليات الجماعية (seeders, imports).
 *  - نقدر نحوّله للطابور من مكان واحد.
 *
 * الكتابة بتتم عبر الطابور افتراضياً (config: activity.queue_writes) عشان
 * الـ INSERT مايوقفش الـ request — مهم بشكل خاص في العمليات اللي بتكتب
 * عشرات السجلات زي إصدار مسير الرواتب.
 */
class ActivityLogger
{
    protected bool $enabled = true;

    /**
     * تسجيل حركة على موديل.
     *
     * بيرجع السجل لو اتكتب مباشرة، أو null لو اتدفع للطابور أو اتعطّل.
     */
    public function log(Model $subject, string $event, array $old = [], array $new = []): ?ActivityLog
    {
        if (!$this->enabled || !config('activity.enabled', true)) {
            return null;
        }

        try {
            $attributes = $this->buildAttributes($subject, $event, $old, $new);

            if ($this->shouldQueue()) {
                RecordActivityJob::dispatch($attributes);

                return null;
            }

            return ActivityLog::create($attributes);
        } catch (\Throwable $e) {
            // التسجيل مايوقعش العملية الأصلية: لو فشل، بنكتفي بتسجيله في لوج الملفات.
            Log::error('ActivityLogger failed: ' . $e->getMessage(), [
                'subject_type' => $subject->getMorphClass(),
                'subject_id'   => $subject->getKey(),
                'event'        => $event,
            ]);

            return null;
        }
    }

    /**
     * بناء صف السجل.
     *
     * بيتفصل عن الكتابة عشان الـ job يستقبل مصفوفة جاهزة — الموديل نفسه
     * ممكن يكون اتغيّر أو اتحذف لما الـ job يتنفذ.
     */
    protected function buildAttributes(Model $subject, string $event, array $old, array $new): array
    {
        $admin = auth('admin')->check() ? auth('admin')->user() : null;
        $request = request();

        return [
            'event'         => $event,
            'subject_type'  => $subject->getMorphClass(),
            'subject_id'    => $subject->getKey(),
            'subject_label' => method_exists($subject, 'activityLabel') ? $subject->activityLabel() : null,
            'causer_id'     => $admin?->id,
            'causer_name'   => $admin?->name,
            'old_values'    => $old ?: null,
            'new_values'    => $new ?: null,
            'ip_address'    => $request?->ip(),
            'user_agent'    => mb_substr((string) $request?->userAgent(), 0, 500) ?: null,
            'url'           => mb_substr((string) $request?->fullUrl(), 0, 500) ?: null,
            'method'        => $request?->method(),
        ];
    }

    /**
     * هل نكتب عبر الطابور؟
     *
     * بنرجع false لو الطابور sync أصلاً (مافيش فايدة) أو لو الإعداد مقفول.
     */
    protected function shouldQueue(): bool
    {
        return config('activity.queue_writes', false)
            && config('queue.default') !== 'sync';
    }

    /** تنفيذ عملية من غير تسجيل (استيراد، seeders، ترحيل بيانات). */
    public function withoutLogging(callable $callback): mixed
    {
        $previous = $this->enabled;
        $this->enabled = false;

        try {
            return $callback();
        } finally {
            $this->enabled = $previous;
        }
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
