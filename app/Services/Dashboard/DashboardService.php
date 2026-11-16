<?php

namespace App\Services\Dashboard;

use App\Enums\AdvanceStatus;
use App\Enums\ApprovalStatus;
use App\Enums\LeaveStatus;
use App\Enums\PayrollStatus;
use App\Enums\RewardStatus;
use App\Models\AdminNotification;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLeave;
use App\Models\EmployeePenalty;
use App\Models\EmployeeReward;
use App\Models\Expense;
use App\Models\Payroll;
use App\Models\Sale;
use App\Models\VendorSubscription;
use App\Services\Dashboard\DashboardFilter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * بيانات لوحة التحكم.
 *
 * كل الأرقام بتتجمّع في استعلامات SQL مجمّعة مش حلقات PHP، عشان
 * الصفحة تفضل سريعة مع نمو البيانات. وكل حاجة بتحترم عزل الفروع
 * تلقائياً لأن الموديلات عليها ScopedToBranch.
 *
 * الأرقام المالية مفصولة بالعملة — مافيش جمع بين عملات مختلفة.
 */
class DashboardService
{
    /** عدد الشهور في الرسم البياني للاتجاه. */
    private const TREND_MONTHS = 6;

    /** سقف الشهور المعروضة عشان الرسم مايبقاش مزدحم. */
    private const MAX_TREND_MONTHS = 24;

    private DashboardFilter $filter;

    public function __construct(
        private ?CarbonImmutable $now = null,
    ) {
        $this->now    = $now ?? CarbonImmutable::now();
        $this->filter = DashboardFilter::default($this->now);
    }

    /** تطبيق فلاتر المستخدم. بترجّع نسخة عشان الخدمة تفضل قابلة للتسلسل. */
    public function withFilter(DashboardFilter $filter): self
    {
        $clone = clone $this;
        $clone->filter = $filter;

        return $clone;
    }

    public function filter(): DashboardFilter
    {
        return $this->filter;
    }

    /** كل بيانات اللوحة في نداء واحد. */
    public function payload(): array
    {
        return [
            'period'        => $this->period(),
            'filter'        => $this->filter->toArray(),
            'financial'     => $this->financialSummary(),
            'hr'            => $this->hrSummary(),
            'pending'       => $this->pendingActions(),
            'trend'         => $this->monthlyTrend(),
            'expenseSplit'  => $this->expensesByCategory(),
            'salesChannels' => $this->salesByChannel(),
            'topCustomers'  => $this->topCustomers(),
            'alerts'        => $this->alerts(),
        ];
    }

    /**
     * الأجزاء اللي بتتحدّث عبر AJAX بس — من غير العدادات الثابتة.
     * ده اللي بيترجع لما المستخدم يغيّر الفلتر.
     */
    public function filteredPayload(): array
    {
        return [
            'filter'        => $this->filter->toArray(),
            'financial'     => $this->financialSummary(),
            'trend'         => $this->monthlyTrend(),
            'expenseSplit'  => $this->expensesByCategory(),
            'salesChannels' => $this->salesByChannel(),
            'topCustomers'  => $this->topCustomers(),
        ];
    }

    private function period(): array
    {
        return [
            'month' => $this->now->month,
            'year'  => $this->now->year,
            'label' => $this->filter->label(),
        ];
    }

    /**
     * تطبيق فلتر الفرع والعملة على أي استعلام.
     *
     * الفرع اختياري لأن بعض السجلات (زي الاشتراكات العامة) مالهاش
     * فرع، والعملة بتتفلتر بس لما المستخدم يطلب عملة بعينها.
     */
    private function applyScope(mixed $query, string $table, bool $withCurrency = true): mixed
    {
        if ($this->filter->hasBranch()) {
            $query->where($table . '.branch_id', $this->filter->branchId);
        }

        if ($withCurrency && $this->filter->hasCurrency()) {
            $query->where($table . '.currency', $this->filter->currency);
        }

        return $query;
    }

    // ========================================================
    //  المالية
    // ========================================================

    /**
     * الإيرادات والمصروفات للشهر الحالي، مفصولة بالعملة.
     */
    private function financialSummary(): array
    {
        [$start, $end] = $this->monthBounds();

        $sales = $this->applyScope(
                Sale::whereBetween('sale_date', [$start, $end]), 'sales'
            )
            ->groupBy('currency')
            ->selectRaw('currency, SUM(total_amount) as total, SUM(due_amount) as due, COUNT(*) as count')
            ->get()
            ->keyBy('currency');

        $expenses = $this->applyScope(
                Expense::whereBetween('expense_date', [$start, $end]), 'expenses'
            )
            ->groupBy('currency')
            ->selectRaw('currency, SUM(amount) as total')
            ->get()
            ->pluck('total', 'currency');

        $currencies = $sales->keys()->merge($expenses->keys())->unique()->values();

        $rows = [];

        foreach ($currencies as $currency) {
            $revenue = (float) ($sales[$currency]->total ?? 0);
            $spent   = (float) ($expenses[$currency] ?? 0);

            $rows[] = [
                'currency'  => $currency,
                'revenue'   => $revenue,
                'expenses'  => $spent,
                'net'       => round($revenue - $spent, 2),
                'due'       => (float) ($sales[$currency]->due ?? 0),
                'invoices'  => (int) ($sales[$currency]->count ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * اتجاه الإيرادات والمصروفات لآخر 6 شهور.
     *
     * بنستخدم العملة الأكثر استخداماً عشان الرسم يبقى له معنى —
     * جمع عملات مختلفة في خط واحد رقم مضلّل.
     */
    private function monthlyTrend(): array
    {
        [$start, $end] = $this->filter->bounds();
        $start = $start->startOfMonth();

        $months = min($this->filter->monthsInRange(), self::MAX_TREND_MONTHS);

        $saleMonth    = $this->monthExpression('sale_date');
        $expenseMonth = $this->monthExpression('expense_date');

        // كل العملات مش العملة الغالبة بس. كل عملة بتاخد خطها الخاص
        // عشان مانجمعش عملات مختلفة في رقم واحد.
        $sales = $this->applyScope(
                Sale::whereBetween('sale_date', [$start, $end]), 'sales'
            )
            ->groupBy(DB::raw($saleMonth), 'currency')
            ->selectRaw("{$saleMonth} as ym, currency, SUM(total_amount) as total")
            ->get();

        $expenses = $this->applyScope(
                Expense::whereBetween('expense_date', [$start, $end]), 'expenses'
            )
            ->groupBy(DB::raw($expenseMonth), 'currency')
            ->selectRaw("{$expenseMonth} as ym, currency, SUM(amount) as total")
            ->get();

        $labels = [];
        $keys   = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month    = $end->subMonths($i);
            $keys[]   = $month->format('Y-m');
            $labels[] = $month->format('m/Y');
        }

        $currencies = $sales->pluck('currency')
            ->merge($expenses->pluck('currency'))
            ->filter()
            ->unique()
            ->values();

        $salesMap    = $sales->groupBy('currency');
        $expensesMap = $expenses->groupBy('currency');

        $series = [];

        foreach ($currencies as $currency) {
            $sMonths = $salesMap->get($currency, collect())->pluck('total', 'ym');
            $eMonths = $expensesMap->get($currency, collect())->pluck('total', 'ym');

            $series[] = [
                'currency' => $currency,
                'revenue'  => array_map(fn ($k) => round((float) ($sMonths[$k] ?? 0), 2), $keys),
                'expenses' => array_map(fn ($k) => round((float) ($eMonths[$k] ?? 0), 2), $keys),
            ];
        }

        return [
            'labels' => $labels,
            'series' => $series,
            // العملة الافتراضية المعروضة أول ما الرسم يفتح
            'currency' => $this->filter->hasCurrency()
                ? $this->filter->currency
                : ($currencies->first() ?? $this->dominantCurrency()),
        ];
    }

    /**
     * توزيع المصروفات على التصنيفات — لكل عملة مجموعتها.
     *
     * كان بيرجّع عملة واحدة بس، فباقي العملات كانت بتختفي تماماً
     * من اللوحة. دلوقتي بيرجّع كل العملات والواجهة بتبدّل بينها.
     */
    private function expensesByCategory(): array
    {
        [$start, $end] = $this->monthBounds();

        $rows = $this->applyScope(
                Expense::whereBetween('expense_date', [$start, $end]), 'expenses'
            )
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->groupBy('expenses.currency', 'expense_categories.name')
            ->selectRaw('expenses.currency as currency, expense_categories.name as label, SUM(expenses.amount) as total')
            ->orderByDesc('total')
            ->get();

        return $this->groupRowsByCurrency($rows, 'expenses', 'expense_date');
    }

    /** المبيعات حسب القناة — لكل عملة مجموعتها. */
    private function salesByChannel(): array
    {
        [$start, $end] = $this->monthBounds();

        $rows = $this->applyScope(
                Sale::whereBetween('sale_date', [$start, $end]), 'sales'
            )
            ->leftJoin('sales_channels', 'sales_channels.id', '=', 'sales.sales_channel_id')
            ->groupBy('sales.currency', 'sales_channels.id', 'sales_channels.name')
            ->selectRaw('sales.currency as currency, sales_channels.id as channel_id, COALESCE(sales_channels.name, ?) as label, SUM(sales.total_amount) as total, COUNT(*) as invoices', [__('sale.direct_customer')])
            ->orderByDesc('total')
            ->get();

        return $this->groupRowsByCurrency($rows, 'sales', 'sale_date', true);
    }

    /** أعلى العملاء تعاملاً في الشهر. */
    private function topCustomers(): array
    {
        [$start, $end] = $this->monthBounds();

        return $this->applyScope(
                Sale::whereBetween('sale_date', [$start, $end]), 'sales'
            )
            ->whereNotNull('customer_id')
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->groupBy('customers.id', 'customers.name')
            ->selectRaw('customers.name as label, SUM(sales.total_amount) as total, COUNT(*) as invoices')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'label'    => $row->label,
                'total'    => round((float) $row->total, 2),
                'invoices' => (int) $row->invoices,
            ])
            ->all();
    }

    // ========================================================
    //  الموارد البشرية
    // ========================================================

    private function hrSummary(): array
    {
        $byStatus = Employee::groupBy('status')
            ->selectRaw('status, COUNT(*) as count')
            ->pluck('count', 'status');

        [$start, $end] = $this->monthBounds();

        return [
            'active'      => (int) ($byStatus['active'] ?? 0),
            'on_leave'    => (int) ($byStatus['on_leave'] ?? 0),
            'terminated'  => (int) ($byStatus['terminated'] ?? 0),
            'total'       => (int) $byStatus->sum(),
            'new_hires'   => $this->applyScope(
                Employee::whereBetween('hire_date', [$start, $end]), 'employees', false
            )->count(),
            'by_branch'   => $this->employeesByBranch(),
        ];
    }

    /**
     * الموظفون لكل فرع: النشطون ومن هم في إجازة.
     * استعلام واحد يغطي الحالتين — الفهرس (status, branch_id) موجود.
     */
    private function employeesByBranch(): array
    {
        return Employee::whereIn('employees.status', ['active', 'on_leave'])
            ->join('branches', 'branches.id', '=', 'employees.branch_id')
            ->groupBy('branches.name')
            ->selectRaw('branches.name as label')
            ->selectRaw("SUM(CASE WHEN employees.status = 'active' THEN 1 ELSE 0 END) as active_total")
            ->selectRaw("SUM(CASE WHEN employees.status = 'on_leave' THEN 1 ELSE 0 END) as on_leave_total")
            ->selectRaw('COUNT(*) as total')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label'    => $row->label,
                'active'   => (int) $row->active_total,
                'on_leave' => (int) $row->on_leave_total,
                'total'    => (int) $row->total,
            ])
            ->all();
    }

    // ========================================================
    //  ما يحتاج إجراءً
    // ========================================================

    /**
     * الطلبات المعلّقة — ده اللي المدير بيفتح اللوحة عشانه.
     */
    private function pendingActions(): array
    {
        return [
            'leaves'      => EmployeeLeave::where('status', LeaveStatus::Pending->value)->count(),
            'advances'    => EmployeeAdvance::where('status', AdvanceStatus::Pending->value)->count(),
            'penalties'   => EmployeePenalty::where('status', ApprovalStatus::Pending->value)->count(),
            'rewards'     => EmployeeReward::where('status', RewardStatus::Pending->value)->count(),
            'payrolls'    => Payroll::where('status', PayrollStatus::Draft->value)->count(),
            'unread_notifications' => admin()
                ? AdminNotification::forAdmin(admin()->id)->unread()->count()
                : 0,
        ];
    }

    /** تنبيهات تحتاج انتباهاً: فواتير متأخرة واشتراكات قاربت. */
    private function alerts(): array
    {
        return [
            'overdue_invoices' => Sale::overdue()->count(),

            'due_subscriptions' => VendorSubscription::where('status', 'active')
                ->whereNotNull('next_billing_date')
                ->whereBetween('next_billing_date', [
                    $this->now->startOfDay(),
                    $this->now->addDays(7)->endOfDay(),
                ])
                ->count(),
        ];
    }

    // ========================================================
    //  مساعدات
    // ========================================================

    /**
     * تعبير SQL بيرجّع الشهر بصيغة YYYY-MM حسب قاعدة البيانات.
     *
     * MySQL بتستخدم DATE_FORMAT و SQLite بتستخدم strftime — من غير
     * الفصل ده الاستعلام بيقع في بيئة الاختبارات.
     */
    private function monthExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function monthBounds(): array
    {
        return $this->filter->bounds();
    }

    /**
     * بيحوّل صفوف فيها عمود عملة لشكل: مجموعة لكل عملة + العملة
     * الافتراضية المعروضة. أقصى 6 بنود لكل عملة عشان الرسم يفضل
     * مقروء.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     */
    private function groupRowsByCurrency($rows, string $table, string $dateColumn, bool $withChannel = false): array
    {
        $groups = [];

        foreach ($rows->groupBy('currency') as $currency => $items) {
            if ($currency === null || $currency === '') {
                continue;
            }

            $groups[] = [
                'currency' => $currency,
                'total'    => round((float) $items->sum('total'), 2),
                'rows'     => $items->take(6)->map(function ($row) use ($withChannel) {
                    $out = [
                        'label' => $row->label,
                        'total' => round((float) $row->total, 2),
                    ];

                    if ($withChannel) {
                        $out['invoices']   = (int) $row->invoices;
                        $out['channel_id'] = $row->channel_id;
                    }

                    return $out;
                })->values()->all(),
            ];
        }

        // الأكبر قيمة يبقى المعروض الأول
        usort($groups, fn ($a, $b) => $b['total'] <=> $a['total']);

        $default = $this->filter->hasCurrency()
            ? $this->filter->currency
            : ($groups[0]['currency'] ?? $this->resolvedCurrency($table, $dateColumn));

        return [
            'currency' => $default,
            'groups'   => $groups,
            // توافق للخلف: صفوف العملة المعروضة
            'rows'     => collect($groups)->firstWhere('currency', $default)['rows'] ?? [],
        ];
    }

    /**
     * العملة اللي الرسم هيتبني عليها: اختيار المستخدم لو موجود،
     * وإلا الأكثر استخداماً في نفس الفترة المفلترة.
     */
    private function resolvedCurrency(string $table, string $dateColumn): string
    {
        if ($this->filter->hasCurrency()) {
            return $this->filter->currency;
        }

        [$start, $end] = $this->filter->bounds();

        $model = $table === 'sales' ? Sale::query() : Expense::query();

        $found = $this->applyScope(
                $model->whereBetween($table . '.' . $dateColumn, [$start, $end]), $table, false
            )
            ->groupBy($table . '.currency')
            ->selectRaw($table . '.currency as currency, COUNT(*) as c')
            ->orderByDesc('c')
            ->value('currency');

        return $found ?? $this->dominantCurrency();
    }

    /** العملات المتاحة في الفترة — عشان الواجهة تبني قائمة الاختيار. */
    public function availableCurrencies(): array
    {
        [$start, $end] = $this->filter->bounds();

        $sales = Sale::whereBetween('sale_date', [$start, $end])
            ->distinct()->pluck('currency');

        $expenses = Expense::whereBetween('expense_date', [$start, $end])
            ->distinct()->pluck('currency');

        return $sales->merge($expenses)->filter()->unique()->sort()->values()->all();
    }

    /**
     * العملة الأكثر استخداماً في المبيعات — للرسوم اللي بتحتاج
     * خط واحد. جمع عملات مختلفة في رقم واحد بيدي نتيجة مضلّلة.
     */
    private function dominantCurrency(): string
    {
        return Sale::groupBy('currency')
            ->selectRaw('currency, COUNT(*) as c')
            ->orderByDesc('c')
            ->value('currency')
            ?? 'EGP';
    }
}
