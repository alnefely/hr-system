<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * سلامة أرقام الفاتورة: الإجمالي من البنود، مفيش تعديل ينزّله تحت
 * المدفوع، ومفيش دفعة تعدّي المتبقي.
 */
class SaleIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'HQ', 'code' => 'HQ', 'country' => 'Egypt',
            'currency' => 'EGP', 'is_active' => true,
        ]);

        $role = Role::create(['name' => 'Super', 'roles' => all_roles()]);
        $this->actingAs(Admin::create([
            'name' => 'Root', 'email' => 'root@integrity.test',
            'password' => bcrypt('password'), 'main' => true, 'role_id' => $role->id,
        ]), 'admin');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'invoice_number' => 'INV-I-0001', 'sale_type' => 'direct_sale',
            'branch_id' => $this->branch->id, 'sale_date' => '2026-11-10',
            'payment_status' => 'unpaid', 'payment_method' => 'cash',
            'tax_amount' => 0, 'discount_amount' => 0,
            'items' => [
                ['item_name' => 'A', 'quantity' => 2, 'unit_price' => 100, 'total_price' => 9999],
                ['item_name' => 'B', 'quantity' => 1, 'unit_price' => 50, 'total_price' => 9999],
                ['item_name' => '', 'quantity' => 5, 'unit_price' => 5],
            ],
            'subtotal' => 1, 'total_amount' => 1,
        ], $overrides);
    }

    private function createSale(array $overrides = []): Sale
    {
        $this->post(route('admin.sales.store'), $this->payload($overrides))->assertSessionHasNoErrors();

        return Sale::where('invoice_number', $overrides['invoice_number'] ?? 'INV-I-0001')->firstOrFail();
    }

    /** البنود بتتحفظ في INSERT واحد، وإجمالي كل بند محسوب على السيرفر. */
    public function test_items_are_inserted_in_one_query_with_server_totals(): void
    {
        DB::enableQueryLog();
        $sale = $this->createSale();
        $inserts = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_starts_with(strtolower($q['query']), 'insert into "sale_items"'));
        DB::disableQueryLog();

        $this->assertCount(1, $inserts, 'البنود لسه بتتحفظ بند بند');
        $this->assertSame(2, $sale->items()->count(), 'البند اللي من غير اسم اتحفظ');
        $this->assertEquals([200.0, 50.0], $sale->items()->orderBy('id')->pluck('total_price')->map(fn ($v) => (float) $v)->all());
        $this->assertEquals(250, (float) $sale->total_amount);
    }

    /** تعديل الفاتورة لإجمالي أقل من المدفوع بيترفض. */
    public function test_update_cannot_lower_total_below_paid_amount(): void
    {
        $sale = $this->createSale();

        $this->post(route('admin.sales.payments.store', $sale), [
            'payment_date' => '2026-11-11', 'amount' => 200, 'payment_method' => 'cash',
        ])->assertSessionHas('success');

        $this->put(route('admin.sales.update', $sale), $this->payload([
            'items' => [['item_name' => 'A', 'quantity' => 1, 'unit_price' => 100]],
        ]))->assertSessionHasErrors('total_amount');

        $sale->refresh();
        $this->assertEquals(250, (float) $sale->total_amount, 'الإجمالي اتغيّر رغم الرفض');
        $this->assertSame(2, $sale->items()->count(), 'البنود اتمسحت رغم الرفض');
    }

    /** التعديل لإجمالي أكبر من أو يساوي المدفوع مسموح ويعيد حساب المتبقي. */
    public function test_update_above_paid_amount_is_allowed(): void
    {
        $sale = $this->createSale();

        $this->post(route('admin.sales.payments.store', $sale), [
            'payment_date' => '2026-11-11', 'amount' => 100, 'payment_method' => 'cash',
        ]);

        $this->put(route('admin.sales.update', $sale), $this->payload([
            'items' => [['item_name' => 'A', 'quantity' => 3, 'unit_price' => 100]],
        ]))->assertSessionHasNoErrors();

        $sale->refresh();
        $this->assertEquals(300, (float) $sale->total_amount);
        $this->assertEquals(200, (float) $sale->due_amount);
        $this->assertSame('partially_paid', $sale->payment_status);
    }

    /**
     * الدفعة بتتشيّك على المتبقي المقروء من الصف المقفول — مش من نسخة
     * الفاتورة اللي اتحمّلت مع الطلب (اللي ممكن تكون قديمة).
     */
    public function test_payment_is_checked_against_fresh_due_amount(): void
    {
        $sale = $this->createSale();

        // دفعة اتسجّلت من طلب تاني بعد ما الفاتورة اتحمّلت
        SalePayment::create([
            'sale_id' => $sale->id, 'payment_date' => '2026-11-11',
            'amount' => 200, 'payment_method' => 'cash',
        ]);
        $sale->recalculatePayments();

        $this->post(route('admin.sales.payments.store', $sale), [
            'payment_date' => '2026-11-12', 'amount' => 100, 'payment_method' => 'cash',
        ])->assertSessionHas('error');

        $this->assertEquals(200, (float) $sale->payments()->sum('amount'));
        $this->assertEquals(50, (float) $sale->fresh()->due_amount);
    }

    public function test_payment_within_due_amount_is_recorded(): void
    {
        $sale = $this->createSale();

        $this->post(route('admin.sales.payments.store', $sale), [
            'payment_date' => '2026-11-12', 'amount' => 250, 'payment_method' => 'cash',
        ])->assertSessionHas('success');

        $sale->refresh();
        $this->assertEquals(0, (float) $sale->due_amount);
        $this->assertSame('paid', $sale->payment_status);
    }

    /** فلتر التاريخ شامل يوم النهاية (نطاق بدل whereDate). */
    public function test_date_filter_includes_end_day(): void
    {
        $this->createSale(['invoice_number' => 'INV-I-0001', 'sale_date' => '2026-11-10']);
        $this->createSale(['invoice_number' => 'INV-I-0002', 'sale_date' => '2026-11-11']);

        $ids = Sale::query()->betweenDays('2026-11-10', '2026-11-10')->pluck('invoice_number')->all();
        $this->assertSame(['INV-I-0001'], $ids);

        $this->assertSame(2, Sale::query()->betweenDays('2026-11-10', '2026-11-11')->count());
        $this->assertSame(2, Sale::query()->betweenDays('not-a-date', null)->count(), 'التاريخ الغلط المفروض يتجاهل');
    }
}
