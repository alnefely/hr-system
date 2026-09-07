<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_payments')) {
            Schema::create('vendor_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_subscription_id')->constrained('vendor_subscriptions')->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->date('payment_date');
                $table->enum('payment_method', ['bank_transfer', 'card', 'cash', 'cheque'])->default('bank_transfer');
                $table->string('transaction_reference', 100)->nullable();
                $table->string('invoice_number', 100)->nullable();
                $table->string('attachment', 500)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamps();

                $table->index('payment_date', 'vendor_payments_date_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payments');
    }
};
