<?php

namespace App\Http\Controllers\Portal;

use App\Enums\PayrollItemStatus;

/**
 * قسائم راتبي — المصروفة بس.
 *
 * البند قبل الصرف ممكن يتعدّل أو المسير يترفض، فعرضه للموظف كان هيدي
 * رقم ممكن يتغيّر.
 */
class PayslipController extends PortalController
{
    public function index()
    {
        $this->employee()->loadMissing('branch:id,currency');

        return view('portal.payslips.index', [
            'employee' => $this->employee(),
            'items'    => $this->paidItems()->with('payroll:id,year,month,title')->latest('paid_at')->paginate(12),
        ]);
    }

    public function show(int $id)
    {
        $item = $this->paidItems()->whereKey($id)->firstOrFail();
        $item->load(['payroll.branch', 'employee.jobTitle', 'employee.branch']);

        // نفس قسيمة لوحة الإدارة (صفحة طباعة مستقلة)
        return view('admin.hr.payrolls.payslip', compact('item'));
    }

    private function paidItems()
    {
        return $this->employee()->payrollItems()->where('status', PayrollItemStatus::Paid->value);
    }
}
