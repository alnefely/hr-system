<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_subscriptions')) {
            Schema::create('vendor_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->string('title', 200);
                $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                
                $table->enum('type', ['subscription', 'contract', 'one_time'])->default('subscription');
                $table->enum('billing_cycle', ['monthly', 'quarterly', 'semi_annually', 'annually', 'one_time'])->default('monthly');
                $table->decimal('cost', 12, 2);
                $table->string('currency', 10)->default('EGP');
                
                $table->date('start_date');
                $table->date('next_billing_date')->nullable();
                $table->boolean('auto_renew')->default(true);
                $table->enum('status', ['active', 'pending', 'expired', 'cancelled'])->default('active');
                
                $table->string('attachment', 500)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'next_billing_date'], 'vendor_subscriptions_status_billing_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_subscriptions');
    }
};
