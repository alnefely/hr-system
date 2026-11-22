<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * التواصل على المهمة: التعليقات، المرفقات، وسجل الأحداث.
 *
 * task_activities منفصل عن activity_logs العام: ده الخط الزمني اللي
 * بيظهر للموظف جوّه المهمة (اتكلّف فلان، الحالة اتغيرت، النسبة بقت ٦٠٪)
 * بصياغة مفهومة، مش سجل تدقيق خام بالقيم قبل وبعد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('task_comments')->cascadeOnDelete();
            $table->morphs('author');
            $table->text('body');
            // أرقام الموظفين المشار إليهم (@) — بتتقرا مرة واحدة وقت الإشعار
            $table->json('mentions')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['task_id', 'created_at'], 'task_comments_task_created_idx');
        });

        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('comment_id')->nullable()->constrained('task_comments')->nullOnDelete();
            $table->morphs('uploader');
            $table->string('disk', 30)->default('local');
            $table->string('path', 1000);
            $table->string('original_name', 255);
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('task_id', 'task_attachments_task_idx');
        });

        Schema::create('task_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->nullableMorphs('actor');
            // created, status_changed, assigned, unassigned, declined, progress_updated,
            // commented, attachment_added, review_submitted, approved, rejected ...
            $table->string('event', 50);
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['task_id', 'created_at'], 'task_activities_task_created_idx');
            $table->index(['event', 'created_at'], 'task_activities_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_activities');
        Schema::dropIfExists('task_attachments');
        Schema::dropIfExists('task_comments');
    }
};
