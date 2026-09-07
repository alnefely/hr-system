<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number', 50)->unique();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->enum('sale_type', ['external_project', 'app_service', 'direct_sale'])->default('direct_sale');
                $table->string('sales_channel', 100)->nullable();
                $table->foreignId('sales_channel_id')->nullable()->constrained('sales_channels')->nullOnDelete();
                $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
                $table->date('sale_date');
                
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax_amount', 12, 2)->default(0);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->string('currency', 10)->default('EGP');
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('due_amount', 12, 2)->default(0);
                
                $table->enum('payment_status', ['paid', 'partially_paid', 'unpaid'])->default('paid');
                $table->enum('payment_method', ['bank_transfer', 'cash', 'card', 'cheque'])->default('cash');
                $table->string('attachment', 500)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                // فهارس الفلترة الأكثر استخداماً في القوائم والتقارير
                $table->index(['sale_date', 'branch_id'], 'sales_sale_date_branch_id_idx');
                $table->index(['payment_status', 'sale_date'], 'sales_payment_status_date_idx');
                $table->index('currency', 'sales_currency_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
