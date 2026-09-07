<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/** أنواع الإجازات وتأثيرها على الرصيد والراتب. */
enum LeaveType: string
{
    use HasEnumHelpers;

    case Annual = 'annual';
    case Casual = 'casual';
    case Sick   = 'sick';
    case Unpaid = 'unpaid';

    public function label(): string
    {
        return __('leave.type_enums.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Annual => 'bg-label-primary',
            self::Casual => 'bg-label-warning',
            self::Sick   => 'bg-label-info',
            self::Unpaid => 'bg-label-danger',
        };
    }

    /** عمود الرصيد المستهلك المقابل للنوع ده. */
    public function usedColumn(): string
    {
        return 'used_' . $this->value;
    }

    /** عمود الرصيد السنوي المخصص — الإجازة بدون أجر مالهاش رصيد. */
    public function balanceColumn(): ?string
    {
        return $this === self::Unpaid ? null : $this->value . '_balance';
    }

    /** هل بتتخصم من الراتب؟ */
    public function isDeductible(): bool
    {
        return $this === self::Unpaid;
    }

    /** هل ليها رصيد سنوي محدود؟ */
    public function hasBalance(): bool
    {
        return $this !== self::Unpaid;
    }
}
