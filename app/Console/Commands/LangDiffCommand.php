<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;

/**
 * بيقارن مفاتيح الترجمة بين لغتين ويطبع الناقص في كل اتجاه.
 *
 *   php artisan lang:diff
 *   php artisan lang:diff --from=ar --to=en
 *   php artisan lang:diff --file=payroll
 */
class LangDiffCommand extends Command
{
    protected $signature = 'lang:diff
        {--from=ar : اللغة المرجعية}
        {--to=en : اللغة اللي بنقارنها}
        {--file= : ملف واحد بس بدل كل الملفات}';

    protected $description = 'مقارنة مفاتيح الترجمة بين لغتين وإظهار الناقص في كل اتجاه';

    public function handle(): int
    {
        $from = $this->option('from');
        $to   = $this->option('to');
        $only = $this->option('file');

        $fromPath = lang_path($from);
        $toPath   = lang_path($to);

        if (!is_dir($fromPath) || !is_dir($toPath)) {
            $this->error("مجلد اللغة غير موجود: {$fromPath} أو {$toPath}");

            return self::FAILURE;
        }

        $files = collect(glob($fromPath . '/*.php'))
            ->merge(glob($toPath . '/*.php'))
            ->map(fn ($p) => basename($p, '.php'))
            ->unique()
            ->when($only, fn ($c) => $c->filter(fn ($f) => $f === $only))
            ->sort()
            ->values();

        if ($files->isEmpty()) {
            $this->warn('مفيش ملفات ترجمة للمقارنة.');

            return self::SUCCESS;
        }

        $rows = [];
        $totalMissing = 0;

        foreach ($files as $file) {
            $fromKeys = $this->keysOf("{$fromPath}/{$file}.php");
            $toKeys   = $this->keysOf("{$toPath}/{$file}.php");

            $missingInTo   = array_values(array_diff($fromKeys, $toKeys));
            $missingInFrom = array_values(array_diff($toKeys, $fromKeys));

            $totalMissing += count($missingInTo) + count($missingInFrom);

            if ($missingInTo || $missingInFrom) {
                $rows[] = [
                    $file,
                    count($fromKeys),
                    count($toKeys),
                    count($missingInTo),
                    count($missingInFrom),
                ];

                $this->newLine();
                $this->line("<fg=yellow>── {$file} ──</>");

                foreach ($missingInTo as $key) {
                    $this->line("  <fg=red>ناقص في {$to}:</>   {$key}");
                }

                foreach ($missingInFrom as $key) {
                    $this->line("  <fg=magenta>ناقص في {$from}:</> {$key}");
                }
            }
        }

        $this->newLine();

        if (!$rows) {
            $this->info("كل المفاتيح متطابقة بين {$from} و {$to}. ✔");

            return self::SUCCESS;
        }

        $this->table(
            ['الملف', "مفاتيح {$from}", "مفاتيح {$to}", "ناقص في {$to}", "ناقص في {$from}"],
            $rows
        );

        $this->error("إجمالي المفاتيح الناقصة: {$totalMissing}");

        return self::FAILURE;
    }

    /** كل المفاتيح المسطّحة (dot notation) في ملف ترجمة. */
    private function keysOf(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        $data = require $path;

        if (!is_array($data)) {
            return [];
        }

        return array_keys(Arr::dot($data));
    }
}
