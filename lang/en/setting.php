<?php

/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
| Screen titles + label for every setting field.
|
| The Settings model reads directly from here:
|
|     trans("setting.{$this->key}.label")
|     trans("setting.{$this->key}.hint")
|     trans("setting.{$this->key}.placeholder")
|
| Any new setting added to the database must have a matching key here
| with exactly the same key, otherwise the screen shows the raw key name.
*/

return [

    'title'       => 'Settings',
    'clear_cache' => 'Clear Cache',

    // Options for enable/disable fields (select with 1/0 value)
    'options' => [
        1 => 'Enabled',
        0 => 'Disabled',
    ],

    'groups' => [
        'general' => 'General Settings',
        'social'  => 'Social Media',
        'mail'    => 'Email',
    ],

    // Divider
    'logo_favicon_divider' => [
        'label'       => 'Logo & Favicon',
        'hint'        => '',
        'placeholder' => '',
    ],
    'app_env_divider' => [
        'label'       => 'Application Environment & Timezone',
        'hint'        => '',
        'placeholder' => '',
    ],

    'site_name' => [
        'label'       => 'Site Name',
        'hint'        => '',
        'placeholder' => 'Enter the site name',
    ],
    'site_email' => [
        'label'       => 'Site Email',
        'hint'        => '',
        'placeholder' => 'Enter the site email',
    ],
    'site_logo' => [
        'label'       => 'Site Logo',
        'hint'        => '',
        'placeholder' => '',
    ],
    'site_favicon' => [
        'label'       => 'Site Icon (Favicon)',
        'hint'        => '',
        'placeholder' => '',
    ],

    'mail_driver' => [
        'label'       => 'Mail Driver',
        'hint'        => '',
        'placeholder' => '',
    ],
    'mail_host' => [
        'label'       => 'SMTP Server (Mail Host)',
        'hint'        => '',
        'placeholder' => '',
    ],
    'mail_port' => [
        'label'       => 'Mail Port',
        'hint'        => '',
        'placeholder' => '',
    ],
    'mail_username' => [
        'label'       => 'Mail Username',
        'hint'        => '',
        'placeholder' => '',
    ],
    'mail_password' => [
        'label'       => 'Mail Password',
        'hint'        => '',
        'placeholder' => '',
    ],
    'mail_encryption' => [
        'label'       => 'Mail Encryption',
        'hint'        => '',
        'placeholder' => '',
    ],
    'mail_from_name' => [
        'label'       => 'Mail From Name',
        'hint'        => '',
        'placeholder' => '',
    ],
    'mail_from_address' => [
        'label'       => 'Mail From Address',
        'hint'        => '',
        'placeholder' => '',
    ],
    'facebook_url' => [
        'label'       => 'Facebook URL',
        'hint'        => '',
        'placeholder' => '',
    ],
    'twitter_url' => [
        'label'       => 'Twitter URL',
        'hint'        => '',
        'placeholder' => '',
    ],
    'instagram_url' => [
        'label'       => 'Instagram URL',
        'hint'        => '',
        'placeholder' => '',
    ],
    'linkedin_url' => [
        'label'       => 'LinkedIn URL',
        'hint'        => '',
        'placeholder' => '',
    ],
    'youtube_url' => [
        'label'       => 'Youtube URL',
        'hint'        => '',
        'placeholder' => '',
    ],
    'tiktok_url' => [
        'label'       => 'TikTok URL',
        'hint'        => '',
        'placeholder' => '',
    ],
    'snapchat_url' => [
        'label'       => 'Snapchat URL',
        'hint'        => '',
        'placeholder' => '',
    ],
    'github_url' => [
        'label'       => 'GitHub URL',
        'hint'        => '',
        'placeholder' => '',
    ],
    'telegram_url' => [
        'label'       => 'Telegram URL',
        'hint'        => '',
        'placeholder' => '',
    ],
    'whatsapp_url' => [
        'label'       => 'WhatsApp URL',
        'hint'        => '',
        'placeholder' => '',
    ],
    'phone_number' => [
        'label'       => 'Phone Number',
        'hint'        => '',
        'placeholder' => '',
    ],
    'app_env' => [
        'label'       => 'App Environment',
        'hint'        => 'Sets the current application environment (e.g. local: development mode, production: production mode)',
        'placeholder' => '',
    ],
    'app_timezone' => [
        'label'       => 'App Timezone',
        'hint'        => 'Sets the default timezone for the application (e.g. UTC, Asia/Riyadh, America/New_York)',
        'placeholder' => '',
    ],

];
