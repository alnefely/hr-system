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
            'password' => bcrypt($this->password()),
        ]);
    }

    /**
     * كلمة مرور المشرف الرئيسي.
     *
     * 123456 ثابتة في الكود كانت بتتسجّل في أي تنصيب جديد حتى الإنتاج.
     * في الإنتاج لازم ADMIN_SEED_PASSWORD (8 حروف على الأقل)؛ في التطوير
     * الافتراضي فاضل زي ما هو للسهولة.
     */
    private function password(): string
    {
        $password = (string) env('ADMIN_SEED_PASSWORD', '');

        if ($password !== '') {
            return $password;
        }

        if (app()->isProduction()) {
            throw new \RuntimeException('Set ADMIN_SEED_PASSWORD (min 8 chars) in .env before seeding the main admin in production.');
        }

        return '123456';
    }
}
