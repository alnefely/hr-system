<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Artisan::call('optimize:clear');
        Artisan::call('db:seed');
        // Artisan::call('db:seed', ['--force' => true,'--class' => 'SettingsSeeder']);
        // Artisan::call('db:seed', ['--force' => true,'--class' => 'RoleSeeder']);
        // Artisan::call('db:seed', ['--force' => true,'--class' => 'AdminSeeder']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
