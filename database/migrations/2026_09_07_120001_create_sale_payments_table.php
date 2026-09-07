<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sale_payments')) {
            Schema::create('sale_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
                $table->date('payment_date');
                $table->decimal('amount', 12, 2);
                $table->string('payment_method', 50)->default('cash');
                $table->string('reference_number', 100)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['sale_id', 'payment_date'], 'sale_payments_sale_date_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
