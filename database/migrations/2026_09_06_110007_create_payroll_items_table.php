<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('allowances', 10, 2)->default(0);
            $table->json('allowance_details')->nullable();
            $table->decimal('deductions', 10, 2)->default(0);
            $table->json('deduction_details')->nullable();
            $table->decimal('net_salary', 10, 2)->default(0);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->enum('payment_method', ['cash', 'bank_transfer'])->default('bank_transfer');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payroll_id', 'employee_id']);
            $table->index(['payroll_id', 'status'], 'payroll_items_payroll_id_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
