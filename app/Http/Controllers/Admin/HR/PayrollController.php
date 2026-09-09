<?php

namespace App\Http\Controllers\Admin\HR;

use App\Enums\PayrollStatus;
use App\Enums\NotificationType;
use App\Services\Notification\NotificationService;
use App\Http\Controllers\Controller;
use App\Services\HR\PayrollGenerator;
use App\Models\Branch;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeReward;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // select صريح: yajra بيعمل join تلقائي للترتيب بأعمدة
            // العلاقات، و select * ساعتها بيخلي أعمدة الجدول المنضم
            // تدهس أعمدة الجدول الأصلي (id بالذات).
            $query = Payroll::select('payrolls.*')
                ->with(['branch:id,name,currency', 'creator:id,name', 'approver:id,name']);

            if ($request->filled('year')) {
                $query->where('year', $request->year);
            }

            if ($request->filled('month')) {
                $query->where('month', $request->month);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            // نطاق تواريخ على فترة المسير نفسها (سنة/شهر) مش على
            // تاريخ الإنشاء — المستخدم بيفكّر بالفترة اللي المسير
            // بيغطيها.
            if ($request->filled('date_from')) {
                $from = \Carbon\Carbon::parse($request->date_from);
                $query->whereRaw('(payrolls.year * 100 + payrolls.month) >= ?', [
                    $from->year * 100 + $from->month,
                ]);
            }

            if ($request->filled('date_to')) {
                $to = \Carbon\Carbon::parse($request->date_to);
                $query->whereRaw('(payrolls.year * 100 + payrolls.month) <= ?', [
                    $to->year * 100 + $to->month,
                ]);
            }

            return datatables($query)
                ->addColumn('title_link', function ($row) {
                    return '<a href="' . route('admin.payrolls.show', $row->id) . '" class="fw-bold text-primary">' . e($row->title) . '</a>';
                })
                ->addColumn('period', fn($row) => sprintf('%02d / %04d', $row->month, $row->year))
                ->addColumn('branch_name', fn($row) => $row->branch?->name ?? __('payroll.fields.all_branches'))
                ->addColumn('total_basic', fn($row) => '<span dir="ltr">' . number_format($row->total_basic_salary, 2) . '</span>')
                ->addColumn('total_net', fn($row) => '<span class="fw-bold text-success" dir="ltr">' . number_format($row->total_net_salary, 2) . ' ' . ($row->branch?->currency ?? '') . '</span>')
                // البادج والاسم المترجم مصدرهم PayrollStatus — مصدر واحد
                // بدل ما الخريطة تتكرر في كل كنترولر وview.
                ->addColumn('status_badge', fn($row) => PayrollStatus::fromValue($row->status)?->badgeHtml()
                    ?? '<span class="badge bg-label-secondary">' . e($row->status) . '</span>')
                ->addColumn('creator_name', fn($row) => $row->creator?->name ?? '-')
                ->addColumn('actions', function ($row) {
                    $html = '<div class="d-inline-flex gap-1">';
                    $html .= '<a href="' . route('admin.payrolls.show', $row->id) . '" class="btn btn-sm btn-icon btn-label-primary" title="' . __('payroll.actions.details') . '">
                        <i class="fa-solid fa-eye"></i>
                    </a>';
                    if ($row->status === 'draft' && admin_can('payroll_delete')) {
                        $html .= '<form action="' . route('admin.payrolls.destroy', $row->id) . '" method="POST" class="d-inline">
                            ' . csrf_field() . method_field('DELETE') . '
                            <button type="button" class="btn btn-sm btn-icon btn-label-secondary btn-swal-confirm" title="' . __('global.delete') . '"
                                data-title="' . __('global.confirm_delete') . '"
                                data-text="' . __('payroll.messages.confirm_delete') . '"
                                data-icon="warning"
                                data-btn-text="' . __('global.delete') . '"
                                data-btn-class="btn-danger">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->rawColumns(['title_link', 'total_basic', 'total_net', 'status_badge', 'actions'])
                ->make(true);
        }

        $branches = Branch::select('id', 'name')->get();
        $stats = [
            'total_paid'     => Payroll::where('status', 'paid')->sum('total_net_salary'),
            'draft_count'    => Payroll::where('status', 'draft')->count(),
            'approved_count' => Payroll::where('status', 'approved')->count(),
        ];

        return view('admin.hr.payrolls.index', compact('branches', 'stats'));
    }

    public function create()
    {
        $branches = Branch::select('id', 'name')->get();
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');

        return view('admin.hr.payrolls.create', compact('branches', 'currentYear', 'currentMonth'));
    }

    public function store(Request $request, PayrollGenerator $generator)
    {
        $validated = $request->validate([
            'year'      => 'required|integer|min:2020|max:2050',
            'month'     => 'required|integer|min:1|max:12',
            'branch_id' => 'nullable|exists:branches,id',
            'notes'     => 'nullable|string',
        ]);

        $year     = (int) $validated['year'];
        $month    = (int) $validated['month'];
        $branchId = $validated['branch_id'] ?? null;

        try {
            // من غير فرع محدد بنعمل كشف مستقل لكل فرع بدل كشف واحد
            // بيجمّع كل الموظفين — الكشف المجمّع مابينفعش للفلترة
            // ولا للتقارير الفرعية، وكل فرع ليه عملته وخزينته.
            if ($branchId === null) {
                $result = $generator->generateForAllBranches(
                    $year,
                    $month,
                    $validated['notes'] ?? null
                );

                foreach ($result['created'] as $payroll) {
                    $this->notifyPayrollAwaitingReview($payroll);
                }

                if (! $result['created']) {
                    return redirect()->back()->withInput()->with(
                        'error',
                        $result['skipped']
                            ? __('payroll.messages.all_branches_exist')
                            : __('payroll.messages.no_branches_to_generate')
                    );
                }

                $message = __('payroll.messages.created_for_branches', [
                    'count' => count($result['created']),
                ]);

                if ($result['skipped']) {
                    $message .= ' — ' . __('payroll.messages.skipped_branches', [
                        'branches' => implode('، ', $result['skipped']),
                    ]);
                }

                return redirect()->route('admin.payrolls.index')->with('success', $message);
            }

            if ($generator->exists($year, $month, $branchId)) {
                return redirect()->back()->withInput()->with('error', __('payroll.messages.already_exists'));
            }

            $payroll = $generator->generate($year, $month, $branchId, $validated['notes'] ?? null);
            $this->notifyPayrollAwaitingReview($payroll);
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

    public function updateItems(Request $request, Payroll $payroll)
    {
        if ($payroll->status !== 'draft') {
            return redirect()->back()->with('error', __('payroll.messages.cant_edit_approved'));
        }

        $request->validate([
            'items'                  => 'required|array',
            'items.*.allowances'     => 'nullable|numeric|min:0',
            'items.*.deductions'     => 'nullable|numeric|min:0',
            'items.*.payment_method' => 'required|in:cash,bank_transfer',
            'items.*.notes'          => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $payroll) {
            foreach ($request->items as $itemId => $data) {
                $item = $payroll->items()->find($itemId);
                if ($item) {
                    $item->update([
                        'allowances'     => $data['allowances'] ?? 0,
                        'deductions'     => $data['deductions'] ?? 0,
                        'payment_method' => $data['payment_method'],
                        'notes'          => $data['notes'] ?? null,
                    ]);
                }
            }
            $payroll->recalculateTotals();
        });

        return redirect()->back()->with('success', __('payroll.messages.updated'));
    }

    public function updateItemDetails(Request $request, PayrollItem $item)
    {
        if ($item->payroll?->status !== 'draft') {
            return redirect()->back()->with('error', __('payroll.messages.cant_edit_approved'));
        }

        $request->validate([
            'allowance_details'          => 'nullable|array',
            'allowance_details.*.title'  => 'required|string|max:150',
            'allowance_details.*.amount' => 'required|numeric|min:0',
            'deduction_details'          => 'nullable|array',
            'deduction_details.*.title'  => 'required|string|max:150',
            'deduction_details.*.amount' => 'required|numeric|min:0',
            'payment_method'             => 'required|in:cash,bank_transfer',
            'notes'                      => 'nullable|string',
        ]);

        $allowanceDetails = [];
        if ($request->has('allowance_details') && is_array($request->allowance_details)) {
            foreach ($request->allowance_details as $row) {
                if (!empty($row['title']) && isset($row['amount']) && $row['amount'] >= 0) {
                    $allowanceDetails[] = [
                        'title'  => trim($row['title']),
                        'amount' => (float) $row['amount'],
                    ];
                }
            }
        }

        $deductionDetails = [];
        if ($request->has('deduction_details') && is_array($request->deduction_details)) {
            foreach ($request->deduction_details as $row) {
                if (!empty($row['title']) && isset($row['amount']) && $row['amount'] >= 0) {
                    $deductionDetails[] = [
                        'title'  => trim($row['title']),
                        'amount' => (float) $row['amount'],
                    ];
                }
            }
        }

        $item->update([
            'allowance_details' => $allowanceDetails,
            'deduction_details' => $deductionDetails,
            'payment_method'    => $request->payment_method,
            'notes'             => $request->notes,
        ]);

        return redirect()->back()->with('success', __('payroll.messages.updated'));
    }

    public function approve(Payroll $payroll)
    {
        if ($payroll->status !== 'draft') {
            return redirect()->back()->with('error', __('payroll.messages.cant_edit_approved'));
        }

        $payroll->update([
            'status'      => 'approved',
            'approved_by' => admin()?->id,
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', __('payroll.messages.approved'));
    }

    public function reject(Request $request, Payroll $payroll)
    {
        if ($payroll->status !== 'draft' && $payroll->status !== 'rejected') {
            return redirect()->back()->with('error', __('payroll.messages.cant_edit_approved'));
        }

        $request->validate([
            'rejection_reason' => 'required|string|min:3|max:1000',
        ]);

        $payroll->update([
            'status'           => 'rejected',
            'rejected_by'      => admin()?->id,
            'rejected_at'      => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()->back()->with('success', __('payroll.messages.rejected'));
    }

    public function resubmit(Payroll $payroll)
    {
        if ($payroll->status !== 'rejected') {
            return redirect()->back()->with('error', __('payroll.messages.cant_edit_approved'));
        }

        $payroll->update([
            'status' => 'draft',
        ]);

        return redirect()->back()->with('success', __('payroll.messages.resubmitted'));
    }

    public function unapprove(Request $request, Payroll $payroll)
    {
        if ($payroll->status === 'paid') {
            return redirect()->back()->with('error', __('payroll.messages.cant_unapprove_paid'));
        }

        $request->validate([
            'unapprove_reason' => 'required|string|min:3|max:1000',
        ]);

        $payroll->update([
            'status'           => 'draft',
            'unapproved_by'    => admin()?->id,
            'unapproved_at'    => now(),
            'unapprove_reason' => $request->unapprove_reason,
            'approved_by'      => null,
            'approved_at'      => null,
        ]);

        return redirect()->back()->with('success', __('payroll.messages.unapproved'));
    }

    public function payItem(Request $request, PayrollItem $item)
    {
        if ($item->payroll?->status !== 'approved') {
            return redirect()->back()->with('error', __('payroll.messages.cant_pay_unapproved'));
        }

        $request->validate([
            'payment_method' => 'nullable|in:cash,bank_transfer',
        ]);

        if ($item->status === 'paid') {
            return redirect()->back()->with('error', __('payroll.messages.already_paid'));
        }

        DB::transaction(function () use ($request, $item) {
            // القفل بيمنع إن طلبين متزامنين يدفعوا نفس البند ويخصموا السلفة مرتين.
            $locked = PayrollItem::whereKey($item->id)->lockForUpdate()->first();
            if (!$locked || $locked->status === 'paid') {
                return;
            }

            $locked->update([
                'status'         => 'paid',
                'payment_method' => $request->payment_method ?? $locked->payment_method,
                'paid_at'        => now(),
                'paid_by'        => admin()?->id,
            ]);

            $this->settleAdvanceForPayrollItem($locked);

            $payroll = $item->payroll;
            if ($payroll && $payroll->items()->where('status', '!=', 'paid')->count() === 0) {
                $payroll->update([
                    'status'  => 'paid',
                    'paid_by' => admin()?->id,
                    'paid_at' => now(),
                ]);
            }
        });

        return redirect()->back()->with('success', __('payroll.messages.paid_single'));
    }

    public function paySelected(Request $request, Payroll $payroll)
    {
        if ($payroll->status !== 'approved') {
            return redirect()->back()->with('error', __('payroll.messages.cant_pay_unapproved'));
        }

        $request->validate([
            'item_ids'       => 'required|array',
            'item_ids.*'     => 'exists:payroll_items,id',
            'payment_method' => 'nullable|in:cash,bank_transfer',
        ]);

        DB::transaction(function () use ($request, $payroll) {
            // where('status', '!=', 'paid') بيضمن إن البند المدفوع مايتعالجش تاني
            // ومايتخصمش منه قسط السلفة مرة كمان.
            $items = $payroll->items()
                ->whereIn('id', $request->item_ids)
                ->where('status', '!=', 'paid')
                ->lockForUpdate()
                ->get();

            foreach ($items as $item) {
                $item->update([
                    'status'         => 'paid',
                    'paid_at'        => now(),
                    'paid_by'        => admin()?->id,
                    'payment_method' => $request->filled('payment_method') ? $request->payment_method : $item->payment_method,
                ]);
                $this->settleAdvanceForPayrollItem($item);
            }

            if ($payroll->items()->where('status', '!=', 'paid')->count() === 0) {
                $payroll->update([
                    'status'  => 'paid',
                    'paid_by' => admin()?->id,
                    'paid_at' => now(),
                ]);
            }
        });

        return redirect()->back()->with('success', __('payroll.messages.paid_selected'));
    }

    public function pay(Payroll $payroll)
    {
        if ($payroll->status !== 'approved') {
            return redirect()->back()->with('error', __('payroll.messages.cant_pay_unapproved'));
        }

        DB::transaction(function () use ($payroll) {
            $payroll->update([
                'status'  => 'paid',
                'paid_by' => admin()?->id,
                'paid_at' => now(),
            ]);

            $items = $payroll->items()
                ->where('status', '!=', 'paid')
                ->lockForUpdate()
                ->get();

            foreach ($items as $item) {
                $item->update([
                    'status'  => 'paid',
                    'paid_at' => now(),
                    'paid_by' => admin()?->id,
                ]);
                $this->settleAdvanceForPayrollItem($item);
            }
        });

        return redirect()->back()->with('success', __('payroll.messages.paid'));
    }

    public function destroy(Payroll $payroll)
    {
        if ($payroll->status !== 'draft') {
            return redirect()->back()->with('error', __('payroll.messages.cant_edit_approved'));
        }

        $payroll->delete();

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

    private function settleAdvanceForPayrollItem(PayrollItem $item): void
    {
        if (is_array($item->deduction_details)) {
            foreach ($item->deduction_details as $ded) {
                if (!empty($ded['advance_id'])) {
                    $adv = EmployeeAdvance::find($ded['advance_id']);
                    if ($adv && in_array($adv->status, ['approved', 'paid'])) {
                        $paidInst = (float) ($ded['amount'] ?? 0);
                        if ($paidInst > 0) {
                            $adv->repayments()->create([
                                'amount'         => $paidInst,
                                'repayment_date' => now()->toDateString(),
                                'type'           => 'payroll_deduction',
                                'notes'          => 'استقطاع قسط سلفة من مسير رواتب شهر ' . ($item->payroll?->month . '/' . $item->payroll?->year),
                                'created_by'     => admin()?->id,
                            ]);

                            $adv->recalculateRepayments();
                        }
                    }
                }
            }
        }

        // Settle Rewards
        if (is_array($item->allowance_details)) {
            foreach ($item->allowance_details as $al) {
                if (!empty($al['reward_id'])) {
                    EmployeeReward::where('id', $al['reward_id'])->where('status', 'approved')->update([
                        'status' => 'paid',
                    ]);
                }
            }
        }
    }

    /** إشعار المعتمِدين بمسير جاهز للمراجعة. */
    private function notifyPayrollAwaitingReview(Payroll $payroll): void
    {
        app(NotificationService::class)->notifyEligible(
            type: NotificationType::PayrollAwaitingReview,
            title: __('notification.messages.payroll_awaiting_review.title'),
            body: __('notification.messages.payroll_awaiting_review.body', [
                'title' => $payroll->title,
                'net'   => number_format((float) $payroll->total_net_salary, 2),
            ]),
            url: route('admin.payrolls.show', $payroll->id),
            branchId: $payroll->branch_id,
            data: ['payroll_id' => $payroll->id],
            excludeAdminId: admin()?->id,
        );
    }
}
