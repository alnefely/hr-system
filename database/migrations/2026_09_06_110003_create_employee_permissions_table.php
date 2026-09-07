<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('permission_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('hours_count', 4, 2)->default(1.00);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'permission_date'], 'employee_permissions_emp_date_idx');
            $table->index('status', 'employee_permissions_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_permissions');
    }
};
