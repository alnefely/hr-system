<?php

namespace App\Http\Controllers\Admin\HR;

use App\Enums\PayrollStatus;
use App\Exceptions\InvalidStateException;
use App\Http\Controllers\Concerns\RespondsToStateActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Payroll\PayPayrollItemsRequest;
use App\Http\Requests\Admin\Payroll\StorePayrollRequest;
use App\Http\Requests\Admin\Payroll\UnapprovePayrollRequest;
use App\Http\Requests\Admin\Payroll\UpdatePayrollItemDetailsRequest;
use App\Http\Requests\Admin\Payroll\UpdatePayrollItemsRequest;
use App\Http\Requests\Admin\Payroll\RejectPayrollRequest;
use App\Models\Branch;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Services\HR\PayrollGenerator;
use App\Services\HR\PayrollWorkflow;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * شاشات مسير الرواتب.
 *
 * الكنترولر بيستقبل الطلب ويرجّع الرد بس — القواعد كلها في:
 *  - PayrollGenerator: إصدار المسير وحساب البنود.
 *  - PayrollWorkflow: التعديل والاعتماد والصرف وتسوية السلف.
 */
class PayrollController extends Controller
{
    use RespondsToStateActions;

    public function __construct(
        private PayrollWorkflow $workflow,
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->table($request);
        }

        $branches = Branch::accessible()->select('id', 'name')->get();
        $stats = [
            'total_paid'     => Payroll::where('status', PayrollStatus::Paid->value)->sum('total_net_salary'),
            'draft_count'    => Payroll::where('status', PayrollStatus::Draft->value)->count(),
            'approved_count' => Payroll::where('status', PayrollStatus::Approved->value)->count(),
        ];

        return view('admin.hr.payrolls.index', compact('branches', 'stats'));
    }

    public function create()
    {
        $branches = Branch::accessible()->select('id', 'name')->get();
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');

        return view('admin.hr.payrolls.create', compact('branches', 'currentYear', 'currentMonth'));
    }

    public function store(StorePayrollRequest $request, PayrollGenerator $generator)
    {
        $year     = (int) $request->validated('year');
        $month    = (int) $request->validated('month');
        $branchId = $request->validated('branch_id');
        $notes    = $request->validated('notes');

        try {
            // من غير فرع محدد بنعمل كشف مستقل لكل فرع بدل كشف واحد
            // بيجمّع كل الموظفين — الكشف المجمّع مابينفعش للفلترة
            // ولا للتقارير الفرعية، وكل فرع ليه عملته وخزينته.
            if ($branchId === null) {
                return $this->storeForAllBranches($generator, $year, $month, $notes);
            }

            if ($generator->exists($year, $month, $branchId)) {
                return redirect()->back()->withInput()->with('error', __('payroll.messages.already_exists'));
            }

            $payroll = $generator->generate($year, $month, (int) $branchId, $notes);
            $this->workflow->notifyAwaitingReview($payroll);
        } catch (QueryException $e) {
            // الـ unique index على (year, month, branch_id) هو خط الدفاع الأخير
            // لو اتضغط زر الإصدار مرتين في نفس اللحظة.
            if ($e->getCode() === '23000') {
                return redirect()->back()->withInput()->with('error', __('payroll.messages.already_exists'));
            }

            throw $e;
        }

        return redirect()->route('admin.payrolls.index')->with('success', __('payroll.messages.created'));
    }

    public function show(Payroll $payroll)
    {
        $payroll->load([
            'branch:id,name,currency',
            'creator:id,name',
            'approver:id,name',
            'rejector:id,name',
            'unapprover:id,name',
            'payer:id,name',
            'items.employee:id,employee_code,name,phone,bank_name,bank_account_number,job_title_id,branch_id',
            'items.employee.jobTitle:id,name',
            'items.employee.branch:id,name,currency',
            'items.payer:id,name',
        ]);

        return view('admin.hr.payrolls.show', compact('payroll'));
    }

    public function updateItems(UpdatePayrollItemsRequest $request, Payroll $payroll)
    {
        return $this->attempt(
            fn () => $this->workflow->updateItems($payroll, $request->validated('items')),
            'payroll.messages.updated'
        );
    }

    public function updateItemDetails(UpdatePayrollItemDetailsRequest $request, PayrollItem $item)
    {
        return $this->attempt(
            fn () => $this->workflow->updateItemDetails(
                $item,
                $request->validated('allowance_details') ?? [],
                $request->validated('deduction_details') ?? [],
                $request->validated('payment_method'),
                $request->validated('notes'),
            ),
            'payroll.messages.updated'
        );
    }

    public function approve(Payroll $payroll)
    {
        return $this->attempt(fn () => $this->workflow->approve($payroll), 'payroll.messages.approved');
    }

    public function reject(RejectPayrollRequest $request, Payroll $payroll)
    {
        return $this->attempt(fn () => $this->workflow->reject($payroll, $request->reason()), 'payroll.messages.rejected');
    }

    public function resubmit(Payroll $payroll)
    {
        return $this->attempt(fn () => $this->workflow->resubmit($payroll), 'payroll.messages.resubmitted');
    }

    public function unapprove(UnapprovePayrollRequest $request, Payroll $payroll)
    {
        return $this->attempt(fn () => $this->workflow->unapprove($payroll, $request->reason()), 'payroll.messages.unapproved');
    }

    public function payItem(PayPayrollItemsRequest $request, PayrollItem $item)
    {
        return $this->attempt(
            fn () => $this->workflow->payItem($item, $request->validated('payment_method')),
            'payroll.messages.paid_single'
        );
    }

    public function paySelected(PayPayrollItemsRequest $request, Payroll $payroll)
    {
        return $this->attempt(
            fn () => $this->workflow->paySelected($payroll, $request->validated('item_ids'), $request->validated('payment_method')),
            'payroll.messages.paid_selected'
        );
    }

    public function pay(Payroll $payroll)
    {
        return $this->attempt(fn () => $this->workflow->payAll($payroll), 'payroll.messages.paid');
    }

    public function destroy(Payroll $payroll)
    {
        try {
            $this->workflow->delete($payroll);
        } catch (InvalidStateException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.payrolls.index')->with('success', __('payroll.messages.deleted'));
    }

    public function payslip(PayrollItem $item)
    {
        $item->load(['payroll.branch', 'employee.jobTitle', 'employee.branch']);

        return view('admin.hr.payrolls.payslip', compact('item'));
    }

    public function bulkPayslips(Request $request, Payroll $payroll)
    {
        $query = $payroll->items()->with(['payroll.branch', 'employee.jobTitle', 'employee.branch']);

        if ($request->filled('item_ids')) {
            $ids = is_array($request->item_ids) ? $request->item_ids : explode(',', $request->item_ids);
            $query->whereIn('id', array_filter($ids));
        }

        $items = $query->get();

        return view('admin.hr.payrolls.payslips_bulk', compact('payroll', 'items'));
    }

    // ========================================================
    //  الداخلي
    // ========================================================

    private function storeForAllBranches(PayrollGenerator $generator, int $year, int $month, ?string $notes): RedirectResponse
    {
        $result = $generator->generateForAllBranches($year, $month, $notes);

        foreach ($result['created'] as $payroll) {
            $this->workflow->notifyAwaitingReview($payroll);
        }

        if (! $result['created']) {
            return redirect()->back()->withInput()->with(
                'error',
                $result['skipped']
                    ? __('payroll.messages.all_branches_exist')
                    : __('payroll.messages.no_branches_to_generate')
            );
        }

        $message = __('payroll.messages.created_for_branches', ['count' => count($result['created'])]);

        if ($result['skipped']) {
            $message .= ' — ' . __('payroll.messages.skipped_branches', [
                'branches' => implode('، ', $result['skipped']),
            ]);
        }

        return redirect()->route('admin.payrolls.index')->with('success', $message);
    }

    private function table(Request $request)
    {
        // select صريح: yajra بيعمل join تلقائي للترتيب بأعمدة العلاقات،
        // و select * ساعتها بيخلي أعمدة الجدول المنضم تدهس id.
        $query = Payroll::select('payrolls.*')
            ->with(['branch:id,name,currency', 'creator:id,name', 'approver:id,name'])
            ->when($request->filled('year'), fn ($q) => $q->where('year', $request->year))
            ->when($request->filled('month'), fn ($q) => $q->where('month', $request->month))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id));

        // نطاق تواريخ على فترة المسير نفسها (سنة/شهر) مش على تاريخ الإنشاء
        if ($request->filled('date_from')) {
            $from = Carbon::parse($request->date_from);
            $query->whereRaw('(payrolls.year * 100 + payrolls.month) >= ?', [$from->year * 100 + $from->month]);
        }

        if ($request->filled('date_to')) {
            $to = Carbon::parse($request->date_to);
            $query->whereRaw('(payrolls.year * 100 + payrolls.month) <= ?', [$to->year * 100 + $to->month]);
        }

        return datatables($query)
            ->addColumn('title_link', fn ($row) => '<a href="' . route('admin.payrolls.show', $row->id) . '" class="fw-bold text-primary">' . e($row->title) . '</a>')
            ->addColumn('period', fn ($row) => sprintf('%02d / %04d', $row->month, $row->year))
            ->addColumn('branch_name', fn ($row) => $row->branch?->name ?? __('payroll.fields.all_branches'))
            ->addColumn('total_basic', fn ($row) => '<span dir="ltr">' . number_format($row->total_basic_salary, 2) . '</span>')
            ->addColumn('total_net', fn ($row) => '<span class="fw-bold text-success" dir="ltr">' . number_format($row->total_net_salary, 2) . ' ' . e($row->branch?->currency ?? '') . '</span>')
            ->addColumn('status_badge', fn ($row) => PayrollStatus::fromValue($row->status)?->badgeHtml()
                ?? '<span class="badge bg-label-secondary">' . e($row->status) . '</span>')
            ->addColumn('creator_name', fn ($row) => $row->creator?->name ?? '-')
            ->addColumn('actions', fn ($row) => view('admin.hr.payrolls.partials.actions', ['row' => $row])->render())
            ->rawColumns(['title_link', 'total_basic', 'total_net', 'status_badge', 'actions'])
            ->make(true);
    }
}
