<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::create([
            'name' => 'Rhmany Admin',
            'email' => 'admin@admin.com',
            'img' => '/admin/demo.png',
            'main' => 1,
            'role_id' => 1,
            'password' => bcrypt(123456),
        ]);
    }
}
