<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جدول توكنات إعادة تعيين كلمة مرور المشرفين.
 *
 * النظام القديم كان بيولّد كلمة مرور جديدة ويبعتها بالإيميل ويغيّرها فوراً،
 * يعني أي حد يعرف إيميل مشرف يقدر يقفل حسابه. الطريقة دي بتبعت رابط مؤقت
 * بس، وكلمة المرور مابتتغيّرش غير لما صاحب الحساب يفتح الرابط ويحدّدها بنفسه.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_password_reset_tokens')) {
            Schema::create('admin_password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_password_reset_tokens');
    }
};
