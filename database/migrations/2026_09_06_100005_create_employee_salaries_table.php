<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onUpdate('cascade')->onDelete('cascade');
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->string('currency', 10)->default('EGP');
            $table->date('effective_date');
            $table->enum('change_reason', ['hiring', 'promotion', 'annual_raise', 'settlement', 'other'])->default('hiring');
            $table->foreignId('approved_by')->nullable()->constrained('admins')->onUpdate('cascade')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'effective_date'], 'employee_salaries_employee_effective_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
    }
};
