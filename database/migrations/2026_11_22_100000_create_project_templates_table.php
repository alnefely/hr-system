<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * قوالب المشاريع: مشروع جاهز بمهامه بيتنسخ في أي وقت.
 *
 * هيكل المهام محفوظ JSON (عنوان، أولوية، أيام من بداية المشروع، مهام
 * فرعية، خطوات) — القالب بيتقرا كوحدة واحدة وقت النسخ بس، فمفيش داعي
 * لجداول منفصلة واستعلامات join.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_templates', function (Blueprint $table) {
            $table->id();
            // null = قالب عام لكل الفروع
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name', 191);
            $table->text('description')->nullable();
            $table->json('structure')->nullable();
            $table->boolean('is_active')->default(true);
            $table->nullableMorphs('creator');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'is_active'], 'project_templates_branch_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_templates');
    }
};
