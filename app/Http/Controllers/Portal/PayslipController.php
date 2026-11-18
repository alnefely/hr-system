<?php

namespace App\Http\Controllers\Portal;

use App\Enums\PayrollItemStatus;
use Illuminate\Http\Request;

/**
 * قسائم راتبي — المصروفة بس.
 *
 * البند قبل الصرف ممكن يتعدّل أو المسير يترفض، فعرضه للموظف كان هيدي
 * رقم ممكن يتغيّر. الفلتر بتاريخ الصرف.
 */
class PayslipController extends PortalController
{
    public function index(Request $request)
    {
        $employee = $this->employee();
        $employee->loadMissing('branch:id,currency');
        $period = $this->period();

        if ($request->ajax()) {
            $currency = e($employee->branch?->currency ?? '');
            $money = fn ($v, string $class = '', string $sign = '') => '<span dir="ltr" class="text-nowrap ' . $class . '">' . $sign . number_format((float) $v, 2) . '</span>';

            $query = $this->paidItems()
                ->when($period[0], fn ($q) => $q->where('paid_at', '>=', $period[0] . ' 00:00:00'))
                ->when($period[1], fn ($q) => $q->where('paid_at', '<=', $period[1] . ' 23:59:59'))
                ->with('payroll:id,year,month');

            return datatables($query)
                ->addColumn('period', fn ($i) => '<span dir="ltr">' . sprintf('%02d / %04d', $i->payroll?->month, $i->payroll?->year) . '</span>')
                ->editColumn('basic_salary', fn ($i) => $money($i->basic_salary))
                ->editColumn('allowances', fn ($i) => $money($i->allowances, 'text-success', '+'))
                ->editColumn('deductions', fn ($i) => $money($i->deductions, 'text-danger', '-'))
                ->editColumn('net_salary', fn ($i) => $money($i->net_salary, 'fw-bold') . ' ' . $currency)
                ->editColumn('paid_at', fn ($i) => $i->paid_at?->format('Y-m-d'))
                ->addColumn('action', fn ($i) => '<a href="' . route('portal.payslips.show', $i->id) . '" target="_blank" class="btn btn-sm btn-label-primary">'
                    . '<i class="fa-solid fa-print me-1"></i>' . e(__('portal.payslips.view')) . '</a>')
                ->rawColumns(['period', 'basic_salary', 'allowances', 'deductions', 'net_salary', 'action'])
                ->make(true);
        }

        return view('portal.payslips.index', [
            'employee' => $employee,
            'period'   => $period,
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
        return $this->employee()->payrollItems()->reorder()->where('status', PayrollItemStatus::Paid->value);
    }
}
