<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VendorSubscriptionRequest;
use App\Models\Branch;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Http\Requests\Admin\RecordVendorPaymentRequest;
use App\Models\VendorSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VendorSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        // select صريح: yajra بيعمل join تلقائي للترتيب بأعمدة
        // العلاقات، و select * ساعتها بيخلي أعمدة الجدول المنضم
        // تدهس أعمدة الجدول الأصلي (id بالذات).
        $query = VendorSubscription::select('vendor_subscriptions.*')
            ->with(['vendor:id,name,company_name', 'branch:id,name']);

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('billing_cycle')) {
            $query->where('billing_cycle', $request->billing_cycle);
        }

        if ($request->ajax()) {
            return datatables($query)
                ->addColumn('vendor_name', fn($row) => $row->vendor?->name ?? '-')
                ->addColumn('branch_name', fn($row) => $row->branch?->name ?? __('global.all_branches'))
                ->addColumn('formatted_cost', fn($row) => '<span class="fw-bold" dir="ltr">' . number_format($row->cost, 2) . '</span> <span class="badge bg-label-info">' . e($row->currency) . '</span>')
                ->addColumn('cycle_badge', function ($row) {
                    return '<span class="badge bg-label-secondary">' . __('vendor_subscription.cycles.' . $row->billing_cycle) . '</span>';
                })
                ->addColumn('status_badge', function ($row) {
                    $status = $row->computed_status;
                    $badges = [
                        'active'    => 'bg-label-success',
                        'pending'   => 'bg-label-warning',
                        'expired'   => 'bg-label-danger',
                        'cancelled' => 'bg-label-secondary',
                    ];
                    $class = $badges[$status] ?? 'bg-label-secondary';
                    return '<span class="badge ' . $class . '">' . __('vendor_subscription.statuses.' . $status) . '</span>';
                })
                ->addColumn('formatted_next_billing', function ($row) {
                    if (!$row->next_billing_date) {
                        return '-';
                    }
                    $dateStr = $row->next_billing_date->format('Y-m-d');
                    $days = $row->remaining_days;

                    if ($days > 0) {
                        return '<span dir="ltr">' . $dateStr . '</span> <span class="badge bg-label-warning ms-1" data-bs-toggle="tooltip" title="متبقي ' . $days . ' يوماً على موعد التجديد القادم">متبقي ' . $days . ' يوم</span>';
                    } elseif ($days === 0) {
                        return '<span dir="ltr">' . $dateStr . '</span> <span class="badge bg-label-danger ms-1" data-bs-toggle="tooltip" title="تاريخ الاستحقاق والتجديد هو اليوم">مستحق اليوم</span>';
                    } else {
                        return '<span dir="ltr">' . $dateStr . '</span> <span class="badge bg-label-danger ms-1" data-bs-toggle="tooltip" title="الاشتراك متأخر عن التجديد">متأخر ' . abs($days) . ' يوم</span>';
                    }
                })
                ->addColumn('actions', fn ($row) => view('admin.partials.table-actions.crud', ['row' => $row, 'routes' => 'admin.vendor-subscriptions', 'can' => 'vendor_subscription', 'show' => true])->render())
                ->rawColumns(['formatted_cost', 'cycle_badge', 'status_badge', 'formatted_next_billing', 'actions'])
                ->make(true);
        }

        $vendors  = Vendor::where('is_active', true)->get(['id', 'name']);
        $branches = Branch::accessible()->where('is_active', true)->get(['id', 'name']);

        // Multi-currency calculation for active subscriptions costs!
        $activeCostsByCurrency = VendorSubscription::where('status', 'active')
            ->select('currency', DB::raw('SUM(cost) as total_cost'))
            ->groupBy('currency')
            ->pluck('total_cost', 'currency')
            ->toArray();

        $activeSubscriptionsCount = VendorSubscription::where('status', 'active')->count();

        $upcomingRenewalsCount = VendorSubscription::where('status', 'active')
            ->whereNotNull('next_billing_date')
            ->whereBetween('next_billing_date', [now(), now()->addDays(30)])
            ->count();

        $expiredCount = VendorSubscription::where(function ($q) {
            $q->where('status', 'expired')
              ->orWhere(fn($q2) => $q2->where('status', 'active')->where('next_billing_date', '<', now()));
        })->count();

        return view('admin.vendor-subscriptions.index', compact(
            'vendors',
            'branches',
            'activeCostsByCurrency',
            'activeSubscriptionsCount',
            'upcomingRenewalsCount',
            'expiredCount'
        ));
    }

    public function create()
    {
        $vendors  = Vendor::where('is_active', true)->get(['id', 'name']);
        $branches = Branch::accessible()->where('is_active', true)->get(['id', 'name']);

        return view('admin.vendor-subscriptions.create', compact('vendors', 'branches'));
    }

    public function store(VendorSubscriptionRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = admin()?->id;
        $data['auto_renew'] = $request->boolean('auto_renew', true);

        if ($request->hasFile('attachment_file')) {
            $data['attachment'] = $request->file('attachment_file')->store('vendor_subscriptions', 'public');
        }

        VendorSubscription::create($data);

        return redirect()->route('admin.vendor-subscriptions.index')->with('success', __('vendor_subscription.messages.created'));
    }

    public function show(VendorSubscription $vendorSubscription)
    {
        $vendorSubscription->load(['vendor', 'branch', 'creator', 'payments.creator']);

        return view('admin.vendor-subscriptions.show', compact('vendorSubscription'));
    }

    public function edit(VendorSubscription $vendorSubscription)
    {
        $vendors  = Vendor::where('is_active', true)->get(['id', 'name']);
        $branches = Branch::accessible()->where('is_active', true)->get(['id', 'name']);

        return view('admin.vendor-subscriptions.edit', compact('vendorSubscription', 'vendors', 'branches'));
    }

    public function update(VendorSubscriptionRequest $request, VendorSubscription $vendorSubscription)
    {
        $data = $request->validated();
        $data['auto_renew'] = $request->boolean('auto_renew');

        if ($request->hasFile('attachment_file')) {
            if ($vendorSubscription->attachment) {
                Storage::disk('public')->delete($vendorSubscription->attachment);
            }
            $data['attachment'] = $request->file('attachment_file')->store('vendor_subscriptions', 'public');
        }

        $vendorSubscription->update($data);

        return redirect()->route('admin.vendor-subscriptions.index')->with('success', __('vendor_subscription.messages.updated'));
    }

    public function destroy(VendorSubscription $vendorSubscription)
    {
        $vendorSubscription->delete();

        return redirect()->route('admin.vendor-subscriptions.index')->with('success', __('vendor_subscription.messages.deleted'));
    }

    public function recordPayment(RecordVendorPaymentRequest $request, VendorSubscription $vendorSubscription)
    {
        $paymentData = [
            'vendor_subscription_id' => $vendorSubscription->id,
            'amount'                 => $request->amount,
            'payment_date'           => $request->payment_date,
            'payment_method'         => $request->payment_method,
            'transaction_reference'  => $request->transaction_reference,
            'invoice_number'         => $request->invoice_number,
            'notes'                  => $request->notes,
            'created_by'             => admin()?->id,
        ];

        if ($request->hasFile('attachment_file')) {
            $paymentData['attachment'] = $request->file('attachment_file')->store('vendor_payments', 'public');
        }

        VendorPayment::create($paymentData);

        // Auto extend next_billing_date if provided or by cycle
        if ($request->filled('renew_next_date')) {
            $vendorSubscription->next_billing_date = $request->renew_next_date;
            $vendorSubscription->status = 'active';
        } else {
            $baseDate = $vendorSubscription->next_billing_date
                ? Carbon::parse($vendorSubscription->next_billing_date)
                : Carbon::parse($request->payment_date);

            // إذا كان تاريخ التجديد السابق في الماضي، نبدأ الاحتساب من تاريخ الدفعة الحالية
            if ($baseDate->isPast()) {
                $baseDate = Carbon::parse($request->payment_date);
            }

            $newNext = match ($vendorSubscription->billing_cycle) {
                'monthly'       => $baseDate->copy()->addMonth(),
                'quarterly'     => $baseDate->copy()->addMonths(3),
                'semi_annually' => $baseDate->copy()->addMonths(6),
                'annually'      => $baseDate->copy()->addYear(),
                default         => $baseDate,
            };
            $vendorSubscription->next_billing_date = $newNext;
            $vendorSubscription->status = 'active';
        }

        $vendorSubscription->save();

        return redirect()->back()->with('success', __('vendor_subscription.messages.payment_success'));
    }
    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = VendorSubscription::onlyTrashed()->with(['vendor:id,name,company_name']);
            return datatables($query)
                ->addColumn('vendor_name', fn($row) => $row->vendor?->company_name ?: ($row->vendor?->name ?? '-'))
                ->addColumn('cost_formatted', fn($row) => '<span dir="ltr">' . number_format($row->cost, 2) . '</span> ' . ($row->currency ?? 'USD'))
                ->addColumn('deleted_at', fn($row) => $row->deleted_at ? $row->deleted_at->format('Y-m-d H:i') : '-')
                ->rawColumns(['cost_formatted'])
                ->make(true);
        }
        return view('admin.vendor-subscriptions.trash');
    }

    public function restore(int $id)
    {
        $sub = VendorSubscription::onlyTrashed()->findOrFail($id);
        $sub->restore();
        return redirect()->back()->with('success', __('global.restore'));
    }

    public function forceDelete(int $id)
    {
        $sub = VendorSubscription::onlyTrashed()->findOrFail($id);
        $sub->forceDelete();
        return redirect()->back()->with('success', __('global.force_delete'));
    }
}
