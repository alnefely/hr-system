<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * المكافآت والبدلات الاستثنائية.
 *
 * بتختلف عن employee_allowances: دي مبالغ لمرة واحدة مرتبطة بتاريخ
 * محدد وبتدخل مسير الشهر اللي فيه تاريخها، والبدلات ثابتة شهرياً.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('reward_type', ['bonus', 'incentive', 'allowance', 'appreciation'])->default('bonus');
            $table->string('title');
            $table->decimal('amount', 10, 2);
            $table->date('reward_date');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('approved');
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'reward_date']);
            // عدّاد الطلبات المعلّقة في اللوحة: WHERE status = 'pending'
            $table->index('status', 'employee_rewards_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_rewards');
    }
};
