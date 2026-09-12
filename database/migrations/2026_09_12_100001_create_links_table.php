<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table) {
            $table->id();

            $table->string('short_code', 32);
            $table->text('original_url');
            $table->string('title')->nullable();
            $table->text('notes')->nullable();

            // الفاعل في المشروع هو Admin — مفيش جدول users أصلاً.
            // nullable عشان حذف المشرف مايحذفش الروابط.
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();

            // قواعد التحكم
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_clicks')->nullable();

            // عدّادات مجمّعة — عشان الليستة ماتعملش aggregate على جدول الكليكات
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->unsignedBigInteger('unique_clicks_count')->default(0);
            $table->timestamp('last_clicked_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'created_at']);
            $table->index('is_active');
        });

        // الـ collation إجباري: الافتراضي case-insensitive، يعني aB7xK9m
        // و Ab7Xk9M هيتعاملوا كنفس الكود — تصادمات وهمية وفقد ثلثي الـ keyspace.
        // SQLite (بيئة الاختبارات) case-sensitive أصلاً فالعبارة دي لـ MySQL بس.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE links MODIFY short_code VARCHAR(32)
                           CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');
        }

        Schema::table('links', function (Blueprint $table) {
            $table->unique('short_code', 'links_short_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
