<?php

namespace App\Http\Requests\Admin;

/** رفض طلب (مسير، إجازة، إذن، جزاء، مكافأة، سلفة) — حقل rejection_reason. */
class RejectionRequest extends ReasonRequest
{
    protected string $field = 'rejection_reason';
}
