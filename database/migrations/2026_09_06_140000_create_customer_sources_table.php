<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * مصادر العملاء.
 *
 * كان المصدر نص ثابت في قائمة منسدلة داخل الـ blade، فمافيش طريقة
 * المستخدم يضيف مصدر جديد من غير تعديل كود، ولا يعرف كل مصدر جابله
 * كام عميل. الجدول ده بيخليها بيانات قابلة للإدارة.
 *
 * التاريخ قبل جدول العملاء بخطوة عشان المفتاح الأجنبي يلاقيه.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_sources')) {
            return;
        }

        Schema::create('customer_sources', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // الفلترة الشائعة: المصادر المفعّلة مرتّبة
            $table->index(['is_active', 'sort_order'], 'customer_sources_active_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_sources');
    }
};
