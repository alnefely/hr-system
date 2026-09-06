<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {

            $table->id();
    
            // الـ key بتاع الـ setting زي site_name, site_email
            // nullable علشان صفوف الـ section مش بيكون ليها key
            $table->string('key')->nullable();
            
            // لتجميع الـ settings مع بعض زي general, mail, seo
            // بيفيد لو عندك settings في صفحات مختلفة
            $table->string('group')->default('general');
            
            // القيمة المخزنة
            $table->text('value')->nullable();
            
            // للـ select و checkbox بيتخزن فيه الخيارات
            $table->json('options')->nullable();
            
            // نوع الـ input أو section للفاصل
            $table->enum('type', [
                'text',
                'textarea', 
                'select',
                'checkbox',
                'number',
                'tel',
                'email',
                'url',
                'file',
                'password',
                'color',
                'date',
                'time',
                'datetime-local',
                'section',
                'divider'
            ])->default('text');
            
            $table->enum('dir', ['ltr', 'rtl', 'auto'])->default('auto');
            
            $table->boolean('is_required')->default(false);
            
            // حجم العمود في الـ grid (1-12)
            $table->tinyInteger('col_size')->unsigned()->default(4);
            
            // ترتيب الظهور
            $table->unsignedInteger('order')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
