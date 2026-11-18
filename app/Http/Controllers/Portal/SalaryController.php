<?php

namespace App\Http\Controllers\Portal;

/**
 * راتبي — للعرض بس: الراتب الحالي والبدلات الثابتة وسجل تعديلات الراتب
 * (من غير مين اعتمد — بيانات داخلية).
 */
class SalaryController extends PortalController
{
    public function index()
    {
        return view('portal.salary.index', [
            'employee' => $this->employee()->loadMissing([
                'branch:id,currency',
                'allowances:id,employee_id,name,amount',
                'salaries:id,employee_id,basic_salary,allowances,currency,effective_date,change_reason',
            ]),
        ]);
    }
}
