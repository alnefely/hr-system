<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->string('code', 30)->unique();
                $table->string('name', 150);
                $table->string('company_name', 150)->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('phone_country', 10)->nullable();
                $table->string('email', 100)->nullable();
                $table->string('tax_number', 50)->nullable();
                $table->text('address')->nullable();
                $table->text('notes')->nullable();
                // بيانات الملف التجاري
                $table->enum('customer_type', ['individual', 'company'])->default('company');

                // المصدر بقى مرجع لجدول مستقل. العمود النصي القديم
                // اتساب عشان الترحيل ولعرض القيم اللي اتكتبت بإيد.
                $table->foreignId('customer_source_id')->nullable()
                    ->constrained('customer_sources')->nullOnDelete();
                $table->string('source', 100)->nullable();
                $table->string('industry', 100)->nullable();
                $table->string('contact_person', 150)->nullable();
                $table->string('contact_position', 100)->nullable();
                $table->string('secondary_phone', 50)->nullable();
                $table->string('secondary_phone_country', 10)->nullable();
                $table->string('website', 200)->nullable();
                $table->decimal('credit_limit', 12, 2)->nullable()->default(0);
                $table->string('payment_terms', 100)->nullable();

                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
