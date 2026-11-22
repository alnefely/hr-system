<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * حالة المهمة + قواعد الانتقال بين الحالات.
 *
 *   جديدة ← قيد التنفيذ ← مراجعة ← مكتملة
 *                 ↑__________↓ (رفض المراجعة بيرجّعها قيد التنفيذ)
 *   أي حالة مفتوحة ← ملغية
 *   مكتملة / ملغية ← إعادة فتح ← قيد التنفيذ
 *
 * القواعد هنا عشان اللوحة والبوابة ولوحة كانبان يستخدموا نفس المنطق.
 */
enum TaskStatus: string
{
    use HasEnumHelpers;

    case New        = 'new';
    case InProgress = 'in_progress';
    case InReview   = 'in_review';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return __('task.status_enums.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::New        => 'bg-label-secondary',
            self::InProgress => 'bg-label-primary',
            self::InReview   => 'bg-label-warning',
            self::Completed  => 'bg-label-success',
            self::Cancelled  => 'bg-label-dark',
        };
    }

    /** المهمة لسه شغّالة (بتظهر في "المهام المفتوحة"). */
    public function isOpen(): bool
    {
        return ! $this->isClosed();
    }

    public function isClosed(): bool
    {
        return $this === self::Completed || $this === self::Cancelled;
    }

    /** @return array<int, string> قيم الحالات المفتوحة — لاستعلامات whereIn */
    public static function openValues(): array
    {
        return [self::New->value, self::InProgress->value, self::InReview->value];
    }

    /** @return array<int, self> الحالات اللي ينفع تنتقل لها من الحالة دي */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New        => [self::InProgress, self::InReview, self::Completed, self::Cancelled],
            self::InProgress => [self::New, self::InReview, self::Completed, self::Cancelled],
            self::InReview   => [self::InProgress, self::Completed, self::Cancelled],
            self::Completed  => [self::InProgress],
            self::Cancelled  => [self::New, self::InProgress],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }
}
