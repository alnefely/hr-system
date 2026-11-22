<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * متابعة التنفيذ: تحديثات نسبة الإنجاز، المراجعات، وسجلات الوقت.
 */
return new class extends Migration
{
    public function up(): void
    {
        // كل مرة الموظف يقول "خلصت ٤٠٪" بيتسجل هنا مع ملاحظته
        Schema::create('task_progress_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->morphs('author');
            $table->unsignedTinyInteger('previous_progress')->default(0);
            $table->unsignedTinyInteger('progress');
            // النسبة المستهدفة وقت التحديث — عشان نقارن بعدين (متأخر/في الميعاد)
            $table->unsignedTinyInteger('expected_progress')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['task_id', 'created_at'], 'task_progress_task_created_idx');
        });

        // تسليم الموظف ← قرار المراجع (موافقة أو رفض بملاحظات)
        Schema::create('task_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->morphs('submitter');
            $table->text('submission_note')->nullable();
            $table->nullableMorphs('reviewer');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('decision_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'status'], 'task_reviews_task_status_idx');
            // "مهام مستنية مراجعتي"
            $table->index(['reviewer_type', 'reviewer_id', 'status'], 'task_reviews_reviewer_status_idx');
        });

        Schema::create('task_time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->timestamp('started_at');
            // null = العدّاد لسه شغّال
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('minutes')->default(0);
            $table->boolean('is_manual')->default(false);
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['task_id', 'started_at'], 'task_time_task_started_idx');
            // العدّاد الشغّال للموظف: WHERE employee_id = ? AND ended_at IS NULL
            $table->index(['employee_id', 'ended_at'], 'task_time_employee_running_idx');
        });

        // سجل التذكيرات اللي اتبعتت — عشان الأمر المجدول مايبعتش نفس التذكير مرتين
        Schema::create('task_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->enum('type', ['due_soon', 'overdue', 'progress_update', 'custom']);
            $table->timestamp('remind_at');
            $table->timestamp('sent_at')->nullable();
            $table->nullableMorphs('creator');
            $table->timestamps();

            $table->index(['sent_at', 'remind_at'], 'task_reminders_pending_idx');
            $table->index(['task_id', 'type'], 'task_reminders_task_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_reminders');
        Schema::dropIfExists('task_time_entries');
        Schema::dropIfExists('task_reviews');
        Schema::dropIfExists('task_progress_updates');
    }
};
