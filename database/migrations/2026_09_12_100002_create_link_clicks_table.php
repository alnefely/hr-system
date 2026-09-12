<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained()->cascadeOnDelete();

            // هويات مهشَّرة — مفيش IP خام. كفاية للتفرقة بين الزوار،
            // ومن غير مسؤولية قانونية على بيانات شخصية.
            $table->string('ip_hash', 64)->nullable();
            $table->string('visitor_hash', 64)->nullable();

            $table->string('device_type', 20)->nullable();
            $table->string('os', 40)->nullable();
            $table->string('browser', 40)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('language', 10)->nullable();

            $table->string('referrer_domain', 190)->nullable();

            // البوتات تتعلّم ولا تتحذف — تفضل قابلة للمراجعة
            $table->boolean('is_bot')->default(false);
            $table->string('bot_name', 60)->nullable();

            $table->timestamp('clicked_at')->useCurrent();

            $table->index(['link_id', 'clicked_at']);
            $table->index(['link_id', 'visitor_hash']);
            $table->index(['is_bot', 'clicked_at']);

            // للتنظيف الدوري: الحذف بيمشي على التاريخ لوحده
            $table->index('clicked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_clicks');
    }
};
