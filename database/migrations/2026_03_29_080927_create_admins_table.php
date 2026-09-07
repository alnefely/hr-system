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
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->nullable();
            $table->string('email', 100)->unique();
            $table->boolean('main')->default(0);
            $table->string('img', 200)->nullable();
            
            $table->foreignId('role_id')->nullable()->constrained('roles')->onUpdate('cascade')->onDelete('set null');

            // العمود هنا من غير قيد أجنبي لأن جدول employees لسه ماتعملش.
            // القيد بيتضاف في create_employees_table بعد ما الجدولين يبقوا موجودين.
            $table->unsignedBigInteger('employee_id')->nullable();
            
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
