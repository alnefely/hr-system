<?php

namespace App\Http\Requests\Admin;

/**
 * رفض حركة مالية (جزاء، مكافأة، مسير) — السبب 3 حروف على الأقل
 * لأنه بيتوثّق ويتعرض لكل اللي بيراجعوا الحركة.
 */
class StrictRejectionRequest extends RejectionRequest
{
    protected int $min = 3;
}
