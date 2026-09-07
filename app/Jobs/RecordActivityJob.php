<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * كتابة سجل الحركة في الخلفية.
 *
 * التسجيل كان بيعمل INSERT متزامن جوّه نفس الـ request. مع مسير رواتب
 * بـ 200 موظف كان ده 200 INSERT إضافي قبل ما المستخدم يشوف النتيجة.
 * دلوقتي بيتدفع للطابور والمستخدم مايستناش.
 *
 * الـ job ده مايعيدش المحاولة كتير: لو فشل التسجيل، ده مايستاهلش
 * تعطيل الطابور — بنسجّله في لوج الملفات وخلاص.
 */
class RecordActivityJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 30;

    /**
     * @param array<string, mixed> $attributes صف السجل جاهز للكتابة
     */
    public function __construct(
        public array $attributes,
    ) {
        $this->onQueue(config('activity.queue', 'default'));
    }

    public function handle(): void
    {
        ActivityLog::create($this->attributes);
    }

    /** فشل التسجيل مايوقعش أي حاجة تانية. */
    public function failed(\Throwable $e): void
    {
        Log::error('RecordActivityJob failed: ' . $e->getMessage(), [
            'subject_type' => $this->attributes['subject_type'] ?? null,
            'subject_id'   => $this->attributes['subject_id'] ?? null,
            'event'        => $this->attributes['event'] ?? null,
        ]);
    }
}
