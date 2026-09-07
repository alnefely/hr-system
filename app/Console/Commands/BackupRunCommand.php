<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * نسخة احتياطية من قاعدة البيانات والمرفقات.
 *
 *   php artisan backup:run                 # قاعدة البيانات + المرفقات
 *   php artisan backup:run --db-only       # قاعدة البيانات بس
 *   php artisan backup:run --keep=30       # يحتفظ بآخر 30 نسخة
 *
 * بيعتمد على mysqldump الموجود في PATH. لو مش موجود، بيقول للمستخدم
 * بوضوح بدل ما ينتج ملف فاضي ويوهمه إن النسخة اتاخدت.
 */
class BackupRunCommand extends Command
{
    protected $signature = 'backup:run
        {--db-only : نسخة قاعدة البيانات فقط بدون المرفقات}
        {--keep= : عدد النسخ اللي نحتفظ بيها (الافتراضي من config)}
        {--no-cleanup : ماتحذفش النسخ القديمة}';

    protected $description = 'إنشاء نسخة احتياطية من قاعدة البيانات وملفات المرفقات';

    public function handle(): int
    {
        $disk = storage_path('app/' . config('backup.path', 'backups'));
        File::ensureDirectoryExists($disk);

        $stamp = Carbon::now()->format('Ymd_His');
        $name = config('backup.prefix', 'sbbs') . "_{$stamp}";

        $this->line("إنشاء نسخة احتياطية: <fg=cyan>{$name}</>");
        $this->newLine();

        // ---- قاعدة البيانات ----
        $sqlPath = "{$disk}/{$name}.sql";

        if (!$this->dumpDatabase($sqlPath)) {
            return self::FAILURE;
        }

        $this->info('  ✔ قاعدة البيانات: ' . $this->humanSize(filesize($sqlPath)));

        // ---- الضغط (مع المرفقات لو مطلوب) ----
        $zipPath = "{$disk}/{$name}.zip";
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('تعذر إنشاء ملف الضغط.');
            @unlink($sqlPath);

            return self::FAILURE;
        }

        $zip->addFile($sqlPath, basename($sqlPath));

        if (!$this->option('db-only')) {
            $files = $this->addAttachments($zip);
            $this->info("  ✔ المرفقات: {$files} ملف");
        }

        $zip->close();
        @unlink($sqlPath);

        $this->newLine();
        $this->info('تمت النسخة بنجاح: ' . $zipPath);
        $this->line('الحجم: <fg=cyan>' . $this->humanSize(filesize($zipPath)) . '</>');

        if (!$this->option('no-cleanup')) {
            $this->cleanup($disk);
        }

        return self::SUCCESS;
    }

    /** تفريغ قاعدة البيانات بـ mysqldump. */
    private function dumpDatabase(string $target): bool
    {
        $connection = config('database.default');
        $db = config("database.connections.{$connection}");

        if (($db['driver'] ?? null) !== 'mysql') {
            $this->error("النسخ الاحتياطي مدعوم لـ MySQL فقط حالياً (الحالي: {$db['driver']}).");

            return false;
        }

        $command = [
            'mysqldump',
            '--host=' . $db['host'],
            '--port=' . $db['port'],
            '--user=' . $db['username'],
            '--single-transaction',
            '--quick',
            '--routines',
            '--default-character-set=utf8mb4',
            $db['database'],
        ];

        $process = new Process($command, timeout: (int) config('backup.timeout', 600));

        // كلمة المرور بتتبعت كـ env عشان ماتظهرش في قائمة العمليات
        $process->setEnv(['MYSQL_PWD' => (string) $db['password']]);

        $handle = fopen($target, 'w');

        try {
            $process->run(function ($type, $buffer) use ($handle) {
                if ($type === Process::OUT) {
                    fwrite($handle, $buffer);
                }
            });
        } finally {
            fclose($handle);
        }

        if (!$process->isSuccessful()) {
            @unlink($target);
            $this->error('فشل mysqldump: ' . trim($process->getErrorOutput()));
            $this->line('تأكد إن mysqldump موجود في PATH.');

            return false;
        }

        if (!file_exists($target) || filesize($target) === 0) {
            @unlink($target);
            $this->error('ملف النسخة فاضي — النسخة مش صالحة.');

            return false;
        }

        return true;
    }

    /** إضافة مجلدات المرفقات للأرشيف. */
    private function addAttachments(ZipArchive $zip): int
    {
        $count = 0;

        foreach (config('backup.include', []) as $relative) {
            $absolute = base_path($relative);

            if (!is_dir($absolute)) {
                continue;
            }

            foreach (File::allFiles($absolute) as $file) {
                $zip->addFile(
                    $file->getRealPath(),
                    $relative . '/' . $file->getRelativePathname()
                );
                $count++;
            }
        }

        return $count;
    }

    /** حذف النسخ الأقدم مع الاحتفاظ بعدد محدد. */
    private function cleanup(string $disk): void
    {
        $keep = (int) ($this->option('keep') ?: config('backup.keep', 14));

        $backups = collect(glob($disk . '/*.zip'))
            ->sortByDesc(fn ($path) => filemtime($path))
            ->values();

        if ($backups->count() <= $keep) {
            return;
        }

        $toDelete = $backups->slice($keep);

        foreach ($toDelete as $path) {
            @unlink($path);
        }

        $this->line("تم حذف {$toDelete->count()} نسخة قديمة (الاحتفاظ بآخر {$keep}).");
    }

    private function humanSize(int|false $bytes): string
    {
        if ($bytes === false) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
