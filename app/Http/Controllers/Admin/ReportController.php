<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Payroll;
use App\Models\Sale;
use App\Models\VendorPayment;
use App\Support\BranchScopeResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function financialPL(Request $request)
    {
        $scopedBranchId = BranchScopeResolver::branchId();

        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
        $branchId = $request->input('branch_id');

        // 1. Sales / Revenues grouped by currency
        $salesQuery = Sale::whereBetween('sale_date', [$dateFrom, $dateTo]);
        if ($branchId) {
            $salesQuery->where('branch_id', $branchId);
        }
        $salesByCurrency = (clone $salesQuery)
            ->select('currency', DB::raw('SUM(total_amount) as total'))
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();
        $totalSalesCount = (clone $salesQuery)->count();

        // 2. Operational / Petty Cash Expenses grouped by currency
        $expenseQuery = Expense::whereBetween('expense_date', [$dateFrom, $dateTo]);
        if ($branchId) {
            $expenseQuery->where('branch_id', $branchId);
        }
        $expensesByCurrency = (clone $expenseQuery)
            ->select('currency', DB::raw('SUM(amount) as total'))
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        // 3. Vendor Payments grouped by currency
        $vendorQuery = VendorPayment::join('vendor_subscriptions', 'vendor_payments.vendor_subscription_id', '=', 'vendor_subscriptions.id')
            ->whereBetween('vendor_payments.payment_date', [$dateFrom, $dateTo]);
        if ($branchId) {
            $vendorQuery->where('vendor_subscriptions.branch_id', $branchId);
        }
        // VendorPayment مش عليه Global Scope (بيرتبط بالفرع عبر الاشتراك)،
        // فبنطبّق العزل يدوياً هنا.
        if ($scopedBranchId) {
            $vendorQuery->where('vendor_subscriptions.branch_id', $scopedBranchId);
        }
        $vendorPaymentsByCurrency = (clone $vendorQuery)
            ->select('vendor_subscriptions.currency', DB::raw('SUM(vendor_payments.amount) as total'))
            ->groupBy('vendor_subscriptions.currency')
            ->pluck('total', 'vendor_subscriptions.currency')
            ->toArray();

        // 4. Payroll Costs (linked to branch currency or EGP, filtered by paid_at)
        $payrollByCurrency = DB::table('payroll_items')
            ->join('payrolls', 'payrolls.id', '=', 'payroll_items.payroll_id')
            ->join('employees', 'employees.id', '=', 'payroll_items.employee_id')
            ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
            ->where('payrolls.status', 'paid')
            ->where(function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('payrolls.paid_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                  ->orWhere(function ($q2) use ($dateFrom, $dateTo) {
                      $q2->whereNull('payrolls.paid_at')
                         ->whereBetween('payrolls.created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                  });
            })
            ->when($branchId, fn($q) => $q->where('employees.branch_id', $branchId))
            // الاستعلام ده مكتوب بـ DB::table فمابيمرّش على الـ Global Scope،
            // فبنطبّق عزل الفرع يدوياً عشان مشرف الفرع مايشوفش رواتب فروع تانية.
            ->when($scopedBranchId, fn($q) => $q->where('employees.branch_id', $scopedBranchId))
            ->groupBy(DB::raw("COALESCE(branches.currency, 'EGP')"))
            ->selectRaw("COALESCE(branches.currency, 'EGP') as currency, SUM(payroll_items.net_salary) as total")
            ->pluck('total', 'currency')
            ->toArray();

        // 5. Combine and calculate Multi-Currency Profit & Loss
        $allCurrencies = array_unique(array_merge(
            array_keys($salesByCurrency),
            array_keys($expensesByCurrency),
            array_keys($vendorPaymentsByCurrency),
            array_keys($payrollByCurrency)
        ));

        if (empty($allCurrencies)) {
            $allCurrencies = ['EGP'];
        }

        $currencyPnL = [];
        foreach ($allCurrencies as $curr) {
            $rev     = (float)($salesByCurrency[$curr] ?? 0);
            $exp     = (float)($expensesByCurrency[$curr] ?? 0);
            $vendor  = (float)($vendorPaymentsByCurrency[$curr] ?? 0);
            $payroll = (float)($payrollByCurrency[$curr] ?? 0);
            $costs   = $exp + $vendor + $payroll;
            $net     = $rev - $costs;
            $margin  = $rev > 0 ? ($net / $rev) * 100 : 0;

            $currencyPnL[$curr] = [
                'currency'        => $curr,
                'revenue'         => $rev,
                'expenses'        => $exp,
                'vendor_payments' => $vendor,
                'payroll'         => $payroll,
                'total_costs'     => $costs,
                'net_profit_loss' => $net,
                'profit_margin'   => $margin,
            ];
        }

        // Recent sales in period
        $recentSales = Sale::with(['customer:id,name', 'branch:id,name'])
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->latest('sale_date')
            ->take(6)
            ->get();

        // Recent operational expenses in period
        $recentExpenses = Expense::with(['category:id,name', 'branch:id,name'])
            ->whereBetween('expense_date', [$dateFrom, $dateTo])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->latest('expense_date')
            ->take(6)
            ->get();

        $branches = Branch::where('is_active', true)
            ->when($scopedBranchId, fn($q) => $q->where('id', $scopedBranchId))
            ->get(['id', 'name', 'currency']);

        return view('admin.reports.financial_pl', compact(
            'dateFrom',
            'dateTo',
            'branchId',
            'branches',
            'currencyPnL',
            'totalSalesCount',
            'recentSales',
            'recentExpenses'
        ));
    }
}
