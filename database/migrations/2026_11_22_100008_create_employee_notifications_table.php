<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إشعارات بوابة الموظف — نفس فكرة admin_notifications بس للموظف.
 *
 * منفصل عن جدول المشرفين لأن المستلم موديل مختلف (employees) والبوابة
 * ليها جلسة مستقلة؛ الفهارس على نفس أنماط الاستعلام.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('type', 60);
            $table->string('title', 191);
            $table->text('body')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('icon', 60)->nullable();
            $table->string('level', 20)->default('info');
            $table->json('data')->nullable();
            $table->nullableMorphs('causer');
            $table->string('causer_name', 191)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'read_at'], 'employee_notifications_employee_read_idx');
            $table->index(['type', 'created_at'], 'employee_notifications_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_notifications');
    }
};
