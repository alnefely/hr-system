<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeePermission;
use Illuminate\Http\Request;

class EmployeePermissionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // select صريح: yajra بيعمل join تلقائي للترتيب بأعمدة
            // العلاقات، و select * ساعتها بيخلي أعمدة الجدول المنضم
            // تدهس أعمدة الجدول الأصلي (id بالذات).
            $query = EmployeePermission::select('employee_permissions.*')
                ->with(['employee:id,name,employee_code', 'approver:id,name']);

            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            return datatables($query)
                ->addColumn('employee_name', fn($row) => $row->employee?->name ?? '-')
                ->addColumn('employee_code', fn($row) => $row->employee?->employee_code ?? '-')
                ->addColumn('formatted_date', fn($row) => $row->permission_date ? $row->permission_date->format('Y-m-d') : '-')
                ->addColumn('time_range', fn($row) => ($row->start_time ?: '-') . ' - ' . ($row->end_time ?: '-'))
                ->addColumn('hours_count', fn($row) => '<span class="badge bg-label-primary">' . $row->hours_count . ' ' . __('ساعة') . '</span>')
                ->addColumn('status_badge', function ($row) {
                    $badges = [
                        'pending'  => 'bg-label-warning',
                        'approved' => 'bg-label-success',
                        'rejected' => 'bg-label-danger',
                    ];
                    $badge = $badges[$row->status] ?? 'bg-label-secondary';
                    $lbl = __('permission.status_enums.' . $row->status) ?? $row->status;
                    return '<span class="badge ' . $badge . '">' . $lbl . '</span>';
                })
                ->addColumn('approver_name', fn($row) => $row->approver?->name ?? '-')
                ->addColumn('actions', function ($row) {
                    $html = '<div class="d-inline-flex gap-1">';
                    if ($row->status === 'pending') {
                        if (admin_can('permission_approve')) {
                            $html .= '<form action="' . route('admin.permissions.approve', $row->id) . '" method="POST" class="d-inline">
                                ' . csrf_field() . '
                                <button type="button" class="btn btn-sm btn-icon btn-label-success btn-swal-confirm" title="' . __('permission.actions.approve') . '" data-title="اعتماد طلب الاستئذان" data-text="هل أنت متأكد من اعتماد طلب الاستئذان لهذا الموظف؟" data-icon="question" data-btn-text="نعم، اعتمد" data-btn-class="btn-success">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>';
                        }
                        if (admin_can('permission_reject')) {
                            $html .= '<button type="button" class="btn btn-sm btn-icon btn-label-danger reject-btn" data-id="' . $row->id . '" title="' . __('permission.actions.reject') . '">
                                <i class="fa-solid fa-xmark"></i>
                            </button>';
                        }
                    }
                    if (admin_can('permission_delete')) {
                        $html .= '<form action="' . route('admin.permissions.destroy', $row->id) . '" method="POST" class="d-inline">
                            ' . csrf_field() . method_field('DELETE') . '
                            <button type="button" class="btn btn-sm btn-icon btn-label-secondary btn-swal-confirm" title="' . __('global.delete') . '" data-title="تأكيد الحذف" data-text="هل أنت متأكد من حذف هذا السجل؟" data-icon="warning" data-btn-text="نعم، احذف" data-btn-class="btn-danger">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->rawColumns(['hours_count', 'status_badge', 'actions'])
                ->make(true);
        }

        $employees = Employee::active()->get(['id', 'name', 'employee_code']);

        $selectedEmployeeId = $request->get('employee_id');
        $selectedMonth      = (int) $request->get('month', date('n'));
        $selectedYear       = (int) $request->get('year', date('Y'));

        $employeeStats    = null;
        $selectedEmployee = null;

        if ($selectedEmployeeId) {
            $selectedEmployee = Employee::find($selectedEmployeeId);
            if ($selectedEmployee) {
                $approvedHours = (float) EmployeePermission::where('employee_id', $selectedEmployeeId)
                    ->where('status', 'approved')
                    ->inMonth($selectedYear, $selectedMonth)
                    ->sum('hours_count');

                $monthlyLimit   = (float) ($selectedEmployee->monthly_permission_hours_limit ?? 4.00);
                $remainingHours = max(0, $monthlyLimit - $approvedHours);
                $isExceeded     = $approvedHours > $monthlyLimit;
                $exceededHours  = max(0, $approvedHours - $monthlyLimit);

                $employeeStats = [
                    'approved_hours' => $approvedHours,
                    'monthly_limit'  => $monthlyLimit,
                    'remaining_hours'=> $remainingHours,
                    'is_exceeded'    => $isExceeded,
                    'exceeded_hours' => $exceededHours,
                ];
            }
        }

        return view('admin.hr.permissions.index', compact(
            'employees',
            'selectedEmployee',
            'selectedEmployeeId',
            'selectedMonth',
            'selectedYear',
            'employeeStats'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id'     => ['required', 'exists:employees,id'],
            'permission_date' => ['required', 'date'],
            'start_time'      => ['required', 'date_format:H:i'],
            'end_time'        => ['required', 'date_format:H:i', 'after:start_time'],
            'hours_count'     => ['required', 'numeric', 'min:0.1', 'max:12'],
            'reason'          => ['nullable', 'string', 'max:1000'],
        ]);

        // الساعات بتتحسب من الوقت مش من إدخال المستخدم: كان ممكن يسجّل
        // إذن من 9 لـ 10 ويكتب 8 ساعات، والمسير يخصم على أساسها.
        $start = \Carbon\Carbon::createFromFormat('H:i', $data['start_time']);
        $end   = \Carbon\Carbon::createFromFormat('H:i', $data['end_time']);
        $data['hours_count'] = round($start->floatDiffInHours($end), 2);

        $data['status'] = 'pending';
        EmployeePermission::create($data);

        return redirect()->route('admin.permissions.index')->with('success', __('permission.messages.created'));
    }

    public function approve(Request $request, EmployeePermission $permission)
    {
        // من غير الفحص ده، طلب مرفوض أو معتمد ممكن يتعمد تاني —
        // والساعات تتحسب مرتين في حد الاستئذان الشهري.
        if ($permission->status !== 'pending') {
            return redirect()->back()->with('error', __('permission.messages.not_pending'));
        }

        $permission->update([
            'status'      => 'approved',
            'approved_by' => admin()?->id,
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', __('permission.messages.approved'));
    }

    public function reject(Request $request, EmployeePermission $permission)
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        if ($permission->status === 'rejected') {
            return redirect()->back()->with('error', __('permission.messages.not_pending'));
        }

        $permission->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_by'      => admin()?->id,
            'approved_at'      => now(),
        ]);

        return redirect()->back()->with('success', __('permission.messages.rejected'));
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
                ->addColumn('employee_name', fn($row) => $row->employee?->name ?? '-')
                ->addColumn('employee_code', fn($row) => $row->employee?->employee_code ?? '-')
                ->addColumn('deleted_at', fn($row) => $row->deleted_at ? $row->deleted_at->format('Y-m-d H:i') : '-')
                ->make(true);
        }
        return view('admin.hr.permissions.trash');
    }

    public function restore(int $id)
    {
        $perm = EmployeePermission::onlyTrashed()->findOrFail($id);
        $perm->restore();
        return redirect()->back()->with('success', __('global.restore'));
    }

    public function forceDelete(int $id)
    {
        $perm = EmployeePermission::onlyTrashed()->findOrFail($id);
        $perm->forceDelete();
        return redirect()->back()->with('success', __('global.force_delete'));
    }
}
