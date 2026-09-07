<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إشعارات المشرفين.
 *
 * جدول خاص بدل notifications بتاع Laravel عشان نقدر نربطه بالفرع
 * (عزل البيانات) ونفلتر بالنوع بسهولة.
 *
 * الفهارس مبنية على أنماط الاستعلام الفعلية:
 *  - (admin_id, read_at)  => إشعارات مشرف + عدّاد غير المقروء
 *  - (branch_id, created_at) => إشعارات فرع
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_notifications')) {
            return;
        }

        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();

            // المستلم — null يعني إشعار موجّه لفرع كامل
            $table->foreignId('admin_id')->nullable()->constrained('admins')->cascadeOnDelete();

            // الفرع اللي الإشعار يخصه (للعزل)
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();

            $table->string('type', 60);
            $table->string('title', 191);
            $table->text('body')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('icon', 60)->nullable();
            $table->string('level', 20)->default('info');

            // بيانات إضافية للعرض (المبلغ، اسم الموظف...)
            $table->json('data')->nullable();

            // مين سبّب الإشعار
            $table->foreignId('causer_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('causer_name', 191)->nullable();

            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'read_at'], 'admin_notifications_admin_read_idx');
            $table->index(['branch_id', 'created_at'], 'admin_notifications_branch_idx');
            $table->index(['type', 'created_at'], 'admin_notifications_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
