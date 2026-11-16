<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Role;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * مبالغ الفاتورة بتتحسب على السيرفر من الكمية × السعر،
 * مش بتتاخد جاهزة من المتصفح.
 */
class SaleTotalsServerSideTest extends TestCase
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
            'name' => 'Root', 'email' => 'root@test.com',
            'password' => bcrypt('password'), 'main' => true, 'role_id' => $role->id,
        ]), 'admin');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'invoice_number' => 'INV-T-0001', 'sale_type' => 'direct_sale',
            'branch_id' => $this->branch->id, 'sale_date' => '2026-11-10',
            'payment_status' => 'unpaid', 'payment_method' => 'cash',
            'tax_amount' => 14, 'discount_amount' => 4,
            'items' => [
                ['item_name' => 'Design', 'quantity' => 2, 'unit_price' => 50, 'total_price' => 1],
                ['item_name' => 'Hosting', 'quantity' => 1, 'unit_price' => 100, 'total_price' => 1],
            ],
            // أرقام متلاعب بيها من المتصفح
            'subtotal' => 1, 'total_amount' => 1,
        ], $overrides);
    }

    public function test_totals_are_recalculated_from_items(): void
    {
        $this->post(route('admin.sales.store'), $this->payload())->assertSessionHasNoErrors();

        $sale = Sale::where('invoice_number', 'INV-T-0001')->firstOrFail();

        $this->assertEquals(200, (float) $sale->subtotal);
        $this->assertEquals(210, (float) $sale->total_amount);   // 200 + 14 − 4
        $this->assertEquals(210, (float) $sale->due_amount);
        $this->assertEquals([100.0, 100.0], $sale->items()->orderBy('id')->pluck('total_price')->map(fn ($v) => (float) $v)->all());
    }

    public function test_sale_without_items_keeps_entered_subtotal(): void
    {
        $this->post(route('admin.sales.store'), $this->payload([
            'items' => [], 'subtotal' => 500, 'tax_amount' => 0, 'discount_amount' => 0, 'total_amount' => 1,
        ]))->assertSessionHasNoErrors();

        $this->assertEquals(500, (float) Sale::where('invoice_number', 'INV-T-0001')->value('total_amount'));
    }
}
