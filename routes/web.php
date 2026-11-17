<?php


use Illuminate\Support\Facades\Route;
use UniSharp\LaravelFilemanager\Lfm;

// الصفحة الرئيسية: اختيار اللوحة (بوابة الموظف أو لوحة الإدارة).
// كل لوحة ليها حارس دخول وجلسة مستقلة تماماً.
Route::get('/', function () {
    return view('landing', [
        'admin'    => auth('admin')->user(),
        'employee' => auth('employee')->user(),
    ]);
})->middleware('throttle:WebRateLimiter')->name('home');

Route::middleware('throttle:WebRateLimiter')->group(function () {
    Route::prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));
});



Route::group(['prefix' => 'filemanager', 'middleware' => ['AdminPermission:filemanager_show']], function () {
    Lfm::routes();
});


/*
|--------------------------------------------------------------------------
| تحويل الروابط المختصرة
|--------------------------------------------------------------------------
| لازم تفضل آخر الملف: الراوت بياخد بارامتر حر، ولو اتحط فوق كان
| هيبلع راوتات النظام. الـ regex بيحصره في شكل الأكواد بس.
*/
Route::get('/s/{code}', [\App\Http\Controllers\Links\RedirectController::class, 'handle'])
    ->where('code', '[A-Za-z0-9_-]{3,32}')
    ->middleware('throttle:WebRateLimiter')
    ->name('link.redirect');

