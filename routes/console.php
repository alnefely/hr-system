<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| أوامر الكونسول
|--------------------------------------------------------------------------
| الأوامر الحقيقية بتتكتب كـ classes في app/Console/Commands ولارافيل
| بيكتشفها تلقائياً. الملف ده للـ closures البسيطة والجدولة بس.
|
| أوامر المشروع:
|   php artisan lang:check-missing   → app/Console/Commands/LangCheckMissingCommand.php
|   php artisan lang:diff            → app/Console/Commands/LangDiffCommand.php
|   php artisan activity:prune       → app/Console/Commands/PruneActivityLogsCommand.php
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


/*
|--------------------------------------------------------------------------
| المهام المجدولة
|--------------------------------------------------------------------------
| محتاجة كرون واحد على السيرفر:
|   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
*/

// التنبيهات الدورية كل يوم 8 صباحاً (اشتراكات، عهد، فواتير متأخرة).
// الأمر idempotent فمابيبعتش نفس التنبيه مرتين في اليوم.
Schedule::command('alerts:send')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onOneServer();

// نسخة احتياطية يومية 3 صباحاً (قاعدة البيانات + المرفقات).
Schedule::command('backup:run')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer();

// ترحيل أرصدة العهدة النقدية أول كل شهر الساعة 1 صباحاً.
// كان بيتنفذ داخل صفحة المصاريف مع كل زيارة — بقى مرة واحدة شهرياً.
Schedule::command('petty-cash:carry-over')
    ->monthlyOn(1, '01:00')
    ->withoutOverlapping()
    ->onOneServer();

// تنظيف سجل الحركات القديم كل يوم الساعة 2 صباحاً.
// --force عشان مايستناش تأكيد، والمدة بتيجي من config/activity.php
Schedule::command('activity:prune --force')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();

// تفريغ نقرات الروابط من Redis لقاعدة البيانات كل ٥ دقايق.
// مسار التحويل مابيكتبش في قاعدة البيانات خالص عشان يفضل سريع،
// فالأمر ده هو اللي بينقل النقرات والعدّادات. من غيره الإحصائيات
// بتقف — لكن التحويل نفسه بيفضل شغّال.
Schedule::command('links:flush')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// وتلخيص الإحصائيات كل ٥ دقايق كمان، بعد التفريغ على طول.
//
// بيلخّص النهارده وامبارح: updateOrCreate بيصحّح الصف بدل ما
// يكرره، فأرقام اليوم الجاري بتتحدّث مع كل تشغيلة.
//
// الحمل بسيط — استعلامات تجميع على نقرات يومين بس مش الجدول كله.
// لو الترافيك كبر لدرجة إن ده يضغط، رجّعه dailyAt('03:15') وزوّد
// --days عشان يغطي اللي فات.
Schedule::command('links:aggregate')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// حذف النقرات القديمة يومياً ٣:٣٠ صباحاً.
// المدة من config/links.php (افتراضي ٩٠ يوم).
// الأرشيف الدائم في link_daily_stats فالتاريخ مابيضيعش.
Schedule::command('links:prune')
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->onOneServer();
