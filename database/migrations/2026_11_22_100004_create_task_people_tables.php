<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الناس حوالين المهمة: المكلّفين والمتابعين.
 *
 * المكلّف بيتنفذ عليه التكليف فوراً (status = active)، ويقدر يعتذر
 * بسبب (declined) — السجل بيفضل موجود عشان يبان في سجل المهمة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('status', ['active', 'declined'])->default('active');
            $table->text('decline_reason')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->nullableMorphs('assigned_by');
            $table->timestamps();

            $table->unique(['task_id', 'employee_id'], 'task_assignees_unique');
            // "مهامي": WHERE employee_id = ? AND status = 'active'
            $table->index(['employee_id', 'status'], 'task_assignees_employee_status_idx');
        });

        Schema::create('task_watchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            // مشرف أو موظف
            $table->morphs('watcher');
            $table->timestamps();

            $table->unique(['task_id', 'watcher_type', 'watcher_id'], 'task_watchers_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_watchers');
        Schema::dropIfExists('task_assignees');
    }
};
