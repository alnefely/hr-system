<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\JobTitleRequest;
use App\Models\JobTitle;
use App\Services\HR\JobTitleService;
use Exception;
use Illuminate\Http\Request;

class JobTitleController extends Controller
{
    public function __construct(
        protected JobTitleService $jobTitleService
    ) {}

    /**
     * قائمة المسميات الوظيفية
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datatables($this->jobTitleService->query())
                ->editColumn('description', function ($row) {
                    if (!$row->description) return '-';
                    $short = \Illuminate\Support\Str::limit($row->description, 45);
                    return '<span title="' . e($row->description) . '" data-bs-toggle="tooltip" data-bs-placement="top" class="cursor-pointer">' . e($short) . '</span>';
                })
                ->addColumn('status_badge', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-label-success">' . __('job_title.fields.active') . '</span>'
                        : '<span class="badge bg-label-danger">' . __('job_title.fields.inactive') . '</span>';
                })
                ->filterColumn('status_badge', function ($query, $keyword) {
                    if ($keyword === '1' || $keyword === '0') {
                        $query->where('is_active', $keyword);
                    }
                })
                ->rawColumns(['status_badge', 'description'])
                ->make(true);
        }

        $trashCount = $this->jobTitleService->trashCount();

        return view('admin.hr.job_titles.index', compact('trashCount'));
    }

    /**
     * سلة محذوفات المسميات الوظيفية
     */
    public function trash(Request $request)
    {
        if ($request->ajax()) {
            return datatables($this->jobTitleService->trashQuery())->make(true);
        }

        return view('admin.hr.job_titles.trash');
    }

    /**
     * شاشة إضافة مسمى وظيفي
     */
    public function create()
    {
        return view('admin.hr.job_titles.create');
    }

    /**
     * حفظ مسمى وظيفي جديد
     */
    public function store(JobTitleRequest $request)
    {
        $this->jobTitleService->create($request->validated());

        return redirect()->route('admin.job_titles')->with('success', __('job_title.messages.created'));
    }

    /**
     * شاشة تعديل مسمى وظيفي
     */
    public function edit(JobTitle $jobTitle)
    {
        return view('admin.hr.job_titles.edit', compact('jobTitle'));
    }

    /**
     * تحديث مسمى وظيفي
     */
    public function update(JobTitleRequest $request, JobTitle $jobTitle)
    {
        $this->jobTitleService->update($jobTitle, $request->validated());

        return redirect()->route('admin.job_titles')->with('success', __('job_title.messages.updated'));
    }

    /**
     * نقل المسمى الوظيفي لسلة المحذوفات
     */
    public function destroy(JobTitle $jobTitle)
    {
        try {
            $this->jobTitleService->delete($jobTitle);
            return redirect()->route('admin.job_titles')->with('success', __('job_title.messages.deleted'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * استرجاع مسمى وظيفي من السلة
     */
    public function restore(int $id)
    {
        $this->jobTitleService->restore($id);

        return redirect()->back()->with('success', __('job_title.messages.restored'));
    }

    /**
     * الحذف النهائي للمسمى الوظيفي
     */
    public function forceDelete(int $id)
    {
        try {
            $this->jobTitleService->forceDelete($id);
            return redirect()->back()->with('success', __('job_title.messages.force_deleted'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
