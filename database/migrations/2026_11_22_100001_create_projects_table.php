<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * المشاريع — حاوية لمجموعة مهام وأعضاء.
 *
 * - مربوط بفرع (عزل البيانات)، والأعضاء ممكن يكونوا من فروع تانية.
 * - progress عمود مخزّن (مش محسوب في كل طلب): بيتحدّث لما مهمة في
 *   المشروع تتغيّر، عشان القوائم والتقارير ماتعملش تجميع على كل صف.
 * - creator: مشرف أو موظف (morph) لأن الاتنين يقدروا ينشئوا.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name', 191);
            $table->text('description')->nullable();
            $table->string('color', 20)->nullable();

            // مدير المشروع (موظف)
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->enum('status', ['planning', 'active', 'on_hold', 'completed', 'cancelled'])->default('planning');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');

            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();

            // نسبة الإنجاز المخزّنة (0-100) + عدادات للقوائم من غير تجميع
            $table->unsignedTinyInteger('progress')->default(0);
            $table->unsignedInteger('tasks_count')->default(0);
            $table->unsignedInteger('completed_tasks_count')->default(0);

            $table->foreignId('template_id')->nullable()->constrained('project_templates')->nullOnDelete();
            $table->nullableMorphs('creator');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status'], 'projects_branch_status_idx');
            $table->index(['manager_id', 'status'], 'projects_manager_status_idx');
            $table->index('due_date', 'projects_due_date_idx');
        });

        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('role', ['manager', 'member', 'viewer'])->default('member');
            $table->timestamps();

            $table->unique(['project_id', 'employee_id'], 'project_members_unique');
            // "المشاريع اللي أنا عضو فيها" في البوابة
            $table->index('employee_id', 'project_members_employee_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('projects');
    }
};
