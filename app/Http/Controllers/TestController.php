<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use App\Services\Cache\CacheService;
use App\Services\Cache\Keys\SettingsCache;

class TestController extends Controller
{
    public function test()
    {
        // $cache = app(CacheService::class);

        // return $cache->get('settings', 'site_name');

        return Settings::get('site_name0');

        // return $cache->remember(
        //     [CacheGroup::Settings->value],  // مجموعات
        //     "email", // مفتاح
        //     function () {
        //         return [
        //             'email' => 'info@rhmany.com'.rand(1,555),  // القيمة
        //         ];
        //     },  // القيمة
        // );

        // $cache->invalidate(CacheGroup::Settings->value);






        // $cache->remember(
        //     ['lessons', 'teacher.2'],  // مجموعات
        //     "data",                              // مفتاح
        //     fn () => ['data' => 'Teacher 2'],  // القيمة
        // );

        // $cache->invalidate('teacher.2');

        return response()->json(['message' => 'Hello, World!']);
    }
}
