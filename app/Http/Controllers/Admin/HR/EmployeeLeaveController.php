<?php

namespace App\Http\Controllers\Admin\HR;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Exceptions\InvalidStateException;
use App\Http\Controllers\Concerns\RespondsToStateActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Leave\StoreLeaveRequest;
use App\Http\Requests\Admin\Leave\UpdateLeaveBalanceRequest;
use App\Http\Requests\Admin\RejectionRequest;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveBalance;
use App\Services\HR\LeaveService;
use Illuminate\Http\Request;

/**
 * شاشات الإجازات. القواعد (الرصيد، التداخل، الاعتماد) في LeaveService.
 */
class EmployeeLeaveController extends Controller
{
    use RespondsToStateActions;

    public function __construct(
        private LeaveService $leaves,
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            // select صريح: yajra بيعمل join تلقائي للترتيب بأعمدة العلاقات،
            // و select * ساعتها بيخلي أعمدة الجدول المنضم تدهس id.
            $query = EmployeeLeave::select('employee_leaves.*')
                ->with(['employee:id,name,employee_code', 'approver:id,name'])
                ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
                ->when($request->filled('leave_type'), fn ($q) => $q->where('leave_type', $request->leave_type))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));

            return datatables($query)
                ->addColumn('employee_name', fn ($row) => $row->employee?->name ?? '-')
                ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
                ->addColumn('leave_type_badge', fn ($row) => LeaveType::fromValue($row->leave_type)?->badgeHtml()
                    ?? '<span class="badge bg-label-secondary">' . e($row->leave_type) . '</span>')
                ->addColumn('formatted_start', fn ($row) => $row->start_date?->format('Y-m-d') ?? '-')
                ->addColumn('formatted_end', fn ($row) => $row->end_date?->format('Y-m-d') ?? '-')
                ->addColumn('status_badge', fn ($row) => LeaveStatus::fromValue($row->status)?->badgeHtml()
                    ?? '<span class="badge bg-label-secondary">' . e($row->status) . '</span>')
                ->addColumn('approver_name', fn ($row) => $row->approver?->name ?? '-')
                ->addColumn('actions', fn ($row) => view('admin.hr.leaves.partials.actions', ['row' => $row])->render())
                ->rawColumns(['leave_type_badge', 'status_badge', 'actions'])
                ->make(true);
        }

        $employees  = Employee::active()->get(['id', 'name', 'employee_code']);
        $trashCount = EmployeeLeave::onlyTrashed()->count();

        return view('admin.hr.leaves.index', compact('employees', 'trashCount'));
    }

    public function balances()
    {
        $year = (int) request('year', date('Y'));

        // أي موظف نشط مالوش رصيد للسنة دي بيتعمله رصيد افتراضي
        $this->leaves->ensureBalances(
            Employee::active()->get(['id', 'annual_leave_entitlement']),
            $year
        );

        $balances = EmployeeLeaveBalance::whereHas('employee')
            ->with('employee:id,name,employee_code,branch_id')
            ->where('year', $year)
            ->get();

        return view('admin.hr.leaves.balances', compact('balances', 'year'));
    }

    public function updateBalance(UpdateLeaveBalanceRequest $request, EmployeeLeaveBalance $balance)
    {
        $this->leaves->updateBalance($balance, $request->validated());

        return redirect()->back()->with('success', __('leave.messages.balance_updated'));
    }

    public function store(StoreLeaveRequest $request)
    {
        try {
            $this->leaves->create($request->validated());
        } catch (InvalidStateException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.leaves.index')->with('success', __('leave.messages.created'));
    }

    public function approve(EmployeeLeave $leave)
    {
        return $this->attempt(fn () => $this->leaves->approve($leave), 'leave.messages.approved');
    }

    public function reject(RejectionRequest $request, EmployeeLeave $leave)
    {
        return $this->attempt(fn () => $this->leaves->reject($leave, $request->reason()), 'leave.messages.rejected');
    }

    public function destroy(EmployeeLeave $leave)
    {
        return $this->attempt(fn () => $this->leaves->delete($leave), 'leave.messages.deleted');
    }

    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = EmployeeLeave::onlyTrashed()->with(['employee:id,name,employee_code']);

            return datatables($query)
                ->addColumn('employee_name', fn ($row) => $row->employee?->name ?? '-')
                ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
                ->addColumn('leave_type_badge', fn ($row) => '<span class="badge bg-label-secondary">'
                    . e(LeaveType::fromValue($row->leave_type)?->label() ?? $row->leave_type) . '</span>')
                ->addColumn('formatted_start', fn ($row) => $row->start_date?->format('Y-m-d') ?? '-')
                ->addColumn('formatted_end', fn ($row) => $row->end_date?->format('Y-m-d') ?? '-')
                ->addColumn('deleted_at', fn ($row) => $row->deleted_at?->format('Y-m-d H:i') ?? '-')
                ->addColumn('action', fn () => '')
                ->rawColumns(['leave_type_badge', 'action'])
                ->make(true);
        }

        return view('admin.hr.leaves.trash');
    }

    public function restore(int $id)
    {
        return $this->attempt(fn () => $this->leaves->restore($id), 'global.restore');
    }

    public function forceDelete(int $id)
    {
        EmployeeLeave::onlyTrashed()->findOrFail($id)->forceDelete();

        return redirect()->back()->with('success', __('global.force_delete'));
    }
}
