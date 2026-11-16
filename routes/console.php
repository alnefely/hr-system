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

// ترحيل أرصدة العهدة النقدية — أوتوماتيك بالكامل، مفيش زر في الشاشة.
//
// الترحيل بيتحسب فوراً مع أي تسجيل/تعديل/حذف لمصروف أو تعديل مخصص
// (Expense::booted و updateBalance). الأمر ده دوره:
//   1) يفتح الشهر الجديد بمرحّله من أول يوم، حتى لو لسه ماحدّش سجّل حاجة.
//   2) شبكة أمان للتعديلات اللي مابتطلقش أحداث الموديل (حذف جماعي،
//      تعديل مباشر في قاعدة البيانات، استيراد).
// آمن لو اتشغّل أكتر من مرة — لو الأرقام مضبوطة مابيغيّرش حاجة.
Schedule::command('petty-cash:carry-over')
    ->monthlyOn(1, '01:00')
    ->withoutOverlapping()
    ->onOneServer();

// حذف الإشعارات القديمة يومياً ٢:٣٠ صباحاً.
// المقروء بعد ٣٠ يوم، وأي إشعار بعد ٩٠ — المدد من config/notifications.php
Schedule::command('notifications:prune')
    ->dailyAt('02:30')
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

// معالجة الطابور من الـ Schedule — للسيرفر اللي مافيهوش Supervisor.
//
// كل دقيقة بيشتغل، يخلّص اللي في الطابور (الإيميلات بالذات: AppMail
// بيتبعت في الخلفية) ويقفل لوحده. --max-time أقل من دقيقة عشان
// مايتداخلش مع التشغيلة اللي بعده.
//
// لو فيه worker دائم عبر Supervisor (QUEUE_SUPERVISED=true) ده بيتوقف،
// عشان مايبقاش فيه اتنين بيسحبوا من نفس الطابور.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer()
    ->skip(fn () => (bool) config('queue.supervised'));
