<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BranchRequest;
use App\Models\Branch;
use App\Services\HR\BranchService;
use Exception;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct(
        protected BranchService $branchService
    ) {}

    /**
     * قائمة الفروع
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datatables($this->branchService->query())
                ->addColumn('status_badge', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-label-success">' . __('branch.fields.active') . '</span>'
                        : '<span class="badge bg-label-danger">' . __('branch.fields.inactive') . '</span>';
                })
                ->rawColumns(['status_badge'])
                ->make(true);
        }

        $trashCount = $this->branchService->trashCount();

        return view('admin.hr.branches.index', compact('trashCount'));
    }

    /**
     * سلة محذوفات الفروع
     */
    public function trash(Request $request)
    {
        if ($request->ajax()) {
            return datatables($this->branchService->trashQuery())->make(true);
        }

        return view('admin.hr.branches.trash');
    }

    /**
     * شاشة إضافة فرع
     */
    public function create()
    {
        return view('admin.hr.branches.create');
    }

    /**
     * حفظ فرع جديد
     */
    public function store(BranchRequest $request)
    {
        $this->branchService->create($request->validated());

        return redirect()->route('admin.branches')->with('success', __('branch.messages.created'));
    }

    /**
     * شاشة تعديل الفرع
     */
    public function edit(Branch $branch)
    {
        return view('admin.hr.branches.edit', compact('branch'));
    }

    /**
     * تحديث الفرع
     */
    public function update(BranchRequest $request, Branch $branch)
    {
        $this->branchService->update($branch, $request->validated());

        return redirect()->route('admin.branches')->with('success', __('branch.messages.updated'));
    }

    /**
     * نقل الفرع لسلة المحذوفات
     */
    public function destroy(Branch $branch)
    {
        try {
            $this->branchService->delete($branch);
            return redirect()->route('admin.branches')->with('success', __('branch.messages.deleted'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * استرجاع الفرع من سلة المحذوفات
     */
    public function restore(int $id)
    {
        $this->branchService->restore($id);

        return redirect()->back()->with('success', __('branch.messages.restored'));
    }

    /**
     * الحذف النهائي للفرع
     */
    public function forceDelete(int $id)
    {
        try {
            $this->branchService->forceDelete($id);
            return redirect()->back()->with('success', __('branch.messages.force_deleted'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
