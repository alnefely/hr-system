<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;

/**
 * مزوّد مستخدمي بوابة الموظف.
 *
 * نفس EloquentUserProvider بالظبط، لكن من غير Global Scope عزل الفروع:
 * العزل بيتحدد من جلسة المشرف (guard: admin)، ولو مشرف فرع وموظف من فرع
 * تاني فاتحين من نفس المتصفح، الـ Scope كان هيرجّع null للموظف ويطلّعه.
 *
 * SoftDeletes بيفضل شغال: الموظف المحذوف مايدخلش.
 */
class EmployeeUserProvider extends EloquentUserProvider
{
    protected function newModelQuery($model = null)
    {
        return parent::newModelQuery($model)->withoutGlobalScope('branch');
    }
}
