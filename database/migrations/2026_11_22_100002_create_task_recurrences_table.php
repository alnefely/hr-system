<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * قواعد المهام المتكررة (يومي / أسبوعي / شهري / سنوي).
 *
 * الأمر المجدول بيدوّر على next_run_at <= now() وينشئ نسخة من المهمة
 * من بيانات blueprint، وبعدين يحسب next_run_at الجاي.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_recurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();

            // بيانات المهمة اللي هتتنسخ: العنوان، الوصف، الأولوية، المكلّفين، الخطوات...
            $table->json('blueprint');

            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly'])->default('weekly');
            // كل كام وحدة (كل ٢ أسبوع مثلاً)
            $table->unsignedSmallInteger('interval')->default(1);
            // أيام الأسبوع للتكرار الأسبوعي [0..6]
            $table->json('weekdays')->nullable();
            // يوم الشهر للتكرار الشهري
            $table->unsignedTinyInteger('month_day')->nullable();
            // مدة المهمة المنشأة بالأيام (موعد التسليم = تاريخ الإنشاء + المدة)
            $table->unsignedSmallInteger('duration_days')->default(0);
            $table->time('run_time')->default('08:00:00');

            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedInteger('occurrences_count')->default(0);
            $table->boolean('is_active')->default(true);

            $table->nullableMorphs('creator');
            $table->timestamps();
            $table->softDeletes();

            // الأمر المجدول: WHERE is_active = 1 AND next_run_at <= now()
            $table->index(['is_active', 'next_run_at'], 'task_recurrences_due_idx');
            $table->index('branch_id', 'task_recurrences_branch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_recurrences');
    }
};
