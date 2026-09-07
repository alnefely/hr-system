<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->integer('installments_count')->default(1);
            $table->decimal('monthly_installment', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->decimal('waived_amount', 10, 2)->default(0.00);
            $table->decimal('remaining_amount', 10, 2);
            $table->date('advance_date');
            $table->date('start_date');
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status']);
            $table->index('start_date', 'employee_advances_start_date_idx');
        });

        // سجل حركات السداد والإعفاء — المصدر الوحيد لحساب المسدد والمتبقي
        Schema::create('employee_advance_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_advance_id')->constrained('employee_advances')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('repayment_date');
            $table->enum('type', ['payroll_deduction', 'manual_payment', 'manager_waiver'])->default('manual_payment');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_advance_repayments');
        Schema::dropIfExists('employee_advances');
    }
};
