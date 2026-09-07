<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جداول الكاش.
 *
 * المشروع بيستخدم Redis للكاش (CACHE_STORE=redis)، لكن الجداول دي
 * موجودة كخطة بديلة لو Redis مش متاح في بيئة معيّنة — و cache_locks
 * بيستخدمها withoutOverlapping في المهام المجدولة لو الكاش database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->bigInteger('expiration')->index();
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->bigInteger('expiration')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
