<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * أساس كنترولرات بوابة الموظف.
 *
 * القاعدة الوحيدة في البوابة: الموظف يشوف ويعدّل بياناته هو بس. فكل
 * استعلام بيبدأ من علاقات الموظف الحالي ($this->employee()->leaves())
 * مش من الموديل مباشرة — مستحيل يوصل لسجل زميله حتى لو غيّر الرقم في الرابط.
 *
 * فلتر التاريخ: from_date / to_date في رابط الصفحة (نفس أسماء لوحة الإدارة).
 * جدول DataTables بيطلب بياناته من نفس الرابط، فالفلتر بيوصل للاستعلام
 * والكروت الملخّصة من مكان واحد.
 */
abstract class PortalController extends Controller
{
    protected function employee(): Employee
    {
        return auth('employee')->user();
    }

    /**
     * الفترة المختارة [from, to] — أي قيمة مش تاريخ سليم بتتجاهل.
     * القيم الافتراضية بتتطبّق بس لو المستخدم مافلترش خالص (أول فتح للصفحة).
     *
     * @return array{0: ?string, 1: ?string}
     */
    protected function period(?string $defaultFrom = null, ?string $defaultTo = null): array
    {
        $request = request();

        if (! $request->has('from_date') && ! $request->has('to_date')) {
            return [$defaultFrom, $defaultTo];
        }

        $clean = function ($value): ?string {
            if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return null;
            }
            [$y, $m, $d] = array_map('intval', explode('-', $value));

            return checkdate($m, $d, $y) ? $value : null;
        };

        return [$clean($request->query('from_date')), $clean($request->query('to_date'))];
    }

    /** يطبّق الفترة على عمود التاريخ (inPeriod من HasDateRangeScopes). */
    protected function inPeriod(Builder|Relation $query, array $period, ?string $column = null): Builder|Relation
    {
        return $query->inPeriod($period[0], $period[1], $column);
    }
}
