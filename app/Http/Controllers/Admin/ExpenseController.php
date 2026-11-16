<?php

namespace App\Http\Controllers\Admin;

use App\Rules\AccessibleBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Expense\StoreExpenseRequest;
use App\Http\Requests\Admin\Expense\UpdateExpenseRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PettyCashBalance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\Export\ExcelExportService;
use App\Services\Finance\PettyCashService;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses.
     */
    public function index(Request $request)
    {
        // الفترة كلها من نطاق التواريخ. الافتراضي: الشهر الحالي
        // كامل، فالشاشة بتفتح على نفس اللي كانت بتفتح عليه قبل
        // ما نشيل حقل الشهر/السنة.
        $dateFrom = $request->filled('date_from')
            ? $request->date_from
            : now()->startOfMonth()->toDateString();

        $dateTo = $request->filled('date_to')
            ? $request->date_to
            : now()->endOfMonth()->toDateString();

        // لو المستخدم عكس التواريخ بنصلّحها بدل نتيجة فاضية
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        // دورة العهدة شهرية، فبناخد شهرها من تاريخ البداية.
        $periodStart = \Carbon\Carbon::parse($dateFrom);
        $year  = (int) $periodStart->year;
        $month = (int) $periodStart->month;

        // بطاقات الرصيد بتخص شهر واحد. لو النطاق بيتخطى الشهر
        // بنخفيها لأن مقارنة مخصص شهر بمنصرف فترة أطول مضلّلة.
        $usingCustomRange = $dateFrom !== now()->startOfMonth()->toDateString()
            || $dateTo !== now()->endOfMonth()->toDateString();

        $spansMultipleMonths = $periodStart->format('Y-m')
            !== \Carbon\Carbon::parse($dateTo)->format('Y-m');
        $branchId = $request->filled('branch_id') ? $request->branch_id : null;

        // ملاحظة: ترحيل رصيد العهدة من الشهر السابق كان بيتنفذ هنا، يعني
        // كتابة في قاعدة البيانات مع كل زيارة GET وبعدد استعلامات بيزيد
        // مع كل فرع. اتنقل للأمر المجدول petty-cash:carry-over اللي بيشتغل
        // أول كل شهر، والشاشة دي بقت للقراءة بس.

        // Base query for expenses list
        // select صريح من جدول المصروفات: yajra بيعمل join تلقائي
        // للترتيب بأعمدة العلاقات، و select * ساعتها بيخلي أعمدة
        // الجدول المنضم تدهس أعمدة المصروف (id بالذات).
        $query = Expense::visibleTo()
            ->select('expenses.*')
            ->with(['category:id,name', 'branch:id,name,currency', 'employee:id,name,employee_code', 'creator:id,name']);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('expense_category_id')) {
            $query->where('expense_category_id', $request->expense_category_id);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $query->whereBetween('expense_date', [$dateFrom, $dateTo]);

        if ($request->ajax()) {
            return datatables($query)
                ->addColumn('category_name', fn($row) => $row->category?->name ?? '-')
                ->addColumn('branch_name', fn($row) => $row->branch?->name ?? __('expense.fields.all_branches'))
                ->addColumn('employee_name', fn($row) => $row->employee ? ($row->employee->name . ' (' . $row->employee->employee_code . ')') : '-')
                ->addColumn('formatted_amount', fn($row) => '<span dir="ltr">' . number_format($row->amount, 2) . '</span> <span class="badge bg-label-info">' . ($row->currency ?? $row->branch?->currency ?? 'EGP') . '</span>')
                ->addColumn('formatted_date', fn($row) => $row->expense_date?->format('Y-m-d'))
                ->addColumn('payment_method', function ($row) {
                    // القيمة بتتخزّن كمفتاح، والجدول كان بيعرضها خام.
                    // لو قيمة قديمة مش في القائمة بنعرضها زي ما هي.
                    $key = 'expense.enums.' . $row->payment_method;

                    return __($key) === $key ? $row->payment_method : __($key);
                })
                // العمود بقى محسوب، و yajra مابيبحثش في المحسوب
                // تلقائياً — فبنوجّهه للعمود الحقيقي.
                ->filterColumn('payment_method', function ($query, $keyword) {
                    $query->where('expenses.payment_method', $keyword);
                })
                ->addColumn('attachment_url', fn($row) => $row->attachment ? (str_starts_with($row->attachment, 'http') ? $row->attachment : Storage::url($row->attachment)) : null)
                ->addColumn('actions', function ($row) {
                    $html = '<div class="d-flex gap-1 justify-content-center">';
                    if (admin_can('expense_update')) {
                        $html .= '<a href="' . route('admin.expenses.edit', $row->id) . '" class="btn btn-sm btn-icon btn-label-primary waves-effect" title="' . __('global.edit') . '"><i class="fa-solid fa-pen-to-square"></i></a>';
                    }
                    if (admin_can('expense_delete')) {
                        $html .= '<form action="' . route('admin.expenses.destroy', $row->id) . '" method="POST" class="d-inline">';
                        $html .= csrf_field();
                        $html .= method_field('DELETE');
                        $html .= '<button type="button" class="btn btn-sm btn-icon btn-label-danger waves-effect btn-swal-confirm" title="' . __('global.delete') . '"
                            data-title="' . __('global.confirm_delete') . '"
                            data-text="' . __('global.confirm_delete') . '"
                            data-icon="warning"
                            data-btn-text="' . __('global.delete') . '"
                            data-btn-class="btn-danger"><i class="fa-solid fa-trash"></i></button>';
                        $html .= '</form>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->rawColumns(['formatted_amount', 'actions'])
                ->make(true);
        }

        if ($request->get('export') === 'excel') {
            $exportExpenses = $query->latest('expense_date')->get();
            $headers = [
                __('global.export.row_no'),
                __('expense.export.expense_date'),
                __('expense.export.invoice_number'),
                __('expense.export.category'),
                __('expense.export.branch'),
                __('expense.export.responsible'),
                __('expense.export.payment_method'),
                __('global.export.amount'),
                __('global.export.currency'),
                __('expense.export.description'),
                __('global.export.notes'),
            ];
            $rows = [];
            $i = 1;
            $sumByCurrency = [];
            foreach ($exportExpenses as $e) {
                $curr = $e->currency ?? $e->branch?->currency ?? 'EGP';
                $sumByCurrency[$curr] = ($sumByCurrency[$curr] ?? 0) + (float) $e->amount;
                $rows[] = [
                    $i++,
                    $e->expense_date?->format('Y-m-d') ?? '-',
                    $e->invoice_number ?? '-',
                    $e->category?->name ?? '-',
                    $e->branch?->name ?? __('expense.fields.all_branches'),
                    $e->employee ? ($e->employee->name . ' (' . $e->employee->employee_code . ')') : '-',
                    __('expense.enums.' . $e->payment_method) ?? $e->payment_method,
                    number_format($e->amount, 2),
                    $curr,
                    $e->description ?? '-',
                    $e->notes ?? '-',
                ];
            }

            $totals = [
                __('global.export.total'),
                '',
                '',
                '',
                '',
                '',
                count($exportExpenses) . ' ' . __('expense.export.records_suffix'),
                implode(' | ', array_map(fn($c, $a) => number_format($a, 2) . ' ' . $c, array_keys($sumByCurrency), array_values($sumByCurrency))),
                '',
                __('expense.export.exported_ok'),
                '',
            ];

            $branchObj = $request->filled('branch_id') ? Branch::find($request->branch_id) : null;
            $meta = [
                __('global.export.period') => $dateFrom . ' ' . __('global.export.to') . ' ' . $dateTo,
                __('global.export.branch')  => $branchObj ? $branchObj->name : __('expense.export.all_branches'),
            ];

            return ExcelExportService::download(__('expense.export.file_title'), $headers, $rows, $totals, 'expenses_' . date('Ymd_His'), $meta);
        }

        $expenses = $query->latest('expense_date')->paginate(15)->withQueryString();

        // Multi-currency Petty Cash Balance Analytics & Remaining Calculation
        $allocatedByCurrency = [];
        $selectedBranch = $request->filled('branch_id') ? Branch::find($request->branch_id) : null;
        $branchCurrency = $selectedBranch?->currency ?? 'EGP';

        if ($selectedBranch) {
            $custodyBalance = PettyCashBalance::where('year', $year)
                ->where('month', $month)
                ->where('branch_id', $selectedBranch->id)
                ->first();
            $allocatedBalance = $custodyBalance ? (float) $custodyBalance->total_balance : 0.00;
            $allocatedByCurrency[$branchCurrency] = $allocatedBalance;
        } else {
            // صفوف بدون فرع مالهاش مصروف يتخصم منها — دخولها في الجمع
            // كان بيضيف رصيد وهمي على "جميع الفروع".
            $allBalances = PettyCashBalance::with('branch:id,name,currency')
                ->whereNotNull('branch_id')
                ->where('year', $year)
                ->where('month', $month)
                ->get();
            $custodyBalance = $allBalances->first();
            $allocatedBalance = 0.00;
            foreach ($allBalances as $b) {
                $c = $b->branch?->currency ?? 'EGP';
                $allocatedByCurrency[$c] = ($allocatedByCurrency[$c] ?? 0.00) + (float) $b->total_balance;
                $allocatedBalance += (float) $b->total_balance;
            }
        }

        $cashExpensesQuery = Expense::visibleTo();

        if ($request->filled('payment_method')) {
            $cashExpensesQuery->where('payment_method', $request->payment_method);
        }

        if ($request->filled('expense_category_id')) {
            $cashExpensesQuery->where('expense_category_id', $request->expense_category_id);
        }

        $cashExpensesQuery->whereBetween('expense_date', [$dateFrom, $dateTo]);

        if ($request->filled('branch_id')) {
            $cashExpensesQuery->where('branch_id', $request->branch_id);
        }

        $cashExpensesTotal = (float) $cashExpensesQuery->sum('amount');

        // Multi-currency calculation: Group expenses by currency and sum each currency independently
        $expensesByCurrency = (clone $cashExpensesQuery)
            ->select('currency', DB::raw('SUM(amount) as total_curr'))
            ->groupBy('currency')
            ->pluck('total_curr', 'currency')
            ->toArray();

        // Calculate remaining balances independently for every currency
        $allCurrencies = array_unique(array_merge(array_keys($allocatedByCurrency), array_keys($expensesByCurrency)));
        $remainingByCurrency = [];
        foreach ($allCurrencies as $c) {
            $alloc = (float) ($allocatedByCurrency[$c] ?? 0.00);
            $spent = (float) ($expensesByCurrency[$c] ?? 0.00);
            $remainingByCurrency[$c] = round($alloc - $spent, 2);
        }

        $remainingBalance = $selectedBranch 
            ? ($remainingByCurrency[$branchCurrency] ?? 0.00) 
            : ($allocatedBalance - $cashExpensesTotal);

        $carryBreakdown = $this->carryBreakdown($year, $month, $selectedBranch);

        $spentPeriodLabel = __('expense.filters.period_label', [
            'from' => $dateFrom,
            'to'   => $dateTo,
        ]);

        // بطاقات العهدة بتتخفي بس لما النطاق يتخطى الشهر — ساعتها
        // مقارنة مخصص شهر واحد بمنصرف فترة أطول بتبقى مضلّلة.
        $usingCustomRange = $spansMultipleMonths;

        $categories = ExpenseCategory::where('is_active', true)->get(['id', 'name']);
        $branches   = Branch::accessible()->where('is_active', true)->get(['id', 'name', 'currency']);
        $employees  = Employee::visibleTo()->active()->get(['id', 'name', 'employee_code']);

        return view('admin.expenses.index', compact(
            'expenses',
            'allocatedBalance',
            'cashExpensesTotal',
            'allocatedByCurrency',
            'expensesByCurrency',
            'remainingByCurrency',
            'allCurrencies',
            'remainingBalance',
            'carryBreakdown',
            'custodyBalance',
            'year',
            'month',
            'dateFrom',
            'dateTo',
            'spentPeriodLabel',
            'categories',
            'branches',
            'employees',
            'branchCurrency',
            'selectedBranch',
            'usingCustomRange'
        ));
    }

    /**
     * Show the form for creating a new expense.
     */
    public function create()
    {
        $categories = ExpenseCategory::where('is_active', true)->get(['id', 'name']);
        $branches   = Branch::accessible()->where('is_active', true)->get(['id', 'name', 'currency']);
        $employees  = Employee::where('status', 'active')->get(['id', 'name', 'employee_code']);

        return view('admin.expenses.create', compact('categories', 'branches', 'employees'));
    }

    /**
     * Store a newly created expense in storage.
     */
    public function store(StoreExpenseRequest $request)
    {
        $data = $request->validated();

        // Backdate permission guard check
        $expenseDate = Carbon::parse($data['expense_date']);
        if ($expenseDate->format('Y-m') < now()->format('Y-m') && !admin_can('expense_backdate')) {
            return redirect()->back()->withInput()->with('error', __('expense.messages.cant_backdate'));
        }

        $data['created_by'] = auth('admin')->id();

        if (empty($data['currency'])) {
            $branch = Branch::find($data['branch_id'] ?? null);
            $data['currency'] = $branch?->currency ?? 'EGP';
        }

        Expense::create($data);

        return redirect()->route('admin.expenses.index')->with('success', __('expense.messages.created'));
    }

    /**
     * Show the form for editing the specified expense.
     */
    public function edit(Expense $expense)
    {
        $categories = ExpenseCategory::where('is_active', true)->get(['id', 'name']);
        $branches   = Branch::accessible()->where('is_active', true)->get(['id', 'name', 'currency']);
        $employees  = Employee::where('status', 'active')->get(['id', 'name', 'employee_code']);

        return view('admin.expenses.edit', compact('expense', 'categories', 'branches', 'employees'));
    }

    /**
     * Update the specified expense in storage.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        $data = $request->validated();

        // Backdate permission guard check
        $expenseDate = Carbon::parse($data['expense_date']);
        if ($expenseDate->format('Y-m') < now()->format('Y-m') && !admin_can('expense_backdate')) {
            return redirect()->back()->withInput()->with('error', __('expense.messages.cant_backdate'));
        }

        if (empty($data['currency'])) {
            $branch = Branch::find($data['branch_id'] ?? null);
            $data['currency'] = $branch?->currency ?? 'EGP';
        }

        $expense->update($data);

        return redirect()->route('admin.expenses.index')->with('success', __('expense.messages.updated'));
    }

    /**
     * Remove the specified expense from storage.
     */
    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->back()->with('success', __('expense.messages.deleted'));
    }

    /**
     * Get petty cash balance details for a specific branch, year, and month.
     */
    public function getCustodyBalance(Request $request)
    {
        $branchId = $request->get('branch_id');
        $year     = (int) $request->get('year', now()->year);
        $month    = (int) $request->get('month', now()->month);

        $branch = $branchId ? Branch::find($branchId) : null;
        $currency = $branch?->currency ?? 'EGP';

        $balance = PettyCashBalance::where('year', $year)
            ->where('month', $month)
            ->where('branch_id', $branchId)
            ->first();

        return response()->json([
            'success'             => true,
            'currency'            => $currency,
            'allocated_amount'    => $balance ? (float) $balance->allocated_amount : 0.00,
            'carried_over_amount' => $balance ? (float) $balance->carried_over_amount : 0.00,
            'total_balance'       => $balance ? (float) $balance->total_balance : 0.00,
            'notes'               => $balance?->notes ?? '',
        ]);
    }

    /**
     * تفصيل ترحيل العهدة لشهر واحد، لكل عملة.
     *
     * بيتحسب على كل مصروفات الشهر بكل طرق الدفع — مش على الفلاتر —
     * لأن ده بالظبط اللي PettyCashService بيرحّله. بطاقة "المتبقي"
     * بتتأثر بالفلاتر، فلو اعتمدنا عليها الشرح هيقول رقم والترحيل رقم تاني.
     *
     * @return array<string, array{allocated: float, carried: float, total: float, spent: float, remaining: float}>
     */
    private function carryBreakdown(int $year, int $month, ?Branch $branch): array
    {
        $balances = PettyCashBalance::with('branch:id,currency')
            ->whereNotNull('branch_id')
            ->where('year', $year)
            ->where('month', $month)
            ->when($branch, fn ($q) => $q->where('branch_id', $branch->id))
            ->get();

        $spent = Expense::visibleTo()
            ->inMonth($year, $month)
            ->when($branch, fn ($q) => $q->where('branch_id', $branch->id))
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        $rows = [];

        foreach ($balances as $b) {
            $c = $b->branch?->currency ?? 'EGP';
            $rows[$c]['allocated'] = ($rows[$c]['allocated'] ?? 0) + (float) $b->allocated_amount;
            $rows[$c]['carried']   = ($rows[$c]['carried'] ?? 0) + (float) $b->carried_over_amount;
        }

        foreach ($rows as $c => $r) {
            $total = $r['allocated'] + $r['carried'];
            $used  = (float) ($spent[$c] ?? 0);

            $rows[$c] += [
                'total'     => round($total, 2),
                'spent'     => round($used, 2),
                'remaining' => round($total - $used, 2),
            ];
        }

        return $rows;
    }

    /**
     * Update or set allocated petty cash balance for a month.
     */
    public function updateBalance(Request $request)
    {
        $request->validate([
            'branch_id'        => ['required', 'exists:branches,id', new AccessibleBranch],
            'year'             => ['required', 'integer', 'min:2020', 'max:2100'],
            'month'            => ['required', 'integer', 'min:1', 'max:12'],
            'allocated_amount' => ['required', 'numeric', 'min:0'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ]);

        $branch = Branch::findOrFail($request->branch_id);

        PettyCashBalance::updateOrCreate(
            [
                'branch_id' => $branch->id,
                'year'      => $request->year,
                'month'     => $request->month,
            ],
            [
                'allocated_amount' => $request->allocated_amount,
                'notes'            => $request->notes,
                'created_by'       => auth('admin')->id(),
            ]
        );

        // مخصص شهر بيغيّر المرحّل لكل الشهور اللي بعده
        app(PettyCashService::class)->recalculateBranch($branch->id, now()->year, now()->month);

        return redirect()->back()->with('success', __('expense.messages.balance_updated', ['month' => $request->month . '/' . $request->year]) . " ({$branch->name} - {$branch->currency})");
    }

    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = Expense::onlyTrashed()->with(['category:id,name', 'branch:id,name', 'creator:id,name']);
            return datatables($query)
                ->addColumn('category_name', fn($row) => $row->category?->name ?? '-')
                ->addColumn('branch_name', fn($row) => $row->branch?->name ?? '-')
                ->addColumn('formatted_amount', fn($row) => '<span dir="ltr">' . number_format($row->amount, 2) . '</span> ' . ($row->currency ?? ''))
                ->addColumn('deleted_at', fn($row) => $row->deleted_at ? $row->deleted_at->format('Y-m-d H:i') : '-')
                ->addColumn('payment_method', function ($row) {
                    // القيمة بتتخزّن كمفتاح، والجدول كان بيعرضها خام.
                    // لو قيمة قديمة مش في القائمة بنعرضها زي ما هي.
                    $key = 'expense.enums.' . $row->payment_method;

                    return __($key) === $key ? $row->payment_method : __($key);
                })
                ->rawColumns(['formatted_amount'])
                ->make(true);
        }
        return view('admin.expenses.trash');
    }

    public function restore(int $id)
    {
        $exp = Expense::onlyTrashed()->findOrFail($id);
        $exp->restore();
        return redirect()->back()->with('success', __('global.restore'));
    }

    public function forceDelete(int $id)
    {
        $exp = Expense::onlyTrashed()->findOrFail($id);
        $exp->forceDelete();
        return redirect()->back()->with('success', __('global.force_delete'));
    }
}
