<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سجل حركات النظام (Audit Log).
 *
 * بيسجّل كل إضافة/تعديل/حذف على الموديلات المراقَبة، مع القيم قبل وبعد،
 * ومين اللي عمل العملية، ومن أي جهاز.
 *
 * الجدول ده بيكبر بسرعة، فالفهارس مبنية على أنماط الاستعلام الفعلية:
 *  - (subject_type, subject_id) => سجل موديول أو سجل بعينه
 *  - (causer_id, created_at)    => نشاط مشرف معيّن
 *  - (event, created_at)        => فلترة بنوع العملية
 *  - created_at                 => أمر التنظيف الدوري
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_logs')) {
            return;
        }

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // العملية: created / updated / deleted / restored / force_deleted
            $table->string('event', 30);

            // الموديل اللي اتعمل عليه الحركة
            $table->string('subject_type', 191);
            $table->unsignedBigInteger('subject_id')->nullable();

            // وصف مختصر للسجل وقت الحركة (اسم الموظف، رقم الفاتورة...)
            // بيتخزّن عشان يفضل ظاهر حتى لو السجل الأصلي اتحذف نهائياً.
            $table->string('subject_label', 191)->nullable();

            // مين عمل العملية (null = أمر artisan أو seeder)
            $table->foreignId('causer_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('causer_name', 191)->nullable();

            // القيم قبل وبعد — الحقول اللي اتغيّرت بس
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // سياق الطلب
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('method', 10)->nullable();

            $table->timestamps();

            $table->index(['subject_type', 'subject_id'], 'activity_logs_subject_idx');
            $table->index(['causer_id', 'created_at'], 'activity_logs_causer_idx');
            $table->index(['event', 'created_at'], 'activity_logs_event_idx');
            $table->index('created_at', 'activity_logs_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
