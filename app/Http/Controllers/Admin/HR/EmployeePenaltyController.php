<?php

namespace App\Http\Controllers\Admin\HR;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Concerns\RespondsToStateActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Penalty\StorePenaltyRequest;
use App\Http\Requests\Admin\StrictRejectionRequest;
use App\Models\Employee;
use App\Models\EmployeePenalty;
use App\Services\Export\ExcelExportService;
use App\Services\HR\ApprovalService;
use Illuminate\Http\Request;

/**
 * الجزاءات الإدارية. الاعتماد والرفض عبر ApprovalService (مع فصل المهام).
 */
class EmployeePenaltyController extends Controller
{
    use RespondsToStateActions;

    public function __construct(
        private ApprovalService $approvals,
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            // select صريح: yajra بيعمل join تلقائي للترتيب بأعمدة
            // العلاقات، و select * ساعتها بيخلي أعمدة الجدول المنضم
            // تدهس أعمدة الجدول الأصلي (id بالذات).
            $query = EmployeePenalty::select('employee_penalties.*')
                // branch_id + employee.branch لازمين: عمود الخصم بيعرض عملة
                // فرع الموظف، ومن غيرهم العلاقة كانت بتطلع null والعملة دايماً EGP.
                ->with(['employee:id,name,employee_code,branch_id', 'employee.branch:id,currency', 'creator:id,name']);

            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->filled('penalty_type')) {
                $query->where('penalty_type', $request->penalty_type);
            }

            return datatables($query)
                ->addColumn('employee_name', fn($row) => $row->employee?->name ?? '-')
                ->addColumn('employee_code', fn($row) => $row->employee?->employee_code ?? '-')
                ->addColumn('type_badge', function ($row) {
                    $types = [
                        'days_deduction'   => 'bg-label-danger',
                        'amount_deduction' => 'bg-label-warning',
                        'warning'          => 'bg-label-info',
                    ];
                    $badge = $types[$row->penalty_type] ?? 'bg-label-secondary';
                    $lbl = __('penalty.type_enums.' . $row->penalty_type) ?? $row->penalty_type;
                    return '<span class="badge ' . $badge . '">' . $lbl . '</span>';
                })
                ->addColumn('deduction_summary', function ($row) {
                    if ($row->penalty_type === 'days_deduction') {
                        $curr = $row->employee?->branch?->currency ?? 'EGP';
                        $amountInfo = $row->deduction_amount > 0 ? ' (<span dir="ltr">' . number_format($row->deduction_amount, 2) . '</span> ' . $curr . ')' : '';
                        return '<span class="text-danger fw-bold"><span dir="ltr">' . $row->days_count . '</span> ' . __('يوم') . $amountInfo . '</span>';
                    } elseif ($row->penalty_type === 'amount_deduction') {
                        $curr = $row->employee?->branch?->currency ?? 'EGP';
                        return '<span class="text-danger fw-bold"><span dir="ltr">' . number_format($row->deduction_amount, 2) . '</span> ' . $curr . '</span>';
                    }
                    return '<span class="text-muted">إنذار إداري</span>';
                })
                ->addColumn('formatted_date', fn($row) => $row->penalty_date ? $row->penalty_date->format('Y-m-d') : '-')
                ->addColumn('creator_name', fn($row) => $row->creator?->name ?? '-')
                ->addColumn('status_badge', fn ($row) => view('admin.hr.partials.approval-status', ['row' => $row])->render())
                ->addColumn('actions', fn ($row) => view('admin.hr.partials.approval-actions', [
                    'row'          => $row,
                    'module'       => 'penalty',
                    'routes'       => 'admin.penalties',
                    'approveTitle' => 'اعتماد الجزاء',
                ])->render())
                ->rawColumns(['status_badge', 'type_badge', 'deduction_summary', 'actions'])
                ->make(true);
        }

        if ($request->get('export') === 'excel') {
            $query = EmployeePenalty::with(['employee:id,name,employee_code,branch_id', 'employee.branch:id,currency']);
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }
            if ($request->filled('penalty_type')) {
                $query->where('penalty_type', $request->penalty_type);
            }

            // سقف للتصدير عشان الملف مايبلعش الذاكرة (الأحدث الأول)
            $penalties = $query->latest('penalty_date')->limit(ExcelExportService::MAX_ROWS)->get();
            $headers = [
                __('global.export.row_no'),
                __('global.export.employee_code'),
                __('global.export.employee_name'),
                __('penalty.export.penalty_type'),
                __('penalty.export.days_deducted'),
                __('penalty.export.deduction_amount'),
                __('global.export.currency'),
                __('penalty.export.penalty_date'),
                __('penalty.export.reason'),
                __('penalty.export.admin_notes'),
            ];
            $rows = [];
            $i = 1;
            $totalDays = 0;
            $totalAmount = 0;
            foreach ($penalties as $pen) {
                $totalDays += (float) $pen->days_count;
                $totalAmount += (float) $pen->deduction_amount;
                $curr = $pen->employee?->branch?->currency ?? 'EGP';
                $rows[] = [
                    $i++,
                    $pen->employee?->employee_code ?? '-',
                    $pen->employee?->name ?? '-',
                    __('penalty.type_enums.' . $pen->penalty_type) ?? $pen->penalty_type,
                    $pen->days_count > 0 ? $pen->days_count : '-',
                    number_format($pen->deduction_amount, 2),
                    $curr,
                    $pen->penalty_date ? $pen->penalty_date->format('Y-m-d') : '-',
                    $pen->reason,
                    $pen->notes ?? '-',
                ];
            }

            $totals = [
                'الإجمالي',
                '',
                count($penalties) . ' جزاء',
                '',
                $totalDays . ' يوم',
                number_format($totalAmount, 2),
                '',
                '',
                'تم التصدير بنجاح',
                '',
            ];

            return ExcelExportService::download('سجل الجزاءات والخصومات الإدارية', $headers, $rows, $totals, 'الجزاءات_' . date('Ymd_His'));
        }

        $employees = Employee::active()->with('branch:id,name,currency')->get(['id', 'name', 'employee_code', 'branch_id', 'basic_salary']);

        $selectedEmployeeId = $request->get('employee_id');
        $selectedEmployee   = null;
        $penaltyStats       = null;

        if ($selectedEmployeeId) {
            $selectedEmployee = Employee::with('branch:id,name,currency')->find($selectedEmployeeId);
            if ($selectedEmployee) {
                $totalPenalties = EmployeePenalty::where('employee_id', $selectedEmployeeId)->count();
                $totalDays      = (float) EmployeePenalty::where('employee_id', $selectedEmployeeId)->sum('days_count');
                $totalAmount    = (float) EmployeePenalty::where('employee_id', $selectedEmployeeId)->sum('deduction_amount');

                $penaltyStats = [
                    'total_penalties' => $totalPenalties,
                    'total_days'      => $totalDays,
                    'total_amount'    => $totalAmount,
                    'currency'        => $selectedEmployee->branch?->currency ?? 'EGP',
                ];
            }
        }

        $trashCount = EmployeePenalty::onlyTrashed()->count();

        return view('admin.hr.penalties.index', compact(
            'employees',
            'selectedEmployee',
            'selectedEmployeeId',
            'penaltyStats',
            'trashCount'
        ));
    }

    public function store(StorePenaltyRequest $request)
    {
        // الجزاء بيبدأ قيد الاعتماد: مايدخلش المسير غير بعد المراجعة.
        EmployeePenalty::create([
            'created_by' => admin()?->id,
            'status'     => ApprovalStatus::Pending->value,
        ] + $request->penaltyData());

        return redirect()->route('admin.penalties.index')->with('success', __('penalty.messages.created'));
    }

    public function destroy(EmployeePenalty $penalty)
    {
        // بعد الاعتماد الجزاء بقى جزء من المسير، فالحذف بيكسر التطابق.
        if (!$penalty->isEditable()) {
            return redirect()->back()->with('error', __('penalty.messages.cannot_modify_approved'));
        }

        $penalty->delete();
        return redirect()->back()->with('success', __('penalty.messages.deleted'));
    }
    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = EmployeePenalty::onlyTrashed()->with(['employee:id,name,employee_code']);
            return datatables($query)
                ->addColumn('employee_name', fn($row) => $row->employee?->name ?? '-')
                ->addColumn('employee_code', fn($row) => $row->employee?->employee_code ?? '-')
                ->addColumn('penalty_date', fn($row) => $row->penalty_date ? $row->penalty_date->format('Y-m-d') : '-')
                ->addColumn('deleted_at', fn($row) => $row->deleted_at ? $row->deleted_at->format('Y-m-d H:i') : '-')
                ->make(true);
        }
        return view('admin.hr.penalties.trash');
    }

    public function restore(int $id)
    {
        $pen = EmployeePenalty::onlyTrashed()->findOrFail($id);
        $pen->restore();
        return redirect()->back()->with('success', __('global.restore'));
    }

    public function forceDelete(int $id)
    {
        $pen = EmployeePenalty::onlyTrashed()->findOrFail($id);
        $pen->forceDelete();
        return redirect()->back()->with('success', __('global.force_delete'));
    }

    // ========================================================
    //  دورة الاعتماد
    // ========================================================

    /**
     * اعتماد الحركة — فصل المهام: اللي سجّلها مايعتمدهاش.
     *
     * المبدأ محاسبياً: أي حركة بتأثر على راتب الموظف لازم يراجعها
     * شخص تاني، عشان مايبقاش نفس الشخص هو اللي يسجّل ويصرف.
     */
    public function approve(EmployeePenalty $penalty)
    {
        return $this->attempt(
            fn () => $this->approvals->approve($penalty, 'penalty'),
            'penalty.messages.approved'
        );
    }

    /** رفض الحركة مع توثيق السبب — المعلّق بس. */
    public function reject(StrictRejectionRequest $request, EmployeePenalty $penalty)
    {
        return $this->attempt(
            fn () => $this->approvals->reject($penalty, $request->reason(), 'penalty'),
            'penalty.messages.rejected'
        );
    }
}
