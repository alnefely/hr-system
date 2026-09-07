<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/** حالة سداد فاتورة المبيعات — محسوبة من سجل الدفعات. */
enum SalePaymentStatus: string
{
    use HasEnumHelpers;

    case Paid          = 'paid';
    case PartiallyPaid = 'partially_paid';
    case Unpaid        = 'unpaid';

    public function label(): string
    {
        return __('sale.statuses.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Paid          => 'bg-label-success',
            self::PartiallyPaid => 'bg-label-warning',
            self::Unpaid        => 'bg-label-danger',
        };
    }

    /** الحالة المشتقة من المبالغ — المصدر الوحيد للحقيقة. */
    public static function fromAmounts(float $total, float $paid): self
    {
        if ($total - $paid <= 0.001) {
            return self::Paid;
        }

        return $paid > 0 ? self::PartiallyPaid : self::Unpaid;
    }

    public function hasOutstanding(): bool
    {
        return $this !== self::Paid;
    }
}
