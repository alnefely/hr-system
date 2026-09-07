<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->integer('year')->default(date('Y'));
            $table->decimal('annual_balance', 5, 2)->default(21.00);
            $table->decimal('casual_balance', 5, 2)->default(7.00);
            $table->decimal('sick_balance', 5, 2)->default(14.00);
            $table->decimal('used_annual', 5, 2)->default(0.00);
            $table->decimal('used_casual', 5, 2)->default(0.00);
            $table->decimal('used_sick', 5, 2)->default(0.00);
            $table->decimal('used_unpaid', 5, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['employee_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_leave_balances');
    }
};
