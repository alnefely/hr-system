<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained()->cascadeOnDelete();
            $table->date('date');

            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('unique_clicks')->default(0);
            $table->unsignedInteger('bot_clicks')->default(0);

            // توزيعات مخزّنة كـ JSON: {"SA": 120, "EG": 45}
            // الشكل ده بيخلي صف واحد يغطي كل تفاصيل اليوم بدل
            // ما نعمل جدول منفصل لكل بُعد.
            $table->json('by_country')->nullable();
            $table->json('by_device')->nullable();
            $table->json('by_browser')->nullable();
            $table->json('by_referrer')->nullable();

            // أساس إعادة التشغيل الآمن: التجميع بيعمل updateOrCreate
            // على المفتاح ده، فتشغيله تاني بيصحّح الصف بدل ما يكرره.
            $table->unique(['link_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_daily_stats');
    }
};
