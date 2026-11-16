<?php

use Database\Seeders\AdminSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * البيانات الأساسية اللي النظام مايشتغلش من غيرها: الإعدادات والأدوار
 * والمشرف الرئيسي.
 *
 * قبل كده كان بيشغّل db:seed كامل، وده بيضيف بيانات تجريبية (عملاء
 * وموظفين وفواتير وهمية) مع أي تنصيب جديد. وفي الإنتاج db:seed من غير
 * --force بيتلغي بصمت، فحتى الأساسيات ماكانتش بتتسجّل.
 *
 * البيانات التجريبية للتطوير: php artisan db:seed
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('optimize:clear');

        foreach ([SettingsSeeder::class, RoleSeeder::class, AdminSeeder::class] as $seeder) {
            Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]);
        }
    }

    public function down(): void
    {
    }
};
