<?php

namespace App\Http\Controllers\Admin\HR;

use App\Enums\AdvanceStatus;
use App\Http\Controllers\Concerns\RespondsToStateActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Advance\RecordAdvancePaymentRequest;
use App\Http\Requests\Admin\Advance\StoreAdvanceRequest;
use App\Http\Requests\Admin\Advance\WaiveAdvanceRequest;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Services\Export\ExcelExportService;
use App\Services\HR\AdvanceService;
use Illuminate\Http\Request;

/**
 * شاشات السلف. الاعتماد والسداد والإعفاء في AdvanceService.
 */
class EmployeeAdvanceController extends Controller
{
    use RespondsToStateActions;

    public function __construct(
        private AdvanceService $advances,
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            // select صريح: yajra بيعمل join تلقائي للترتيب بأعمدة
            // العلاقات، و select * ساعتها بيخلي أعمدة الجدول المنضم
            // تدهس أعمدة الجدول الأصلي (id بالذات).
            $query = EmployeeAdvance::visibleTo()
                ->select('employee_advances.*')->with(['employee:id,name,employee_code,branch_id', 'employee.branch:id,currency', 'approver:id,name']);

            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // نطاق بدل whereDate() عشان فهرس advance_date يشتغل
            $query->betweenDays($request->input('from_date'), $request->input('to_date'));

            return datatables($query)
                ->addColumn('employee_name', fn($row) => $row->employee?->name ?? '-')
                ->addColumn('employee_code', fn($row) => $row->employee?->employee_code ?? '-')
                ->addColumn('formatted_amount', function ($row) {
                    $curr = $row->employee?->branch?->currency ?? 'EGP';
                    return '<span class="fw-bold text-primary" dir="ltr">' . number_format($row->amount, 2) . ' ' . $curr . '</span>';
                })
                ->addColumn('formatted_installment', function ($row) {
                    $curr = $row->employee?->branch?->currency ?? 'EGP';
                    return '<span dir="ltr">' . number_format($row->monthly_installment, 2) . ' ' . $curr . '</span>' . ' <small class="text-muted">(' . $row->installments_count . ' شهر)</small>';
                })
                ->addColumn('formatted_remaining', function ($row) {
                    $curr = $row->employee?->branch?->currency ?? 'EGP';
                    return '<span class="text-danger fw-bold" dir="ltr">' . number_format($row->remaining_amount, 2) . ' ' . $curr . '</span>';
                })
                ->addColumn('formatted_advance_date', fn($row) => $row->advance_date ? $row->advance_date->format('Y-m-d') : '-')
                ->addColumn('formatted_start_date', fn($row) => $row->start_date ? $row->start_date->format('Y-m-d') : '-')
                // الحالة للعرض بس. التغيير بيتم من أزرار الاعتماد والرفض
                // عشان يحترم الصلاحيات ودورة الموافقة.
                ->addColumn('status_badge', fn ($row) => AdvanceStatus::badgeFor($row->status, 'advance.status_enums'))
                ->addColumn('approver_name', fn($row) => $row->approver?->name ?? '-')
                ->addColumn('actions', fn ($row) => view('admin.hr.advances.partials.actions', ['row' => $row])->render())
                ->rawColumns(['formatted_amount', 'formatted_installment', 'formatted_remaining', 'status_badge', 'actions'])
                ->make(true);
        }

        $employees = Employee::visibleTo()->active()->with('branch:id,name,currency')->get(['id', 'name', 'employee_code', 'branch_id']);

        $selectedEmployeeId = $request->get('employee_id');
        $selectedEmployee   = null;
        $advanceStats       = null;

        if ($selectedEmployeeId) {
            $selectedEmployee = Employee::with('branch:id,name,currency')->find($selectedEmployeeId);
            if ($selectedEmployee) {
                // استعلام واحد بدل 4، والمرفوضة مستبعدة من كل الأرقام —
                // قبل كده المتبقي كان بيحسب رصيد السلف المرفوضة كأنه مستحق.
                $totals = EmployeeAdvance::where('employee_id', $selectedEmployeeId)
                    ->where('status', '!=', AdvanceStatus::Rejected->value)
                    ->selectRaw('SUM(amount) as total_amount, SUM(paid_amount) as total_paid, SUM(waived_amount) as total_waived, SUM(remaining_amount) as total_remaining')
                    ->first();

                $advanceStats = [
                    'total_advances'  => (float) $totals?->total_amount,
                    'total_paid'      => (float) $totals?->total_paid,
                    'total_waived'    => (float) $totals?->total_waived,
                    'total_remaining' => (float) $totals?->total_remaining,
                    'currency'        => $selectedEmployee->branch?->currency ?? 'EGP',
                ];
            }
        }

        $fromDate = $request->from_date;
        $toDate   = $request->to_date;
        $selectedStatus = $request->status;
        $trashCount = EmployeeAdvance::visibleTo()->onlyTrashed()->count();

        // Check Excel Export
        if ($request->get('export') === 'excel') {
            $exportQuery = EmployeeAdvance::visibleTo()->with(['employee.branch', 'employee.jobTitle']);
            if ($request->filled('employee_id')) {
                $exportQuery->where('employee_id', $request->employee_id);
            }
            if ($request->filled('status')) {
                $exportQuery->where('status', $request->status);
            }
            $exportQuery->betweenDays($request->input('from_date'), $request->input('to_date'));

            // سقف للتصدير عشان الملف مايبلعش الذاكرة (الأحدث الأول)
            $advances = $exportQuery->orderByDesc('advance_date')->limit(ExcelExportService::MAX_ROWS)->get();

            $headers = [
                __('global.export.row_no'),
                __('global.export.employee_code'),
                __('global.export.employee_name'),
                __('global.export.branch'),
                __('advance.export.advance_amount'),
                __('global.export.currency'),
                __('advance.export.installments_count'),
                __('advance.export.monthly_installment'),
                __('advance.export.paid'),
                __('advance.export.waived'),
                __('advance.export.remaining'),
                __('advance.export.advance_date'),
                __('advance.export.start_date'),
                __('global.export.status'),
            ];

            $rows = [];
            $i = 1;
            $sumAmount = 0;
            $sumPaid = 0;
            $sumWaived = 0;
            $sumRemaining = 0;

            foreach ($advances as $adv) {
                $curr = $adv->employee?->branch?->currency ?? 'EGP';
                $sumAmount += (float)$adv->amount;
                $sumPaid += (float)$adv->paid_amount;
                $sumWaived += (float)$adv->waived_amount;
                $sumRemaining += (float)$adv->remaining_amount;

                $statusLabel = __('advance.status_enums.' . $adv->status) ?? $adv->status;

                $rows[] = [
                    $i++,
                    $adv->employee?->employee_code ?? '-',
                    $adv->employee?->name ?? '-',
                    $adv->employee?->branch?->name ?? '-',
                    number_format($adv->amount, 2),
                    $curr,
                    $adv->installments_count,
                    number_format($adv->monthly_installment, 2),
                    number_format($adv->paid_amount, 2),
                    number_format($adv->waived_amount, 2),
                    number_format($adv->remaining_amount, 2),
                    $adv->advance_date ? $adv->advance_date->format('Y-m-d') : '-',
                    $adv->start_date ? $adv->start_date->format('Y-m-d') : '-',
                    $statusLabel,
                ];
            }

            $totals = [
                'الإجمالي',
                '',
                '',
                '',
                number_format($sumAmount, 2),
                '',
                '',
                '',
                number_format($sumPaid, 2),
                number_format($sumWaived, 2),
                number_format($sumRemaining, 2),
                '',
                '',
                '',
            ];

            $meta = [
                'إجمالي السجلات' => count($rows),
                'الفترة'        => ($fromDate ? "من $fromDate " : '') . ($toDate ? "إلى $toDate" : 'الكل'),
                'الموظف'        => $selectedEmployee ? $selectedEmployee->name : 'جميع الموظفين',
            ];

            return ExcelExportService::download(
                'تقرير وسجل السلف والقروض المالية',
                $headers,
                $rows,
                $totals,
                'سجل_السلف_' . date('Y_m_d'),
                $meta
            );
        }

        return view('admin.hr.advances.index', compact(
            'employees',
            'selectedEmployee',
            'selectedEmployeeId',
            'advanceStats',
            'fromDate',
            'toDate',
            'selectedStatus',
            'trashCount'
        ));
    }

    public function store(StoreAdvanceRequest $request)
    {
        $this->advances->create($request->validated());

        return redirect()->back()->with('success', __('advance.messages.created'));
    }

    public function approve(EmployeeAdvance $advance)
    {
        return $this->attempt(fn () => $this->advances->approve($advance), 'advance.messages.approved');
    }

    public function reject(EmployeeAdvance $advance)
    {
        return $this->attempt(fn () => $this->advances->reject($advance), 'advance.messages.rejected');
    }

    public function recordPayment(RecordAdvancePaymentRequest $request, EmployeeAdvance $advance)
    {
        return $this->attempt(
            fn () => $this->advances->recordPayment(
                $advance,
                (float) $request->validated('amount'),
                $request->validated('repayment_date'),
                $request->validated('notes'),
            ),
            'advance.messages.payment_recorded'
        );
    }

    public function waive(WaiveAdvanceRequest $request, EmployeeAdvance $advance)
    {
        return $this->attempt(
            fn () => $this->advances->waive($advance, (float) $request->validated('waived_amount'), $request->validated('reason')),
            'advance.messages.waived'
        );
    }

    public function repayments(EmployeeAdvance $advance)
    {
        return response()->json([
            'advance'    => $advance,
            'repayments' => $advance->repayments()->with('creator:id,name')->get(),
        ]);
    }

    public function destroy(EmployeeAdvance $advance)
    {
        return $this->attempt(fn () => $this->advances->delete($advance), 'advance.messages.deleted');
    }

    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = EmployeeAdvance::visibleTo()->onlyTrashed()->with(['employee:id,name,employee_code,branch_id', 'employee.branch:id,currency']);
            return datatables($query)
                ->addColumn('employee_name', fn($row) => $row->employee?->name ?? '-')
                ->addColumn('employee_code', fn($row) => $row->employee?->employee_code ?? '-')
                ->addColumn('formatted_amount', function ($row) {
                    $curr = $row->employee?->branch?->currency ?? 'EGP';
                    return '<span class="fw-bold text-primary" dir="ltr">' . number_format($row->amount, 2) . ' ' . $curr . '</span>';
                })
                ->addColumn('formatted_advance_date', fn($row) => $row->advance_date ? $row->advance_date->format('Y-m-d') : '-')
                ->addColumn('deleted_at', fn($row) => $row->deleted_at ? $row->deleted_at->format('Y-m-d H:i') : '-')
                ->rawColumns(['formatted_amount'])
                ->make(true);
        }
        return view('admin.hr.advances.trash');
    }

    public function restore(int $id)
    {
        $adv = EmployeeAdvance::onlyTrashed()->findOrFail($id);
        $adv->restore();
        return redirect()->back()->with('success', __('global.restore'));
    }

    public function forceDelete(int $id)
    {
        $adv = EmployeeAdvance::onlyTrashed()->findOrFail($id);
        $adv->forceDelete();
        return redirect()->back()->with('success', __('global.force_delete'));
    }

}
