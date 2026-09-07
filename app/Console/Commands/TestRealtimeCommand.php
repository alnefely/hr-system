<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Models\Admin;
use App\Services\Notification\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * فحص جاهزية البث اللحظي وإرسال إشعار تجريبي.
 *
 *   php artisan realtime:test              فحص الإعدادات بس
 *   php artisan realtime:test --send       يبعت إشعار تجريبي كمان
 *   php artisan realtime:test --send --admin=3
 *
 * الهدف إنك تعرف بالظبط إيه اللي ناقص قبل ما تفتح المتصفح، بدل
 * ما تقعد تخمّن ليه الجرس مابيتحدّثش.
 */
class TestRealtimeCommand extends Command
{
    protected $signature = 'realtime:test
        {--send : ابعت إشعار تجريبي بعد الفحص}
        {--admin= : رقم المشرف المستقبِل (الافتراضي: أول مشرف رئيسي)}';

    protected $description = 'فحص إعدادات البث اللحظي (Pusher) وإرسال إشعار تجريبي';

    public function handle(NotificationService $notifier): int
    {
        $this->newLine();
        $this->line('<fg=cyan>═══ فحص جاهزية البث اللحظي ═══</>');
        $this->newLine();

        $problems = [];

        // ---- ١) الناقل ----
        $driver = config('broadcasting.default');
        $this->check('ناقل البث', $driver, $driver === 'pusher');

        if ($driver !== 'pusher') {
            $problems[] = "BROADCAST_CONNECTION=pusher مطلوبة في .env (الحالي: {$driver})";
        }

        // ---- ٢) مفاتيح Pusher ----
        foreach (['key' => 'PUSHER_APP_KEY', 'secret' => 'PUSHER_APP_SECRET', 'app_id' => 'PUSHER_APP_ID'] as $k => $env) {
            $value = config("broadcasting.connections.pusher.{$k}");
            $this->check($env, $value ? 'مضبوط' : 'فاضي', (bool) $value);

            if (!$value) {
                $problems[] = "{$env} فاضية في .env";
            }
        }

        $cluster = config('broadcasting.connections.pusher.options.cluster');
        $this->check('PUSHER_APP_CLUSTER', $cluster ?: 'فاضي', (bool) $cluster);

        // ---- ٣) المكتبة ----
        $sdk = class_exists(\Pusher\Pusher::class);
        $this->check('حزمة pusher-php-server', $sdk ? 'مثبّتة' : 'مفقودة', $sdk);

        if (!$sdk) {
            $problems[] = 'شغّل: composer require pusher/pusher-php-server';
        }

        // ---- ٤) مسار التفويض ----
        // بنفحص بالاسم مش بالـ URI: المسار متسجّل جوّه مجموعة الأدمن
        // (admin/broadcasting/auth) عشان يقرا المستخدم من guard الأدمن.
        $hasAuth = \Illuminate\Support\Facades\Route::has('admin.broadcasting.auth');
        $this->check('مسار التفويض', $hasAuth ? 'مسجّل' : 'مفقود', $hasAuth);

        if (!$hasAuth) {
            $problems[] = 'مسار admin.broadcasting.auth مش متسجّل في routes/admin.php';
        }

        // ---- ٥) تعريف القناة ----
        // وجود المسار لوحده مش كفاية: من غير تعريف القناة، التفويض
        // بيمر لأي حد لأن مفيش callback يرفض.
        $hasChannel = $this->channelIsRegistered('admin.{adminId}');
        $this->check('تعريف قناة المشرف', $hasChannel ? 'موجود' : 'مفقود', $hasChannel);

        if (!$hasChannel) {
            $problems[] = 'قناة admin.{adminId} مش معرّفة في AppServiceProvider';
        }

        // ---- ٦) الطابور ----
        $queue = config('queue.default');
        $jobsTable = \Illuminate\Support\Facades\Schema::hasTable('jobs');
        $this->check('الطابور', $queue, true);
        $this->check('جدول jobs', $jobsTable ? 'موجود' : 'مفقود', $jobsTable || $queue === 'sync');

        if ($queue !== 'sync' && !$jobsTable) {
            $problems[] = 'جدول jobs مفقود — شغّل php artisan migrate';
        }

        if ($queue !== 'sync') {
            $pending = $jobsTable ? DB::table('jobs')->count() : 0;
            $this->line(sprintf('  %-28s <fg=yellow>%d وظيفة منتظرة</>', 'حالة الطابور', $pending));

            if ($pending > 20) {
                $problems[] = "في {$pending} وظيفة منتظرة — يبدو إن queue:work مش شغال";
            }
        }

        // ---- الخلاصة ----
        $this->newLine();

        if ($problems) {
            $this->error('البث اللحظي مش جاهز. المطلوب:');
            foreach ($problems as $i => $p) {
                $this->line('  ' . ($i + 1) . '. ' . $p);
            }
            $this->newLine();
            $this->line('<fg=gray>ملاحظة: الإشعارات هتفضل شغالة عادي في الجرس، بس من غير تحديث لحظي.</>');
        } else {
            $this->info('كل الإعدادات سليمة — البث اللحظي جاهز ✔');
        }

        // ---- إرسال تجريبي ----
        if ($this->option('send')) {
            $this->newLine();
            $this->line('<fg=cyan>═══ إرسال إشعار تجريبي ═══</>');

            $admin = $this->option('admin')
                ? Admin::find($this->option('admin'))
                : Admin::where('main', true)->first();

            if (!$admin) {
                $this->error('مفيش مشرف مستهدف.');

                return self::FAILURE;
            }

            $notification = $notifier->send(
                adminId: $admin->id,
                type: NotificationType::LeaveRequested,
                title: 'إشعار تجريبي من realtime:test',
                body: 'لو شفت ده في الجرس من غير ما تعمل Refresh، فالبث اللحظي شغال. الوقت: ' . now()->format('H:i:s'),
                // رابط حقيقي عشان زرار "عرض" يودّي لمكان مفيد
                url: route('admin.leaves.index'),
            );

            if (!$notification) {
                $this->error('فشل إنشاء الإشعار — راجع storage/logs.');

                return self::FAILURE;
            }

            $this->info("اتبعت للمشرف: {$admin->name} (#{$admin->id})");
            $this->line('  القناة: <fg=cyan>private-admin.' . $admin->id . '</>');
            $this->line('  الحدث : <fg=cyan>notification.created</>');
            $this->newLine();
            $this->line('افتح لوحة التحكم بحساب المشرف ده وشوف الجرس.');

            if ($queue !== 'sync') {
                $this->line('<fg=yellow>لو الطابور مش شغال، شغّل: php artisan queue:work</>');
            }
        }

        return $problems ? self::FAILURE : self::SUCCESS;
    }

    /** هل القناة دي معرّفة في الـ broadcaster؟ */
    private function channelIsRegistered(string $channel): bool
    {
        try {
            $broadcaster = \Illuminate\Support\Facades\Broadcast::driver();
            $reflection = new \ReflectionObject($broadcaster);

            if (!$reflection->hasProperty('channels')) {
                return false;
            }

            $property = $reflection->getProperty('channels');
            $property->setAccessible(true);

            return array_key_exists($channel, $property->getValue($broadcaster) ?? []);
        } catch (\Throwable) {
            return false;
        }
    }

    private function check(string $label, string $value, bool $ok): void
    {
        $this->line(sprintf(
            '  %-28s %s %s',
            $label,
            str_pad($value, 22),
            $ok ? '<fg=green>✔</>' : '<fg=red>✘</>'
        ));
    }
}
