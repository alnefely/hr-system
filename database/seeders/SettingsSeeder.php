<?php

namespace Database\Seeders;

use App\Models\Settings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [

            // =========================================
            // General
            // =========================================
            [
                'key'         => null,
                'group'       => 'general',
                'value'       => null,
                'options'     => null,
                'type'        => 'section',
                'is_required' => false,
                'col_size'    => 12,
                'order'       => 1,
                'dir'         => 'auto',
            ],
            [
                'key'         => 'site_name',
                'group'       => 'general',
                'value'       => 'HR SYS',
                'options'     => null,
                'type'        => 'text',
                'dir'         => 'auto',
                'is_required' => true,
                'col_size'    => 4,
                'order'       => 2,
            ],
            [
                'key'         => 'site_email',
                'group'       => 'general',
                'value'       => 'info@rhmany.com',
                'options'     => null,
                'type'        => 'email',
                'dir'         => 'ltr',
                'is_required' => true,
                'col_size'    => 8,
                'order'       => 3,
            ],
            [
                'key'         => 'logo_favicon_divider',
                'group'       => 'general',
                'value'       => 'favicon',
                'options'     => null,
                'type'        => 'divider',
                'dir'         => 'auto',
                'is_required' => false,
                'col_size'    => 12,
                'order'       => 4,
            ],
            [
                'key'         => 'site_logo',
                'group'       => 'general',
                'value'       => demo('img'),
                'options'     => null,
                'type'        => 'file',
                'dir'         => 'auto',
                'is_required' => false,
                'col_size'    => 4,
                'order'       => 5,
            ],
            [
                'key'         => 'site_favicon',
                'group'       => 'general',
                'value'       => demo('img'),
                'options'     => null,
                'type'        => 'file',
                'dir'         => 'auto',
                'is_required' => false,
                'col_size'    => 4,
                'order'       => 6,
            ],
            [
                'key'         => 'app_env_divider',
                'group'       => 'general',
                'value'       => null,
                'options'     => null,
                'type'        => 'divider',
                'dir'         => 'auto',
                'is_required' => false,
                'col_size'    => 12,
                'order'       => 7,
            ],
            [
                'key'         => 'app_env',
                'group'       => 'general',
                'value'       => env('APP_ENV', 'local'),
                'options'     => ['production', 'local'],
                'type'        => 'select',
                'dir'         => 'auto',
                'is_required' => true,
                'col_size'    => 4,
                'order'       => 7,
            ],
            [
                'key'         => 'app_timezone',
                'group'       => 'general',
                'value'       => env('APP_TIMEZONE', 'Africa/Cairo'),
                // 'options'     => array_combine(timezone_identifiers_list(), timezone_identifiers_list()),
                'options'     => ['Africa/Cairo','Asia/Riyadh','Asia/Dubai'],
                'type'        => 'select',
                'dir'         => 'auto',
                'is_required' => true,
                'col_size'    => 4,
                'order'       => 8,
            ],



            // =========================================
            // SEO
            // =========================================
            // [
            //     'key'         => null,
            //     'group'       => 'seo',
            //     'value'       => null,
            //     'options'     => null,
            //     'type'        => 'section',
            //     'is_required' => false,
            //     'col_size'    => 12,
            //     'order'       => 1,
            // ],
            // [
            //     'key'         => 'meta_title',
            //     'group'       => 'seo',
            //     'value'       => 'Rhmany',
            //     'options'     => null,
            //     'type'        => 'text',
            //     'is_required' => false,
            //     'col_size'    => 12,
            //     'order'       => 2,
            // ],
            // [
            //     'key'         => 'meta_description',
            //     'group'       => 'seo',
            //     'value'       => null,
            //     'options'     => null,
            //     'type'        => 'textarea',
            //     'is_required' => false,
            //     'col_size'    => 12,
            //     'order'       => 3,
            // ],
            // [
            //     'key'         => 'meta_keywords',
            //     'group'       => 'seo',
            //     'value'       => null,
            //     'options'     => null,
            //     'type'        => 'text',
            //     'is_required' => false,
            //     'col_size'    => 12,
            //     'order'       => 4,
            // ],
            // [
            //     'key'         => 'google_analytics',
            //     'group'       => 'seo',
            //     'value'       => null,
            //     'options'     => null,
            //     'type'        => 'text',
            //     'is_required' => false,
            //     'col_size'    => 6,
            //     'order'       => 5,
            // ],
            // [
            //     'key'         => 'google_tag_manager',
            //     'group'       => 'seo',
            //     'value'       => null,
            //     'options'     => null,
            //     'type'        => 'text',
            //     'is_required' => false,
            //     'col_size'    => 6,
            //     'order'       => 6,
            // ],

            // =========================================
            // Mail
            // =========================================
            [
                'key'         => null,
                'group'       => 'mail',
                'value'       => null,
                'options'     => null,
                'type'        => 'section',
                'dir'         => 'auto',
                'is_required' => false,
                'col_size'    => 12,
                'order'       => 1,
                'dir'         => 'auto',
            ],
            [
                'key'         => 'mail_driver',
                'group'       => 'mail',
                'value'       => 'log',
                'options'     => ['log', 'smtp', 'sendmail', 'mailgun', 'ses'],
                'type'        => 'select',
                'is_required' => true,
                'col_size'    => 3,
                'order'       => 2,
                'dir'         => 'auto',
            ],
            [
                'key'         => 'mail_host',
                'group'       => 'mail',
                'value'       => 'smtp.mailtrap.io',
                'options'     => null,
                'type'        => 'text',
                'is_required' => true,
                'col_size'    => 3,
                'order'       => 3,
                'dir'         => 'ltr',
            ],
            [
                'key'         => 'mail_port',
                'group'       => 'mail',
                'value'       => '587',
                'options'     => null,
                'type'        => 'number',
                'is_required' => true,
                'col_size'    => 3,
                'order'       => 4,
                'dir'         => 'ltr',
            ],
            [
                'key'         => 'mail_username',
                'group'       => 'mail',
                'value'       => 'info@rhmany.com',
                'options'     => null,
                'type'        => 'text',
                'is_required' => true,
                'col_size'    => 3,
                'order'       => 5,
                'dir'         => 'ltr',
            ],
            [
                'key'         => 'mail_password',
                'group'       => 'mail',
                'value'       => 'password',
                'options'     => null,
                'type'        => 'password',
                'is_required' => false,
                'col_size'    => 3,
                'order'       => 6,
                'dir'         => 'auto',
            ],
            [
                'key'         => 'mail_encryption',
                'group'       => 'mail',
                'value'       => 'tls',
                'options'     => ['tls', 'ssl', 'none', 'null'],
                'type'        => 'select',
                'is_required' => true,
                'col_size'    => 3,
                'order'       => 7,
                'dir'         => 'auto',
            ],
            [
                'key'         => 'mail_from_name',
                'group'       => 'mail',
                'value'       => 'Rhmany',
                'options'     => null,
                'type'        => 'text',
                'is_required' => true,
                'col_size'    => 3,
                'order'       => 8,
                'dir'         => 'ltr',
            ],

            // 'MAIL_FROM_ADDRESS'
            [
                'key'         => 'mail_from_address',
                'group'       => 'mail',
                'value'       => 'info@rhmany.com',
                'options'     => null,
                'type'        => 'text',
                'is_required' => true,
                'col_size'    => 3,
                'order'       => 9,
                'dir'         => 'ltr',
            ],

            // =========================================
            // Social Media
            // =========================================
            [
                'key'         => null,
                'group'       => 'social',
                'value'       => null,
                'options'     => null,
                'type'        => 'section',
                'is_required' => false,
                'col_size'    => 12,
                'order'       => 1,
                'dir'         => 'auto',
            ],
            [
                'key'         => 'facebook_url',
                'group'       => 'social',
                'value'       => null,
                'options'     => null,
                'type'        => 'url',
                'is_required' => false,
                'col_size'    => 4,
                'order'       => 2,
                'dir'         => 'ltr',
            ],
            [
                'key'         => 'twitter_url',
                'group'       => 'social',
                'value'       => null,
                'options'     => null,
                'type'        => 'url',
                'is_required' => false,
                'col_size'    => 4,
                'order'       => 3,
                'dir'         => 'ltr',
            ],
            [
                'key'         => 'instagram_url',
                'group'       => 'social',
                'value'       => null,
                'options'     => null,
                'type'        => 'url',
                'is_required' => false,
                'col_size'    => 4,
                'order'       => 4,
                'dir'         => 'ltr',
            ],
            [
                'key'         => 'linkedin_url',
                'group'       => 'social',
                'value'       => null,
                'options'     => null,
                'type'        => 'url',
                'is_required' => false,
                'col_size'    => 4,
                'order'       => 5,
                'dir'         => 'ltr',
            ],
            [
                'key'         => 'youtube_url',
                'group'       => 'social',
                'value'       => null,
                'options'     => null,
                'type'        => 'url',
                'is_required' => false,
                'col_size'    => 4,
                'order'       => 6,
                'dir'         => 'ltr',
            ],
            [
                'key'         => 'tiktok_url',
                'group'       => 'social',
                'value'       => null,
                'options'     => null,
                'type'        => 'url',
                'is_required' => false,
                'col_size'    => 4,
                'order'       => 7,
                'dir'         => 'ltr',
            ],
            [
                'key'         => 'snapchat_url',
                'group'       => 'social',
                'value'       => null,
                'options'     => null,
                'type'        => 'url',
                'is_required' => false,
                'col_size'    => 4,
                'order'       => 8,
                'dir'         => 'ltr',
            ],
            [
                'key'         => 'github_url',
                'group'       => 'social',
                'value'       => null,
                'options'     => null,
                'type'        => 'url',
                'is_required' => false,
                'col_size'    => 4,
                'order'       => 9,
                'dir'         => 'ltr',
            ],
            [
                'key'         => 'whatsapp_url',
                'group'       => 'social',
                'value'       => null,
                'options'     => null,
                'type'        => 'url',
                'is_required' => false,
                'col_size'    => 4,
                'order'       => 10,
                'dir'         => 'ltr',
            ],
            [
                'key'         => 'telegram_url',
                'group'       => 'social',
                'value'       => null,
                'options'     => null,
                'type'        => 'url',
                'is_required' => false,
                'col_size'    => 4,
                'order'       => 11,
                'dir'         => 'ltr',
            ],
            // phone
            [
                'key'         => 'phone_number',
                'group'       => 'social',
                'value'       => null,
                'options'     => null,
                'type'        => 'tel',
                'is_required' => false,
                'col_size'    => 4,
                'order'       => 12,
                'dir'         => 'ltr',
            ],
        ];

        $settings = array_map(function ($setting) {
            if (isset($setting['options']) && is_array($setting['options'])) {
                $setting['options'] = json_encode($setting['options']);
            }
            $setting['created_at'] = now();
            $setting['updated_at'] = now();
            return $setting;
        }, $settings);

        // insertOrIgnore علشان لو اتشغل أكتر من مرة مش يعمل duplicate
        Settings::insertOrIgnore($settings);
    }
}
