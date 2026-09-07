<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('expense_category_id')->constrained('expense_categories')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('EGP');
            $table->date('expense_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'card'])->default('cash');
            $table->string('invoice_number')->nullable();
            $table->string('attachment')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // فهارس الفلترة: التاريخ والفرع هما الأكثر استخداماً في الشاشات
            $table->index(['expense_date', 'branch_id'], 'expenses_expense_date_branch_id_idx');
            $table->index(['expense_category_id', 'expense_date'], 'expenses_category_date_idx');
            $table->index('currency', 'expenses_currency_idx');
            $table->index('payment_method', 'expenses_payment_method_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
