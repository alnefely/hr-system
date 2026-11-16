<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Models\AdminNotification;
use App\Models\Expense;
use App\Models\PettyCashBalance;
use App\Models\Sale;
use App\Models\VendorSubscription;
use App\Services\Notification\NotificationService;
use Illuminate\Console\Command;

/**
 * التنبيهات الدورية.
 *
 * بتشتغل مرة يومياً وبتفحص ثلاث حالات محتاجة تدخل بشري:
 *  - اشتراكات قربت تتجدد
 *  - عهد نقدية قربت تخلص
 *  - فواتير تجاوزت موعد السداد
 *
 * الأمر idempotent: مابيبعتش نفس التنبيه أكتر من مرة في اليوم،
 * عشان لو اتنفذ مرتين المستخدم مايتزنقش بإشعارات مكررة.
 */
class SendScheduledAlertsCommand extends Command
{
    protected $signature = 'alerts:send
        {--days=7 : عدد الأيام قبل التجديد اللي نبدأ ننبّه فيها}
        {--threshold=20 : نسبة المتبقي من العهدة اللي تحت منها نبعت تنبيه}
        {--dry-run : اعرض التنبيهات من غير ما تبعتها}';

    protected $description = 'إرسال التنبيهات الدورية (اشتراكات، عهد، فواتير متأخرة)';

    private bool $dryRun = false;

    public function handle(NotificationService $notifier): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        // نسخة الأمر ممكن تتستخدم تاني في نفس العملية (Artisan::call)،
        // فذاكرة "اتبعت النهارده" لازم تبدأ فاضية كل تشغيل.
        $this->sentToday = [];

        if ($this->dryRun) {
            $this->warn('وضع المعاينة — مفيش إشعارات هتتبعت فعلياً.');
            $this->newLine();
        }

        $sent = 0;
        $sent += $this->subscriptionsDueSoon($notifier);
        $sent += $this->lowPettyCash($notifier);
        $sent += $this->overdueInvoices($notifier);

        $this->newLine();
        $this->info("إجمالي التنبيهات: {$sent}");

        return self::SUCCESS;
    }

    /** اشتراكات مستحقة التجديد خلال المدة المحددة. */
    private function subscriptionsDueSoon(NotificationService $notifier): int
    {
        $days = (int) $this->option('days');
        $limit = now()->addDays($days)->endOfDay();

        $subscriptions = VendorSubscription::with('vendor:id,name')
            ->where('status', 'active')
            ->whereNotNull('next_billing_date')
            ->whereBetween('next_billing_date', [now()->startOfDay(), $limit])
            ->get();

        $this->line("اشتراكات تقترب من التجديد: <fg=cyan>{$subscriptions->count()}</>");
        $count = 0;

        foreach ($subscriptions as $subscription) {
            if ($this->alreadySentToday(NotificationType::SubscriptionDueSoon, 'subscription_id', $subscription->id)) {
                continue;
            }

            $remainingDays = (int) now()->startOfDay()->diffInDays($subscription->next_billing_date, false);

            if (!$this->dryRun) {
                $notifier->notifyEligible(
                    type: NotificationType::SubscriptionDueSoon,
                    title: __('notification.messages.subscription_due_soon.title'),
                    body: __('notification.messages.subscription_due_soon.body', [
                        'name'   => $subscription->title ?? $subscription->vendor?->name ?? '-',
                        'days'   => $remainingDays,
                        'amount' => number_format((float) $subscription->cost, 2) . ' ' . $subscription->currency,
                    ]),
                    url: route('admin.vendor-subscriptions.show', $subscription->id),
                    branchId: $subscription->branch_id,
                    data: ['subscription_id' => $subscription->id],
                );
            }

            $this->line("   • {$subscription->title} — بعد {$remainingDays} يوم");
            $count++;
        }

        return $count;
    }

    /** عهد نقدية المتبقي فيها أقل من النسبة المحددة. */
    private function lowPettyCash(NotificationService $notifier): int
    {
        $threshold = (float) $this->option('threshold');

        // صفوف بدون فرع (بقايا الترحيل القديم) مالهاش مصروف ولا اسم
        // فرع يتعرض في التنبيه — بنتجاهلها زي شاشة المصاريف.
        $balances = PettyCashBalance::with('branch:id,name,currency')
            ->whereNotNull('branch_id')
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->where('total_balance', '>', 0)
            ->get();

        // المصروف لكل فرع في استعلام واحد
        $spent = Expense::acrossAllBranches()
            ->inMonth(now()->year, now()->month)
            ->groupBy('branch_id')
            ->selectRaw('branch_id, SUM(amount) as total')
            ->pluck('total', 'branch_id');

        $count = 0;
        $low = [];

        foreach ($balances as $balance) {
            $total = (float) $balance->total_balance;
            $used = (float) ($spent[$balance->branch_id] ?? 0);
            $remaining = max(0, $total - $used);
            $percent = $total > 0 ? ($remaining / $total) * 100 : 0;

            if ($percent > $threshold) {
                continue;
            }

            if ($this->alreadySentToday(NotificationType::PettyCashLow, 'branch_id', $balance->branch_id)) {
                continue;
            }

            $low[] = $balance;

            if (!$this->dryRun) {
                $notifier->notifyEligible(
                    type: NotificationType::PettyCashLow,
                    title: __('notification.messages.petty_cash_low.title'),
                    body: __('notification.messages.petty_cash_low.body', [
                        'branch'    => $balance->branch?->name ?? __('expense.export.all_branches'),
                        'remaining' => number_format($remaining, 2) . ' ' . ($balance->branch?->currency ?? ''),
                    ]),
                    // الرابط بيحمل الفرع والشهر عشان الشاشة تفتح على السياق
                    // اللي التنبيه بيتكلم عنه، مش على العرض الافتراضي.
                    url: route('admin.expenses.index', [
                        'branch_id' => $balance->branch_id,
                        'year'      => $balance->year,
                        'month'     => $balance->month,
                    ]),
                    branchId: $balance->branch_id,
                    data: ['branch_id' => $balance->branch_id, 'remaining' => $remaining],
                );
            }

            $this->line("   • {$balance->branch?->name} — متبقي " . round($percent, 1) . '%');
            $count++;
        }

        $this->line('عهد نقدية منخفضة: <fg=cyan>' . count($low) . '</>');

        return $count;
    }

    /**
     * ملخّص يومي واحد لكل فرع بالفواتير المتأخرة.
     *
     * قبل كده كان بيبعت إشعار لكل فاتورة متأخرة كل يوم للأبد — 50 فاتورة
     * متأخرة = 50 إشعار يومياً لكل مشرف، وده بيدفن باقي الإشعارات.
     * الملخّص فيه العدد والمبلغ لكل عملة، ورابط للقائمة مفلترة على المتأخرات.
     */
    private function overdueInvoices(NotificationService $notifier): int
    {
        // التجميع في SQL: صف لكل (فرع، عملة) بدل تحميل كل الفواتير
        $rows = Sale::acrossAllBranches()
            ->overdue()
            ->groupBy('branch_id', 'currency')
            ->selectRaw('branch_id, currency, COUNT(*) as invoices, SUM(due_amount) as due')
            ->get()
            ->groupBy('branch_id');

        $total = $rows->flatten()->sum('invoices');
        $this->line("فواتير متأخرة: <fg=cyan>{$total}</> في <fg=cyan>{$rows->count()}</> فرع");
        $count = 0;

        foreach ($rows as $branchId => $byCurrency) {
            $branchId = $branchId === '' ? null : (int) $branchId;

            if ($this->alreadySentToday(NotificationType::InvoiceOverdue, 'overdue_branch_id', $branchId ?? 0)) {
                continue;
            }

            $invoices = (int) $byCurrency->sum('invoices');
            $amounts  = $byCurrency
                ->map(fn ($r) => number_format((float) $r->due, 2) . ' ' . $r->currency)
                ->implode(' + ');

            if (!$this->dryRun) {
                $notifier->notifyEligible(
                    type: NotificationType::InvoiceOverdue,
                    title: __('notification.messages.invoice_overdue_summary.title'),
                    body: __('notification.messages.invoice_overdue_summary.body', [
                        'count'  => $invoices,
                        'amount' => $amounts,
                        'days'   => Sale::OVERDUE_AFTER_DAYS,
                    ]),
                    url: route('admin.sales.index', array_filter([
                        'overdue'   => 1,
                        'branch_id' => $branchId,
                    ])),
                    branchId: $branchId,
                    data: ['overdue_branch_id' => $branchId ?? 0, 'invoices' => $invoices],
                );
            }

            $this->line("   • فرع #" . ($branchId ?? '-') . " — {$invoices} فاتورة ({$amounts})");
            $count++;
        }

        return $count;
    }

    /**
     * منع تكرار نفس التنبيه في نفس اليوم.
     *
     * بنقارن بمفتاح داخل data عشان التنبيه يبقى مرتبط بالكيان نفسه
     * مش بالنوع بس.
     */
    private function alreadySentToday(NotificationType $type, string $key, mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        // الكيانات اللي اتبعتلها النهارده بتتجاب مرة واحدة لكل نوع، بدل
        // استعلام JSON لكل فاتورة/اشتراك. ونطاق created_at صريح بدل
        // whereDate عشان الفهرس (type, created_at) يتستخدم.
        $cacheKey = $type->value . '|' . $key;

        $this->sentToday[$cacheKey] ??= AdminNotification::where('type', $type->value)
            ->where('created_at', '>=', today())
            ->pluck('data')
            ->map(fn ($data) => is_array($data) ? ($data[$key] ?? null) : null)
            ->filter(fn ($v) => $v !== null)
            ->mapWithKeys(fn ($v) => [(string) $v => true])
            ->all();

        return isset($this->sentToday[$cacheKey][(string) $value]);
    }

    /** @var array<string, array<string, true>> */
    private array $sentToday = [];
}
