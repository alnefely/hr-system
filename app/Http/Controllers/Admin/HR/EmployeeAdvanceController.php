<?php

namespace App\Http\Controllers\Admin\HR;

use App\Enums\AdvanceStatus;
use App\Enums\NotificationType;
use App\Services\Notification\NotificationService;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Services\Export\ExcelExportService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeAdvanceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // select صريح: yajra بيعمل join تلقائي للترتيب بأعمدة
            // العلاقات، و select * ساعتها بيخلي أعمدة الجدول المنضم
            // تدهس أعمدة الجدول الأصلي (id بالذات).
            $query = EmployeeAdvance::visibleTo()
                ->select('employee_advances.*')->with(['employee:id,name,employee_code,branch_id', 'employee.branch:id,currency', 'approver:id,name']);

            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('from_date')) {
                $query->whereDate('advance_date', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('advance_date', '<=', $request->to_date);
            }

            return datatables($query)
                ->addColumn('employee_name', fn($row) => $row->employee?->name ?? '-')
                ->addColumn('employee_code', fn($row) => $row->employee?->employee_code ?? '-')
                ->addColumn('formatted_amount', function ($row) {
                    $curr = $row->employee?->branch?->currency ?? 'EGP';
                    return '<span class="fw-bold text-primary" dir="ltr">' . number_format($row->amount, 2) . ' ' . $curr . '</span>';
                })
                ->addColumn('formatted_installment', function ($row) {
                    $curr = $row->employee?->branch?->currency ?? 'EGP';
                    return '<span dir="ltr">' . number_format($row->monthly_installment, 2) . ' ' . $curr . '</span>' . ' <small class="text-muted">(' . $row->installments_count . ' شهر)</small>';
                })
                ->addColumn('formatted_remaining', function ($row) {
                    $curr = $row->employee?->branch?->currency ?? 'EGP';
                    return '<span class="text-danger fw-bold" dir="ltr">' . number_format($row->remaining_amount, 2) . ' ' . $curr . '</span>';
                })
                ->addColumn('formatted_advance_date', fn($row) => $row->advance_date ? $row->advance_date->format('Y-m-d') : '-')
                ->addColumn('formatted_start_date', fn($row) => $row->start_date ? $row->start_date->format('Y-m-d') : '-')
                ->addColumn('status_badge', function ($row) {
                    // الحالة للعرض بس. التغيير بيتم من أزرار الاعتماد
                    // والرفض عشان يحترم الصلاحيات ودورة الموافقة.
                    $badges = [
                        'pending'  => 'warning',
                        'approved' => 'info',
                        'paid'     => 'success',
                        'rejected' => 'danger',
                    ];

                    $colour = $badges[$row->status] ?? 'secondary';
                    $key    = 'advance.status_enums.' . $row->status;
                    $label  = __($key) === $key ? $row->status : __($key);

                    return '<span class="badge bg-label-' . $colour . '">' . e($label) . '</span>';
                })
                ->addColumn('approver_name', fn($row) => $row->approver?->name ?? '-')
                ->addColumn('actions', function ($row) {
                    $curr = $row->employee?->branch?->currency ?? 'EGP';
                    $html = '<div class="d-inline-flex gap-1">';
                    
                    // Repayments history & payment modal button
                    $html .= '<button type="button" class="btn btn-sm btn-icon btn-label-info view-repayments-btn" 
                        data-id="' . $row->id . '" 
                        data-employee="' . e($row->employee?->name) . '"
                        data-total="' . $row->amount . '"
                        data-paid="' . $row->paid_amount . '"
                        data-waived="' . $row->waived_amount . '"
                        data-remaining="' . $row->remaining_amount . '"
                        data-currency="' . $curr . '"
                        title="سجل الدفعات وسداد دفعة">
                        <i class="fa-solid fa-receipt"></i>
                    </button>';

                    // Manager waiver button
                    if ($row->remaining_amount > 0 && $row->status !== 'rejected') {
                        $html .= '<button type="button" class="btn btn-sm btn-icon btn-label-warning waive-advance-btn" 
                            data-id="' . $row->id . '" 
                            data-employee="' . e($row->employee?->name) . '"
                            data-remaining="' . $row->remaining_amount . '"
                            data-currency="' . $curr . '"
                            title="إسقاط / إعفاء سلفة للموظف">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                        </button>';
                    }

                    if ($row->status === 'pending') {
                        if (admin_can('advance_approve')) {
                            $html .= '<form action="' . route('admin.advances.approve', $row->id) . '" method="POST" class="d-inline">
                                ' . csrf_field() . '
                                <button type="button" class="btn btn-sm btn-icon btn-label-success btn-swal-confirm" title="' . __('advance.approve') . '" data-title="اعتماد السلفة" data-text="هل أنت متأكد من اعتماد السلفة؟" data-icon="question" data-btn-text="نعم، اعتمد" data-btn-class="btn-success">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>';
                        }
                        if (admin_can('advance_reject')) {
                            $html .= '<form action="' . route('admin.advances.reject', $row->id) . '" method="POST" class="d-inline">
                                ' . csrf_field() . '
                                <button type="button" class="btn btn-sm btn-icon btn-label-danger btn-swal-confirm" title="' . __('advance.reject') . '" data-title="رفض السلفة" data-text="هل أنت متأكد من رفض طلب السلفة؟" data-icon="warning" data-btn-text="نعم، ارفض" data-btn-class="btn-danger">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </form>';
                        }
                    }
                    if (admin_can('advance_delete')) {
                        $html .= '<form action="' . route('admin.advances.destroy', $row->id) . '" method="POST" class="d-inline">
                            ' . csrf_field() . method_field('DELETE') . '
                            <button type="button" class="btn btn-sm btn-icon btn-label-secondary btn-swal-confirm" title="' . __('global.delete') . '" data-title="تأكيد الحذف" data-text="هل أنت متأكد من حذف هذا السجل؟" data-icon="warning" data-btn-text="نعم، احذف" data-btn-class="btn-danger">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->rawColumns(['formatted_amount', 'formatted_installment', 'formatted_remaining', 'status_badge', 'actions'])
                ->make(true);
        }

        $employees = Employee::visibleTo()->active()->with('branch:id,name,currency')->get(['id', 'name', 'employee_code', 'branch_id']);

        $selectedEmployeeId = $request->get('employee_id');
        $selectedEmployee   = null;
        $advanceStats       = null;

        if ($selectedEmployeeId) {
            $selectedEmployee = Employee::with('branch:id,name,currency')->find($selectedEmployeeId);
            if ($selectedEmployee) {
                $totalAdvances  = (float) EmployeeAdvance::where('employee_id', $selectedEmployeeId)->where('status', '!=', 'rejected')->sum('amount');
                $totalPaid      = (float) EmployeeAdvance::where('employee_id', $selectedEmployeeId)->sum('paid_amount');
                $totalWaived    = (float) EmployeeAdvance::where('employee_id', $selectedEmployeeId)->sum('waived_amount');
                $totalRemaining = (float) EmployeeAdvance::where('employee_id', $selectedEmployeeId)->sum('remaining_amount');

                $advanceStats = [
                    'total_advances'  => $totalAdvances,
                    'total_paid'      => $totalPaid,
                    'total_waived'    => $totalWaived,
                    'total_remaining' => $totalRemaining,
                    'currency'        => $selectedEmployee->branch?->currency ?? 'EGP',
                ];
            }
        }

        $fromDate = $request->from_date;
        $toDate   = $request->to_date;
        $selectedStatus = $request->status;
        $trashCount = EmployeeAdvance::visibleTo()->onlyTrashed()->count();

        // Check Excel Export
        if ($request->get('export') === 'excel') {
            $exportQuery = EmployeeAdvance::visibleTo()->with(['employee.branch', 'employee.jobTitle']);
            if ($request->filled('employee_id')) {
                $exportQuery->where('employee_id', $request->employee_id);
            }
            if ($request->filled('status')) {
                $exportQuery->where('status', $request->status);
            }
            if ($request->filled('from_date')) {
                $exportQuery->whereDate('advance_date', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $exportQuery->whereDate('advance_date', '<=', $request->to_date);
            }

            $advances = $exportQuery->orderByDesc('advance_date')->get();

            $headers = [
                __('global.export.row_no'),
                __('global.export.employee_code'),
                __('global.export.employee_name'),
                __('global.export.branch'),
                __('advance.export.advance_amount'),
                __('global.export.currency'),
                __('advance.export.installments_count'),
                __('advance.export.monthly_installment'),
                __('advance.export.paid'),
                __('advance.export.waived'),
                __('advance.export.remaining'),
                __('advance.export.advance_date'),
                __('advance.export.start_date'),
                __('global.export.status'),
            ];

            $rows = [];
            $i = 1;
            $sumAmount = 0;
            $sumPaid = 0;
            $sumWaived = 0;
            $sumRemaining = 0;

            foreach ($advances as $adv) {
                $curr = $adv->employee?->branch?->currency ?? 'EGP';
                $sumAmount += (float)$adv->amount;
                $sumPaid += (float)$adv->paid_amount;
                $sumWaived += (float)$adv->waived_amount;
                $sumRemaining += (float)$adv->remaining_amount;

                $statusLabel = __('advance.status_enums.' . $adv->status) ?? $adv->status;

                $rows[] = [
                    $i++,
                    $adv->employee?->employee_code ?? '-',
                    $adv->employee?->name ?? '-',
                    $adv->employee?->branch?->name ?? '-',
                    number_format($adv->amount, 2),
                    $curr,
                    $adv->installments_count,
                    number_format($adv->monthly_installment, 2),
                    number_format($adv->paid_amount, 2),
                    number_format($adv->waived_amount, 2),
                    number_format($adv->remaining_amount, 2),
                    $adv->advance_date ? $adv->advance_date->format('Y-m-d') : '-',
                    $adv->start_date ? $adv->start_date->format('Y-m-d') : '-',
                    $statusLabel,
                ];
            }

            $totals = [
                'الإجمالي',
                '',
                '',
                '',
                number_format($sumAmount, 2),
                '',
                '',
                '',
                number_format($sumPaid, 2),
                number_format($sumWaived, 2),
                number_format($sumRemaining, 2),
                '',
                '',
                '',
            ];

            $meta = [
                'إجمالي السجلات' => count($rows),
                'الفترة'        => ($fromDate ? "من $fromDate " : '') . ($toDate ? "إلى $toDate" : 'الكل'),
                'الموظف'        => $selectedEmployee ? $selectedEmployee->name : 'جميع الموظفين',
            ];

            return ExcelExportService::download(
                'تقرير وسجل السلف والقروض المالية',
                $headers,
                $rows,
                $totals,
                'سجل_السلف_' . date('Y_m_d'),
                $meta
            );
        }

        return view('admin.hr.advances.index', compact(
            'employees',
            'selectedEmployee',
            'selectedEmployeeId',
            'advanceStats',
            'fromDate',
            'toDate',
            'selectedStatus',
            'trashCount'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id'        => ['required', 'exists:employees,id'],
            'amount'             => ['required', 'numeric', 'min:1'],
            'installments_count' => ['required', 'integer', 'min:1'],
            'advance_date'       => ['required', 'date'],
            'start_date'         => ['required', 'date'],
            'reason'             => ['nullable', 'string'],
            'notes'              => ['nullable', 'string'],
        ]);

        $installments = max(1, (int) $data['installments_count']);
        $monthlyInstallment = round((float) $data['amount'] / $installments, 2);

        $advance = EmployeeAdvance::create([
            'employee_id'         => $data['employee_id'],
            'amount'              => $data['amount'],
            'installments_count'  => $installments,
            'monthly_installment' => $monthlyInstallment,
            'paid_amount'         => 0.00,
            'waived_amount'       => 0.00,
            'remaining_amount'    => $data['amount'],
            'advance_date'        => $data['advance_date'],
            'start_date'          => $data['start_date'],
            'status'              => 'pending',
            'reason'              => $data['reason'] ?? null,
            'notes'               => $data['notes'] ?? null,
        ]);

        $this->notifyAdvanceRequested($advance);

        return redirect()->back()->with('success', __('advance.messages.created'));
    }

    public function approve(EmployeeAdvance $advance)
    {
        if ($advance->status !== 'pending') {
            return redirect()->back()->with('error', __('advance.errors.not_pending'));
        }

        $advance->update([
            'status'      => 'approved',
            'approved_by' => admin()?->id,
            'approved_at' => now(),
        ]);

        $this->notifyAdvanceDecided($advance, 'approved');

        return redirect()->back()->with('success', __('advance.messages.approved'));
    }

    public function reject(EmployeeAdvance $advance)
    {
        if ($advance->status !== 'pending') {
            return redirect()->back()->with('error', __('advance.errors.not_pending'));
        }

        $advance->update([
            'status'      => 'rejected',
            'approved_by' => admin()?->id,
            'approved_at' => now(),
        ]);

        $this->notifyAdvanceDecided($advance, 'rejected');

        return redirect()->back()->with('success', __('advance.messages.rejected'));
    }

    public function recordPayment(Request $request, EmployeeAdvance $advance)
    {
        $request->validate([
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'repayment_date' => ['required', 'date'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        try {
            DB::transaction(function () use ($request, $advance) {
                // قفل السلفة لقراءة أحدث رصيد بشكل ذري
                $lockedAdvance = EmployeeAdvance::where('id', $advance->id)->lockForUpdate()->firstOrFail();

                if ((float) $lockedAdvance->remaining_amount <= 0) {
                    throw new Exception(__('advance.errors.already_fully_paid'));
                }

                $amount = (float) $request->amount;
                if ($amount > (float) $lockedAdvance->remaining_amount) {
                    throw new Exception(__('advance.errors.amount_exceeds_remaining', [
                        'amount'    => number_format($amount, 2),
                        'remaining' => number_format($lockedAdvance->remaining_amount, 2),
                    ]));
                }

                $lockedAdvance->repayments()->create([
                    'amount'         => $amount,
                    'repayment_date' => $request->repayment_date,
                    'type'           => 'manual_payment',
                    'notes'          => $request->notes ?? 'سداد نقدي مباشر',
                    'created_by'     => admin()?->id,
                ]);

                // إعادة احتساب الأرصدة والحالة آلياً من جدول السدادات
                $lockedAdvance->recalculateRepayments();
            });

            return redirect()->back()->with('success', __('advance.messages.payment_recorded'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function waive(Request $request, EmployeeAdvance $advance)
    {
        $request->validate([
            'waived_amount' => ['required', 'numeric', 'min:0.01'],
            'reason'        => ['required', 'string', 'max:500'],
        ]);

        try {
            DB::transaction(function () use ($request, $advance) {
                // قفل السلفة لقراءة أحدث رصيد بشكل ذري
                $lockedAdvance = EmployeeAdvance::where('id', $advance->id)->lockForUpdate()->firstOrFail();

                if ((float) $lockedAdvance->remaining_amount <= 0) {
                    throw new Exception(__('advance.errors.already_fully_paid'));
                }

                $amount = (float) $request->waived_amount;
                if ($amount > (float) $lockedAdvance->remaining_amount) {
                    throw new Exception(__('advance.errors.amount_exceeds_remaining', [
                        'amount'    => number_format($amount, 2),
                        'remaining' => number_format($lockedAdvance->remaining_amount, 2),
                    ]));
                }

                $lockedAdvance->repayments()->create([
                    'amount'         => $amount,
                    'repayment_date' => now()->toDateString(),
                    'type'           => 'manager_waiver',
                    'notes'          => 'إسقاط إداري معتمد: ' . $request->reason,
                    'created_by'     => admin()?->id,
                ]);

                // إعادة احتساب الأرصدة والحالة آلياً من جدول السدادات
                $lockedAdvance->recalculateRepayments();
            });

            return redirect()->back()->with('success', __('advance.messages.waived'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function repayments(EmployeeAdvance $advance)
    {
        $repayments = $advance->repayments()->with('creator:id,name')->get();
        return response()->json([
            'advance'    => $advance,
            'repayments' => $repayments,
        ]);
    }

    public function destroy(EmployeeAdvance $advance)
    {
        $advance->delete();
        return redirect()->back()->with('success', __('advance.messages.deleted'));
    }
    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = EmployeeAdvance::visibleTo()->onlyTrashed()->with(['employee:id,name,employee_code,branch_id', 'employee.branch:id,currency']);
            return datatables($query)
                ->addColumn('employee_name', fn($row) => $row->employee?->name ?? '-')
                ->addColumn('employee_code', fn($row) => $row->employee?->employee_code ?? '-')
                ->addColumn('formatted_amount', function ($row) {
                    $curr = $row->employee?->branch?->currency ?? 'EGP';
                    return '<span class="fw-bold text-primary" dir="ltr">' . number_format($row->amount, 2) . ' ' . $curr . '</span>';
                })
                ->addColumn('formatted_advance_date', fn($row) => $row->advance_date ? $row->advance_date->format('Y-m-d') : '-')
                ->addColumn('deleted_at', fn($row) => $row->deleted_at ? $row->deleted_at->format('Y-m-d H:i') : '-')
                ->rawColumns(['formatted_amount'])
                ->make(true);
        }
        return view('admin.hr.advances.trash');
    }

    public function restore(int $id)
    {
        $adv = EmployeeAdvance::onlyTrashed()->findOrFail($id);
        $adv->restore();
        return redirect()->back()->with('success', __('global.restore'));
    }

    public function forceDelete(int $id)
    {
        $adv = EmployeeAdvance::onlyTrashed()->findOrFail($id);
        $adv->forceDelete();
        return redirect()->back()->with('success', __('global.force_delete'));
    }

    // ========================================================
    //  الإشعارات
    // ========================================================

    /** إشعار المسؤولين بطلب سلفة جديد. */
    private function notifyAdvanceRequested(EmployeeAdvance $advance): void
    {
        $employee = $advance->employee;

        app(NotificationService::class)->notifyEligible(
            type: NotificationType::AdvanceRequested,
            title: __('notification.messages.advance_requested.title'),
            body: __('notification.messages.advance_requested.body', [
                'employee'     => $employee?->name ?? '-',
                'amount'       => number_format((float) $advance->amount, 2),
                'installments' => $advance->installments_count,
            ]),
            url: route('admin.advances.index', ['employee_id' => $advance->employee_id]),
            branchId: $employee?->branch_id,
            data: ['advance_id' => $advance->id],
            excludeAdminId: admin()?->id,
        );
    }

    /** إشعار مقدّم الطلب بنتيجة المراجعة. */
    private function notifyAdvanceDecided(EmployeeAdvance $advance, string $decision): void
    {
        $employee = $advance->employee;
        $targetAdminId = $employee?->admin_id;

        if (!$targetAdminId) {
            return;
        }

        app(NotificationService::class)->send(
            adminId: $targetAdminId,
            type: NotificationType::AdvanceDecided,
            title: __('notification.messages.advance_decided.title'),
            body: __('notification.messages.advance_decided.body', [
                'amount' => number_format((float) $advance->amount, 2),
                'status' => AdvanceStatus::fromValue($decision)?->label() ?? $decision,
            ]),
            url: route('admin.advances.index', ['employee_id' => $advance->employee_id]),
            branchId: $employee?->branch_id,
            data: ['advance_id' => $advance->id, 'decision' => $decision],
        );
    }
}
