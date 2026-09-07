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

        $balances = PettyCashBalance::with('branch:id,name,currency')
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

    /** فواتير تجاوزت موعد السداد ولسه عليها مبلغ. */
    private function overdueInvoices(NotificationService $notifier): int
    {
        $invoices = Sale::acrossAllBranches()
            ->with('customer:id,name')
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->where('due_amount', '>', 0)
            ->whereDate('sale_date', '<', now()->subDays(30)->toDateString())
            ->get();

        $this->line("فواتير متأخرة: <fg=cyan>{$invoices->count()}</>");
        $count = 0;

        foreach ($invoices as $invoice) {
            if ($this->alreadySentToday(NotificationType::InvoiceOverdue, 'sale_id', $invoice->id)) {
                continue;
            }

            if (!$this->dryRun) {
                $notifier->notifyEligible(
                    type: NotificationType::InvoiceOverdue,
                    title: __('notification.messages.invoice_overdue.title'),
                    body: __('notification.messages.invoice_overdue.body', [
                        'invoice'  => $invoice->invoice_number,
                        'customer' => $invoice->customer?->name ?? '-',
                        'amount'   => number_format((float) $invoice->due_amount, 2) . ' ' . $invoice->currency,
                    ]),
                    url: route('admin.sales.show', $invoice->id),
                    branchId: $invoice->branch_id,
                    data: ['sale_id' => $invoice->id],
                );
            }

            $this->line("   • {$invoice->invoice_number} — " . number_format((float) $invoice->due_amount, 2));
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

        return AdminNotification::where('type', $type->value)
            ->whereDate('created_at', today())
            ->where('data->' . $key, $value)
            ->exists();
    }
}
