<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmployeeRequest;
use App\Models\Employee;
use App\Services\HR\BranchService;
use App\Services\HR\EmployeeService;
use App\Services\HR\JobTitleService;
use Exception;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService,
        protected BranchService $branchService,
        protected JobTitleService $jobTitleService
    ) {}

    /**
     * قائمة الموظفين مع الـ DataTables
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->employeeService->query();

            // فلاتر إضافية
            if ($request->filled('branch_id')) {
                $query->where('employees.branch_id', $request->branch_id);
            }
            if ($request->filled('job_title_id')) {
                $query->where('employees.job_title_id', $request->job_title_id);
            }
            if ($request->filled('status')) {
                $query->where('employees.status', $request->status);
            }

            return datatables($query)
                ->filterColumn('branch.name', function ($query, $keyword) {
                    $query->where('employees.branch_id', $keyword);
                })
                ->filterColumn('job_title.name', function ($query, $keyword) {
                    $query->where('employees.job_title_id', $keyword);
                })
                ->filterColumn('status', function ($query, $keyword) {
                    $query->where('employees.status', $keyword);
                })
                ->filterColumn('hire_date', function ($query, $keyword) {
                    $query->whereDate('employees.hire_date', $keyword);
                })
                ->addColumn('code_badge', function ($row) {
                    $profileUrl = route('admin.employees.show', $row->id);
                    return '<a href="' . $profileUrl . '" class="badge bg-label-primary text-decoration-none fw-bold"><i class="fa-solid fa-id-badge me-1"></i>' . e($row->employee_code) . '</a>';
                })
                ->addColumn('avatar_img', function ($row) {
                    $profileUrl = route('admin.employees.show', $row->id);
                    return '<div class="d-flex align-items-center">
                        <a href="' . $profileUrl . '" class="avatar avatar-sm me-2 text-decoration-none">
                            <img src="' . $row->avatar_url . '" alt="' . e($row->name) . '" class="rounded-circle">
                        </a>
                        <div>
                            <a href="' . $profileUrl . '" class="fw-bold d-block text-heading text-truncate text-decoration-none text-primary">' . e($row->name) . '</a>
                            <small class="text-muted">' . e($row->email) . '</small>
                        </div>
                    </div>';
                })
                ->addColumn('status_badge', function ($row) {
                    $class = match ($row->status) {
                        'active'     => 'bg-label-success',
                        'on_leave'   => 'bg-label-warning',
                        'terminated' => 'bg-label-danger',
                        default      => 'bg-label-secondary',
                    };
                    return '<span class="badge ' . $class . '">' . __('employee.statuses.' . $row->status) . '</span>';
                })
                ->addColumn('salary_display', function ($row) {
                    if (!admin_can('employee_salary_view')) {
                        return '<span class="text-muted"><i class="fa-solid fa-lock me-1"></i>***</span>';
                    }
                    if (!$row->currentSalary) {
                        return '<span class="text-muted">-</span>';
                    }
                    $salaryVal = number_format($row->currentSalary->total_salary, 2) . ' ' . $row->currentSalary->currency;
                    return '<span class="salary-container">
                        <span class="salary-amount d-none fw-bold text-success">' . $salaryVal . '</span>
                        <span class="salary-masked text-muted"><i class="fa-solid fa-eye-slash me-1"></i>••••••</span>
                    </span>';
                })
                ->editColumn('phone', function ($row) {
                    return '<span dir="ltr" class="d-inline-block text-start" style="unicode-bidi: isolate;">' . e($row->phone) . '</span>';
                })
                ->editColumn('hire_date', function ($row) {
                    return $row->hire_date ? $row->hire_date->format('Y-m-d') : '-';
                })
                ->rawColumns(['code_badge', 'avatar_img', 'status_badge', 'salary_display', 'phone'])
                ->make(true);
        }

        $branches   = $this->branchService->allActive();
        $jobTitles  = $this->jobTitleService->allActive();
        $trashCount = $this->employeeService->trashCount();

        return view('admin.hr.employees.index', compact('branches', 'jobTitles', 'trashCount'));
    }

    /**
     * سلة محذوفات الموظفين
     */
    public function trash(Request $request)
    {
        if ($request->ajax()) {
            return datatables($this->employeeService->trashQuery())->make(true);
        }

        return view('admin.hr.employees.trash');
    }

    /**
     * شاشة إضافة موظف
     */
    public function create()
    {
        $branches  = $this->branchService->allActive();
        $jobTitles = $this->jobTitleService->allActive();
        $managers  = $this->employeeService->managersList();
        $roles     = \App\Models\Role::select('id', 'name')->get();
        $nextCode  = $this->employeeService->getNextEmployeeCode();

        return view('admin.hr.employees.create', compact('branches', 'jobTitles', 'managers', 'roles', 'nextCode'));
    }

    /**
     * حفظ موظف جديد
     */
    public function store(EmployeeRequest $request)
    {
        $employee = $this->employeeService->create($request->validated());

        return redirect()->route('admin.employees.show', $employee->id)
            ->with('success', __('employee.messages.created'));
    }

    /**
     * الملف الشخصي الشامل للموظف (Profile Tabbed View)
     */
    public function show(Employee $employee)
    {
        $employee = $this->employeeService->getProfileData($employee);

        return view('admin.hr.employees.show', compact('employee'));
    }

    /**
     * شاشة تعديل بيانات الموظف
     */
    public function edit(Employee $employee)
    {
        $employee->load(['allowances', 'admin.role', 'documents']);
        $branches  = $this->branchService->allActive();
        $jobTitles = $this->jobTitleService->allActive();
        $managers  = $this->employeeService->managersList($employee->id);
        $roles     = \App\Models\Role::select('id', 'name')->get();

        return view('admin.hr.employees.edit', compact('employee', 'branches', 'jobTitles', 'managers', 'roles'));
    }

    /**
     * تحديث بيانات الموظف
     */
    public function update(EmployeeRequest $request, Employee $employee)
    {
        $this->employeeService->update($employee, $request->validated());

        return redirect()->route('admin.employees.show', $employee->id)
            ->with('success', __('employee.messages.updated'));
    }

    /**
     * نقل ملف الموظف لسلة المحذوفات
     */
    public function destroy(Employee $employee)
    {
        try {
            $this->employeeService->delete($employee);
            return redirect()->route('admin.employees')->with('success', __('employee.messages.deleted'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * استرجاع ملف الموظف من السلة
     */
    public function restore(int $id)
    {
        $this->employeeService->restore($id);

        return redirect()->back()->with('success', __('employee.messages.restored'));
    }

    /**
     * الحذف النهائي لملف الموظف
     */
    public function forceDelete(int $id)
    {
        try {
            $this->employeeService->forceDelete($id);
            return redirect()->back()->with('success', __('employee.messages.force_deleted'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
