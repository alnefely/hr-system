<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('leave_type', ['annual', 'casual', 'sick', 'unpaid'])->default('annual');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days_count', 5, 2)->default(1.00);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('attachment', 1000)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status'], 'employee_leaves_employee_id_status_idx');
            $table->index(['leave_type', 'start_date'], 'employee_leaves_type_start_idx');
            $table->index('start_date', 'employee_leaves_start_date_idx');
            // عدّاد الطلبات المعلّقة في اللوحة: WHERE status = 'pending'
            $table->index('status', 'employee_leaves_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_leaves');
    }
};
