<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\Branch;
use App\Models\Role;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تنبيه الفواتير المتأخرة: ملخّص واحد لكل فرع في اليوم، مش إشعار لكل فاتورة.
 */
class OverdueInvoicesAlertTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;
    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $role = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $this->admin = Admin::create([
            'name' => 'Root', 'email' => 'root@test.com',
            'password' => bcrypt('password'), 'main' => true, 'role_id' => $role->id,
        ]);
    }

    private function sale(string $number, string $date, float $due, string $status = 'unpaid'): Sale
    {
        return Sale::create([
            'invoice_number' => $number, 'sale_type' => 'direct_sale',
            'branch_id' => $this->branch->id, 'sale_date' => $date,
            'subtotal' => 1000, 'total_amount' => 1000, 'currency' => 'EGP',
            'paid_amount' => 1000 - $due, 'due_amount' => $due,
            'payment_status' => $status, 'payment_method' => 'cash',
        ]);
    }

    private function overdueNotifications()
    {
        return AdminNotification::where('type', NotificationType::InvoiceOverdue->value)->get();
    }

    public function test_one_summary_per_branch_instead_of_one_per_invoice(): void
    {
        $old = now()->subDays(45)->toDateString();

        $this->sale('INV-1', $old, 300);
        $this->sale('INV-2', $old, 200, 'partially_paid');
        $this->sale('INV-3', $old, 500);
        $this->sale('INV-NEW', now()->subDays(5)->toDateString(), 900); // لسه مش متأخرة
        $this->sale('INV-PAID', $old, 0, 'paid');                       // مدفوعة

        $this->artisan('alerts:send')->assertSuccessful();

        $notifications = $this->overdueNotifications()->where('admin_id', $this->admin->id);

        $this->assertCount(1, $notifications, 'لازم إشعار واحد للمشرف مش إشعار لكل فاتورة');
        $this->assertStringContainsString('3', $notifications->first()->body);
        $this->assertStringContainsString('1,000.00 EGP', $notifications->first()->body);
        $this->assertStringContainsString('overdue=1', $notifications->first()->url);
    }

    public function test_summary_is_not_repeated_on_the_same_day(): void
    {
        $this->sale('INV-1', now()->subDays(45)->toDateString(), 300);

        $this->artisan('alerts:send')->assertSuccessful();
        $this->artisan('alerts:send')->assertSuccessful();

        $this->assertCount(1, $this->overdueNotifications()->where('admin_id', $this->admin->id));
    }

    public function test_sales_list_overdue_filter_matches_the_alert(): void
    {
        $old = now()->subDays(45)->toDateString();
        $this->sale('INV-1', $old, 300);
        $this->sale('INV-NEW', now()->subDays(5)->toDateString(), 900);
        $this->sale('INV-PAID', $old, 0, 'paid');

        $this->assertSame(['INV-1'], Sale::overdue()->pluck('invoice_number')->all());
    }
}
