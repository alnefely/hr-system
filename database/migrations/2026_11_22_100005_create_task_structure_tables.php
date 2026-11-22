<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تفاصيل المهمة: الخطوات، الارتباطات بين المهام، والتصنيفات.
 */
return new class extends Migration
{
    public function up(): void
    {
        // قائمة خطوات صغيرة جوّه المهمة
        Schema::create('task_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('title', 500);
            $table->boolean('is_done')->default(false);
            $table->nullableMorphs('done_by');
            $table->timestamp('done_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['task_id', 'position'], 'task_checklist_task_position_idx');
        });

        // المهمة task_id ماتبدأش إلا لما depends_on_id تخلص
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('depends_on_id')->constrained('tasks')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'depends_on_id'], 'task_dependencies_unique');
            // "مين مستنّي المهمة دي؟" لما تخلص
            $table->index('depends_on_id', 'task_dependencies_depends_on_idx');
        });

        Schema::create('task_labels', function (Blueprint $table) {
            $table->id();
            // null = تصنيف عام لكل الفروع
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name', 100);
            $table->string('color', 20)->default('#6c757d');
            $table->timestamps();
            $table->softDeletes();

            $table->index('branch_id', 'task_labels_branch_idx');
        });

        Schema::create('task_label_task', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('task_label_id')->constrained('task_labels')->cascadeOnDelete();

            $table->primary(['task_id', 'task_label_id']);
            $table->index('task_label_id', 'task_label_task_label_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_label_task');
        Schema::dropIfExists('task_labels');
        Schema::dropIfExists('task_dependencies');
        Schema::dropIfExists('task_checklist_items');
    }
};
