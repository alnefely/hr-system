<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaleRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SalesChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        // select صريح: yajra بيعمل join تلقائي للترتيب بأعمدة
        // العلاقات، و select * ساعتها بيخلي أعمدة الجدول المنضم
        // تدهس أعمدة الجدول الأصلي (id بالذات).
        $query = Sale::visibleTo()
            ->select('sales.*')->with(['customer:id,name,company_name', 'branch:id,name,currency', 'salesChannel:id,name']);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('sales_channel_id')) {
            $query->where('sales_channel_id', $request->sales_channel_id);
        }

        if ($request->filled('sale_type')) {
            $query->where('sale_type', $request->sale_type);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        if ($request->ajax()) {
            return datatables($query)
                ->addColumn('invoice_link', function ($row) {
                    return '<a href="' . route('admin.sales.show', $row->id) . '" class="fw-bold text-primary" dir="ltr">' . e($row->invoice_number) . '</a>';
                })
                ->addColumn('customer_name', fn($row) => $row->customer?->name ?? __('sale.direct_customer'))
                ->addColumn('branch_name', fn($row) => $row->branch?->name ?? '-')
                ->addColumn('channel_name', fn($row) => $row->salesChannel?->name ?? ($row->sales_channel ?? '-'))
                ->addColumn('formatted_total', function ($row) {
                    $curr = $row->currency ?? $row->branch?->currency ?? 'EGP';
                    return '<span class="fw-bold" dir="ltr">' . number_format($row->total_amount, 2) . '</span> <span class="badge bg-label-info">' . e($curr) . '</span>';
                })
                ->addColumn('payment_badge', function ($row) {
                    $badges = [
                        'paid'           => ['class' => 'bg-label-success', 'label' => __('sale.statuses.paid')],
                        'partially_paid' => ['class' => 'bg-label-warning', 'label' => __('sale.statuses.partially_paid')],
                        'unpaid'         => ['class' => 'bg-label-danger', 'label' => __('sale.statuses.unpaid')],
                    ];
                    $info = $badges[$row->payment_status] ?? ['class' => 'bg-label-secondary', 'label' => $row->payment_status];
                    return '<span class="badge ' . $info['class'] . '">' . $info['label'] . '</span>';
                })
                ->addColumn('formatted_date', fn($row) => $row->sale_date ? '<span dir="ltr">' . $row->sale_date->format('Y-m-d') . '</span>' : '-')
                ->addColumn('actions', function ($row) {
                    $html = '<div class="d-inline-flex gap-1">';
                    if (admin_can('sale_show')) {
                        $html .= '<a href="' . route('admin.sales.show', $row->id) . '" class="btn btn-sm btn-icon btn-label-info" data-bs-toggle="tooltip" title="' . __('global.view') . '">
                            <i class="fa-solid fa-eye"></i>
                        </a>';
                    }
                    if (admin_can('sale_update')) {
                        $html .= '<a href="' . route('admin.sales.edit', $row->id) . '" class="btn btn-sm btn-icon btn-label-primary" data-bs-toggle="tooltip" title="' . __('global.edit') . '">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>';
                    }
                    if (admin_can('sale_delete')) {
                        $html .= '<form action="' . route('admin.sales.destroy', $row->id) . '" method="POST" class="d-inline">
                            ' . csrf_field() . method_field('DELETE') . '
                            <button type="button" class="btn btn-sm btn-icon btn-label-danger waves-effect btn-swal-confirm" 
                                data-title="' . __('global.confirm_delete') . '" 
                                data-text="' . __('global.confirm_delete') . '" 
                                data-icon="warning" 
                                data-btn-text="' . __('global.delete') . '" 
                                data-btn-class="btn-danger" 
                                title="' . __('global.delete') . '">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->rawColumns(['invoice_link', 'formatted_total', 'payment_badge', 'formatted_date', 'actions'])
                ->make(true);
        }

        $customers     = Customer::where('is_active', true)->get(['id', 'name']);
        $branches      = Branch::where('is_active', true)->get(['id', 'name', 'currency']);
        $salesChannels = SalesChannel::where('is_active', true)->get(['id', 'name']);

        // الكروت لازم تعكس نفس الفلاتر المطبّقة على الجدول. من غير
        // فلتر تاريخ بنرجع للشهر الحالي كسلوك افتراضي.
        $hasDateFilter = $request->filled('date_from') || $request->filled('date_to');
        $statsQuery    = clone $query;

        if (! $hasDateFilter) {
            $statsQuery->inMonth(now()->year, now()->month);
        }

        // الاستعلام الأساسي عليه select('sales.*') للترتيب بأعمدة
        // العلاقات. استعلامات التجميع لازم تصفّره — selectRaw
        // بيضيف على الموجود، والنتيجة أعمدة مش مجمّعة مع COUNT
        // وده بيقع في MySQL بـ only_full_group_by.
        $revenuesByCurrency = (clone $statsQuery)
            ->reorder()
            ->select('sales.currency', DB::raw('SUM(sales.total_amount) as total_rev'))
            ->groupBy('sales.currency')
            ->pluck('total_rev', 'currency')
            ->toArray();

        // صف واحد بكل العدادات بدل 3 استعلامات منفصلة
        $counts = (clone $statsQuery)
            ->reorder()
            ->select(DB::raw('COUNT(*) as total'))
            ->selectRaw("SUM(CASE WHEN sales.payment_status = 'paid' THEN 1 ELSE 0 END) as paid")
            ->selectRaw("SUM(CASE WHEN sales.payment_status IN ('unpaid', 'partially_paid') THEN 1 ELSE 0 END) as unpaid")
            ->first();

        $totalInvoicesCount = (int) ($counts->total ?? 0);
        $paidSalesCount     = (int) ($counts->paid ?? 0);
        $unpaidSalesCount   = (int) ($counts->unpaid ?? 0);

        // وصف الأرقام: الفترة + أي فلاتر تانية شغالة.
        //
        // من غير ذكر باقي الفلاتر، فلتر القناة (مثلاً) بيغيّر
        // الأرقام والكارت لسه بيقول "خلال الشهر الحالي" بس —
        // فالمستخدم يفتكر إن الفلتر مااشتغلش.
        $statsPeriodLabel = $hasDateFilter
            ? __('sale.stats.period_filtered', [
                'from' => $request->date_from ?: __('sale.stats.period_open_start'),
                'to'   => $request->date_to ?: __('sale.stats.period_open_end'),
            ])
            : __('sale.stats.period_current_month');

        $activeFilters = [];

        if ($request->filled('branch_id')) {
            $activeFilters[] = Branch::find($request->branch_id)?->name;
        }

        if ($request->filled('customer_id')) {
            $activeFilters[] = Customer::find($request->customer_id)?->name;
        }

        if ($request->filled('sales_channel_id')) {
            $activeFilters[] = SalesChannel::find($request->sales_channel_id)?->name;
        }

        if ($request->filled('payment_status')) {
            $key = 'sale.statuses.' . $request->payment_status;
            $activeFilters[] = __($key) === $key ? $request->payment_status : __($key);
        }

        if ($request->filled('sale_type')) {
            $key = 'sale.type_enums.' . $request->sale_type;
            $activeFilters[] = __($key) === $key ? $request->sale_type : __($key);
        }

        if ($request->filled('currency')) {
            $activeFilters[] = $request->currency;
        }

        $activeFilters = array_values(array_filter($activeFilters));

        if ($activeFilters) {
            $statsPeriodLabel .= ' · ' . implode(' · ', $activeFilters);
        }

        return view('admin.sales.index', compact(
            'customers',
            'branches',
            'salesChannels',
            'revenuesByCurrency',
            'paidSalesCount',
            'unpaidSalesCount',
            'totalInvoicesCount',
            'statsPeriodLabel'
        ));
    }

    public function create()
    {
        $customers     = Customer::where('is_active', true)->get(['id', 'name']);
        $branches      = Branch::where('is_active', true)->get(['id', 'name', 'currency']);
        $salesChannels = SalesChannel::where('is_active', true)->get(['id', 'name']);
        
        $nextInvoiceNumber = Sale::nextInvoiceNumber();

        return view('admin.sales.create', compact('customers', 'branches', 'salesChannels', 'nextInvoiceNumber'));
    }

    public function store(SaleRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = admin()?->id;

        $branch = Branch::find($data['branch_id']);
        if (empty($data['currency'])) {
            $data['currency'] = $branch?->currency ?? 'EGP';
        }

        $status = $data['payment_status'] ?? 'paid';
        $total  = (float)($data['total_amount'] ?? 0);

        if ($status === 'paid') {
            $data['paid_amount'] = $total;
            $data['due_amount']  = 0;
        } elseif ($status === 'unpaid') {
            $data['paid_amount'] = 0;
            $data['due_amount']  = $total;
        } else {
            $paid = (float)($data['paid_amount'] ?? 0);
            $paid = min($paid, $total);
            $data['paid_amount'] = $paid;
            $data['due_amount']  = max(0, $total - $paid);
        }

        // المرفق بييجي كمسار من مدير الملفات، فمفيش رفع هنا.

        DB::transaction(function () use ($data, $request) {
            $sale = Sale::create($data);

            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    if (!empty($item['item_name'])) {
                        SaleItem::create([
                            'sale_id'     => $sale->id,
                            'item_name'   => $item['item_name'],
                            'quantity'    => $item['quantity'] ?? 1,
                            'unit_price'  => $item['unit_price'] ?? 0,
                            'total_price' => $item['total_price'] ?? 0,
                        ]);
                    }
                }
            }

            if ($sale->paid_amount > 0) {
                SalePayment::create([
                    'sale_id'          => $sale->id,
                    'payment_date'     => $sale->sale_date ?? now(),
                    'amount'           => $sale->paid_amount,
                    'payment_method'   => $sale->payment_method ?? 'cash',
                    'reference_number' => $sale->invoice_number . '-INIT',
                    'notes'            => 'دفعة مسددة عند إنشاء الفاتورة',
                    'created_by'       => admin()?->id,
                ]);
            }
        });

        return redirect()->route('admin.sales.index')->with('success', __('sale.messages.created'));
    }

    public function show(Sale $sale)
    {
        $sale->load(['customer', 'branch', 'salesChannel', 'creator', 'items', 'payments.creator']);

        return view('admin.sales.show', compact('sale'));
    }

    public function edit(Sale $sale)
    {
        $sale->load('items');
        $customers     = Customer::where('is_active', true)->get(['id', 'name']);
        $branches      = Branch::where('is_active', true)->get(['id', 'name', 'currency']);
        $salesChannels = SalesChannel::where('is_active', true)->get(['id', 'name']);

        return view('admin.sales.edit', compact('sale', 'customers', 'branches', 'salesChannels'));
    }

    public function update(SaleRequest $request, Sale $sale)
    {
        $data = $request->validated();

        $branch = Branch::find($data['branch_id']);
        if (empty($data['currency'])) {
            $data['currency'] = $branch?->currency ?? 'EGP';
        }

        // المدفوع والمتبقي وحالة السداد مصدرها الوحيد هو سجل الدفعات (sale_payments).
        // لو سيبناهم يتحدّثوا من الفورم هنا، أي تعديل على الفاتورة كان بيخالف السجل
        // ويخلي المبلغ المدفوع رقم مش مسنود بأي دفعة فعلية.
        unset($data['paid_amount'], $data['due_amount'], $data['payment_status']);

        // المرفق بييجي كمسار من مدير الملفات. الملفات بتفضل في
        // المكتبة عشان تتعاد استخدامها، فمابنحذفش الملف القديم.

        DB::transaction(function () use ($sale, $data, $request) {
            $sale->update($data);

            if ($request->has('items') && is_array($request->items)) {
                $sale->items()->delete();
                foreach ($request->items as $item) {
                    if (!empty($item['item_name'])) {
                        SaleItem::create([
                            'sale_id'     => $sale->id,
                            'item_name'   => $item['item_name'],
                            'quantity'    => $item['quantity'] ?? 1,
                            'unit_price'  => $item['unit_price'] ?? 0,
                            'total_price' => $item['total_price'] ?? 0,
                        ]);
                    }
                }
            }

            // الإجمالي ممكن يكون اتغير، فلازم نعيد حساب المتبقي وحالة السداد.
            $sale->recalculatePayments();
        });

        return redirect()->route('admin.sales.index')->with('success', __('sale.messages.updated'));
    }

    public function destroy(Sale $sale)
    {
        $sale->delete();

        return redirect()->route('admin.sales.index')->with('success', __('sale.messages.deleted'));
    }
    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = Sale::onlyTrashed()->with(['customer:id,name', 'salesChannel:id,name']);
            return datatables($query)
                ->addColumn('customer_name', fn($row) => $row->customer?->name ?? '-')
                ->addColumn('channel_name', fn($row) => $row->salesChannel?->name ?? '-')
                ->addColumn('net_formatted', fn($row) => '<span dir="ltr">' . number_format($row->net_amount, 2) . '</span> ' . ($row->currency ?? 'EGP'))
                ->addColumn('deleted_at', fn($row) => $row->deleted_at ? $row->deleted_at->format('Y-m-d H:i') : '-')
                ->rawColumns(['net_formatted'])
                ->make(true);
        }
        return view('admin.sales.trash');
    }

    public function restore(int $id)
    {
        $sale = Sale::onlyTrashed()->findOrFail($id);
        $sale->restore();
        return redirect()->back()->with('success', __('global.restore'));
    }

    public function forceDelete(int $id)
    {
        $sale = Sale::onlyTrashed()->findOrFail($id);
        $sale->forceDelete();
        return redirect()->back()->with('success', __('global.force_delete'));
    }

    public function addPayment(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'payment_date'     => ['required', 'date'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_method'   => ['required', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ]);

        if ((float)$validated['amount'] > ((float)$sale->due_amount + 0.01)) {
            return redirect()->back()->with('error', __('sale.payments.amount_exceeds'));
        }

        DB::transaction(function () use ($validated, $sale) {
            SalePayment::create([
                'sale_id'          => $sale->id,
                'payment_date'     => $validated['payment_date'],
                'amount'           => $validated['amount'],
                'payment_method'   => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes'            => $validated['notes'] ?? null,
                'created_by'       => admin()?->id,
            ]);

            $sale->recalculatePayments();
        });

        return redirect()->back()->with('success', __('sale.payments.success_added'));
    }

    public function deletePayment(Sale $sale, SalePayment $payment)
    {
        if ($payment->sale_id !== $sale->id) {
            abort(404);
        }

        DB::transaction(function () use ($sale, $payment) {
            $payment->delete();
            $sale->recalculatePayments();
        });

        return redirect()->back()->with('success', __('sale.payments.success_deleted'));
    }
}
