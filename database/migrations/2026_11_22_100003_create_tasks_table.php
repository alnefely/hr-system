<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * المهام — قلب الموديول.
 *
 * - المهمة الفرعية = مهمة عادية ليها parent_id (نفس الجدول، نفس المميزات).
 * - project_id اختياري: المهام اليومية من غير مشروع.
 * - progress بيتحدّث يدوي (تحديثات دورية من الموظف) أو تلقائي من الخطوات
 *   أو المهام الفرعية حسب progress_source.
 * - progress_update_interval: الموظف مطلوب منه يحدّث النسبة كل فترة،
 *   و next_progress_update_at هو الموعد اللي بعده يتبعتله تذكير.
 * - النسبة المستهدفة (المفروض يكون وصل كام لحد النهاردة) بتتحسب من
 *   starts_at و due_at — مش محتاجة عمود.
 * - position: الترتيب جوّه عمود الحالة في لوحة كانبان.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('recurrence_id')->nullable()->constrained('task_recurrences')->nullOnDelete();

            $table->string('title', 255);
            $table->longText('description')->nullable();

            $table->enum('status', ['new', 'in_progress', 'in_review', 'completed', 'cancelled'])->default('new');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');

            $table->dateTime('starts_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // نسبة الإنجاز
            $table->unsignedTinyInteger('progress')->default(0);
            $table->enum('progress_source', ['manual', 'checklist', 'subtasks'])->default('manual');
            $table->enum('progress_update_interval', ['none', 'daily', 'weekly', 'custom'])->default('none');
            $table->unsignedSmallInteger('progress_update_every_days')->nullable();
            $table->timestamp('last_progress_update_at')->nullable();
            $table->timestamp('next_progress_update_at')->nullable();

            // الوقت بالدقائق: المتوقع والمسجّل فعلاً (مخزّن من سجلات الوقت)
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->unsignedInteger('spent_minutes')->default(0);

            // المهمة محتاجة مراجعة قبل ما تتقفل
            $table->boolean('requires_review')->default(true);

            // عدادات مخزّنة للقوائم (بدل withCount على كل صف)
            $table->unsignedInteger('subtasks_count')->default(0);
            $table->unsignedInteger('completed_subtasks_count')->default(0);
            $table->unsignedInteger('checklist_count')->default(0);
            $table->unsignedInteger('completed_checklist_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('attachments_count')->default(0);

            $table->unsignedInteger('position')->default(0);
            $table->nullableMorphs('creator');
            $table->timestamp('last_activity_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // لوحة المهام المفتوحة: WHERE branch_id = ? AND status IN (...)
            $table->index(['branch_id', 'status', 'due_at'], 'tasks_branch_status_due_idx');
            $table->index(['project_id', 'status'], 'tasks_project_status_idx');
            $table->index(['parent_id', 'position'], 'tasks_parent_position_idx');
            $table->index(['status', 'due_at'], 'tasks_status_due_idx');
            $table->index(['status', 'position'], 'tasks_status_position_idx');
            // تذكيرات تحديث النسبة
            $table->index('next_progress_update_at', 'tasks_next_progress_idx');
            $table->index('recurrence_id', 'tasks_recurrence_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
