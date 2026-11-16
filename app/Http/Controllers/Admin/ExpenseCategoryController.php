<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExpenseCategory\StoreExpenseCategoryRequest;
use App\Http\Requests\Admin\ExpenseCategory\UpdateExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    /**
     * Display a listing of expense categories.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $categories = ExpenseCategory::withCount('expenses');
            return datatables($categories)
                ->addColumn('is_active_badge', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-label-success">' . __('global.active') . '</span>'
                        : '<span class="badge bg-label-secondary">' . __('global.inactive') . '</span>';
                })
                ->addColumn('actions', fn ($row) => view('admin.expense_categories.partials.actions', ['row' => $row])->render())
                ->rawColumns(['is_active_badge', 'actions'])
                ->make(true);
        }

        $trashCount = ExpenseCategory::onlyTrashed()->count();
        return view('admin.expense_categories.index', compact('trashCount'));
    }

    /**
     * Store a newly created expense category in storage.
     */
    public function store(StoreExpenseCategoryRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        ExpenseCategory::create($data);

        return redirect()->back()->with('success', __('expense_category.messages.created'));
    }

    /**
     * Display the specified expense category details.
     */
    public function show(ExpenseCategory $expenseCategory)
    {
        return response()->json(['status' => true, 'data' => $expenseCategory]);
    }

    /**
     * Update the specified expense category in storage.
     */
    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory)
    {
        $data = $request->validated();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        $expenseCategory->update($data);

        return redirect()->back()->with('success', __('expense_category.messages.updated'));
    }

    /**
     * Remove the specified expense category from storage.
     */
    public function destroy(ExpenseCategory $expenseCategory)
    {
        if ($expenseCategory->expenses()->count() > 0) {
            return redirect()->back()->with('error', __('expense_category.messages.cannot_delete_has_expenses'));
        }

        $expenseCategory->delete();

        return redirect()->back()->with('success', __('expense_category.messages.deleted'));
    }
    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = ExpenseCategory::onlyTrashed();
            return datatables($query)
                ->addColumn('deleted_at', fn($row) => $row->deleted_at ? $row->deleted_at->format('Y-m-d H:i') : '-')
                ->make(true);
        }
        return view('admin.expense_categories.trash');
    }

    public function restore(int $id)
    {
        $cat = ExpenseCategory::onlyTrashed()->findOrFail($id);
        $cat->restore();
        return redirect()->back()->with('success', __('global.restore'));
    }

    public function forceDelete(int $id)
    {
        $cat = ExpenseCategory::onlyTrashed()->findOrFail($id);
        $cat->forceDelete();
        return redirect()->back()->with('success', __('global.force_delete'));
    }
}
