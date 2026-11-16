<?php

namespace App\Http\Controllers\Admin\HR;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Concerns\RespondsToStateActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Permission\StorePermissionRequest;
use App\Http\Requests\Admin\RejectionRequest;
use App\Models\Employee;
use App\Models\EmployeePermission;
use App\Services\HR\ApprovalService;
use App\Services\HR\PayrollRules;
use Illuminate\Http\Request;

/**
 * أذونات الانصراف/الاستئذان. الاعتماد والرفض عبر ApprovalService.
 */
class EmployeePermissionController extends Controller
{
    use RespondsToStateActions;

    public function __construct(
        private ApprovalService $approvals,
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            // select صريح: yajra بيعمل join تلقائي للترتيب بأعمدة العلاقات،
            // و select * ساعتها بيخلي أعمدة الجدول المنضم تدهس id.
            $query = EmployeePermission::select('employee_permissions.*')
                ->with(['employee:id,name,employee_code', 'approver:id,name'])
                ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));

            return datatables($query)
                ->addColumn('employee_name', fn ($row) => $row->employee?->name ?? '-')
                ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
                ->addColumn('formatted_date', fn ($row) => $row->permission_date?->format('Y-m-d') ?? '-')
                ->addColumn('time_range', fn ($row) => ($row->start_time ?: '-') . ' - ' . ($row->end_time ?: '-'))
                ->addColumn('hours_count', fn ($row) => '<span class="badge bg-label-primary">' . e($row->hours_count) . ' ' . __('ساعة') . '</span>')
                ->addColumn('status_badge', fn ($row) => ApprovalStatus::badgeFor($row->status, 'permission.status_enums'))
                ->addColumn('approver_name', fn ($row) => $row->approver?->name ?? '-')
                ->addColumn('actions', fn ($row) => view('admin.hr.permissions.partials.actions', ['row' => $row])->render())
                ->rawColumns(['hours_count', 'status_badge', 'actions'])
                ->make(true);
        }

        $employees = Employee::active()->get(['id', 'name', 'employee_code']);

        $selectedEmployeeId = $request->get('employee_id');
        $selectedMonth      = (int) $request->get('month', date('n'));
        $selectedYear       = (int) $request->get('year', date('Y'));

        $selectedEmployee = $selectedEmployeeId ? Employee::find($selectedEmployeeId) : null;
        $employeeStats    = $selectedEmployee
            ? $this->monthlyStats($selectedEmployee, $selectedYear, $selectedMonth)
            : null;

        return view('admin.hr.permissions.index', compact(
            'employees',
            'selectedEmployee',
            'selectedEmployeeId',
            'selectedMonth',
            'selectedYear',
            'employeeStats'
        ));
    }

    public function store(StorePermissionRequest $request)
    {
        EmployeePermission::create(['status' => ApprovalStatus::Pending->value] + $request->permissionData());

        return redirect()->route('admin.permissions.index')->with('success', __('permission.messages.created'));
    }

    /**
     * من غير فحص الحالة، طلب مرفوض أو معتمد ممكن يتعمد تاني —
     * والساعات تتحسب مرتين في حد الاستئذان الشهري.
     */
    public function approve(EmployeePermission $permission)
    {
        return $this->attempt(
            fn () => $this->approvals->approve($permission, 'permission'),
            'permission.messages.approved'
        );
    }

    /** الإذن ممكن يترفض وهو معلّق أو بعد اعتماده (إلغاء). */
    public function reject(RejectionRequest $request, EmployeePermission $permission)
    {
        return $this->attempt(
            fn () => $this->approvals->reject($permission, $request->reason(), 'permission', allowApproved: true),
            'permission.messages.rejected'
        );
    }

    public function destroy(EmployeePermission $permission)
    {
        $permission->delete();

        return redirect()->back()->with('success', __('permission.messages.deleted'));
    }

    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = EmployeePermission::onlyTrashed()->with(['employee:id,name,employee_code']);

            return datatables($query)
                ->addColumn('employee_name', fn ($row) => $row->employee?->name ?? '-')
                ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
                ->addColumn('deleted_at', fn ($row) => $row->deleted_at?->format('Y-m-d H:i') ?? '-')
                ->make(true);
        }

        return view('admin.hr.permissions.trash');
    }

    public function restore(int $id)
    {
        EmployeePermission::onlyTrashed()->findOrFail($id)->restore();

        return redirect()->back()->with('success', __('global.restore'));
    }

    public function forceDelete(int $id)
    {
        EmployeePermission::onlyTrashed()->findOrFail($id)->forceDelete();

        return redirect()->back()->with('success', __('global.force_delete'));
    }

    /** ساعات الشهر المعتمدة مقابل الحد — بنفس قاعدة المسير. */
    private function monthlyStats(Employee $employee, int $year, int $month): array
    {
        $approvedHours = (float) EmployeePermission::where('employee_id', $employee->id)
            ->where('status', ApprovalStatus::Approved->value)
            ->inMonth($year, $month)
            ->sum('hours_count');

        $limit = PayrollRules::permissionLimit($employee);

        return [
            'approved_hours'  => $approvedHours,
            'monthly_limit'   => $limit,
            'remaining_hours' => max(0, $limit - $approvedHours),
            'is_exceeded'     => $approvedHours > $limit,
            'exceeded_hours'  => max(0, $approvedHours - $limit),
        ];
    }
}
