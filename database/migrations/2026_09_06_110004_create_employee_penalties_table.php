<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('penalty_type', ['days_deduction', 'amount_deduction', 'warning'])->default('days_deduction');
            $table->decimal('days_count', 4, 2)->default(0.00);
            $table->decimal('deduction_amount', 10, 2)->default(0.00);
            $table->date('penalty_date');
            $table->text('reason');
            $table->text('notes')->nullable();

            // دورة الاعتماد: الجزاء خصم من راتب الموظف، وقانون العمل
            // بيتطلب تحقيق وموافقة موثّقة قبل الخصم. فالجزاء بيتسجّل
            // مسودة ومايدخلش المسير غير بعد الاعتماد.
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'penalty_date'], 'employee_penalties_emp_date_idx');
            $table->index('status', 'employee_penalties_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_penalties');
    }
};
