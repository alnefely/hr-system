<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;

/**
 * بوابة تحقق سريعة: كل مفتاح عربي لازم يكون له مقابل إنجليزي.
 *
 * الفرق بينه وبين lang:diff:
 *  - ده اتجاه واحد (ar ← en) وبيرجّع exit code 1 لو في نقص،
 *    فينفع يتحط في CI أو git hook يمنع الدمج.
 *  - lang:diff تقرير تفصيلي في الاتجاهين للاستخدام اليدوي.
 *
 *   php artisan lang:check-missing
 *   php artisan lang:check-missing --from=ar --to=en
 */
class LangCheckMissingCommand extends Command
{
    protected $signature = 'lang:check-missing
        {--from=ar : اللغة المرجعية}
        {--to=en : اللغة اللي بنتأكد إنها مكتملة}';

    protected $description = 'Check missing translation keys between two language directories';

    public function handle(): int
    {
        $from = $this->option('from');
        $to   = $this->option('to');

        $fromPath = lang_path($from);
        $toPath   = lang_path($to);

        if (!is_dir($fromPath) || !is_dir($toPath)) {
            $this->error("Language directories do not exist: {$fromPath} / {$toPath}");

            return self::FAILURE;
        }

        $missingCount = 0;

        foreach (glob($fromPath . '/*.php') as $file) {
            $filename = basename($file);
            $target = $toPath . '/' . $filename;

            if (!file_exists($target)) {
                $this->warn("Missing entire file in {$to}: {$filename}");
                $missingCount++;
                continue;
            }

            $fromData = require $file;
            $toData   = require $target;

            if (!is_array($fromData) || !is_array($toData)) {
                continue;
            }

            $missing = array_diff_key(Arr::dot($fromData), Arr::dot($toData));

            if ($missing) {
                $this->error("In {$filename}, " . count($missing) . " key(s) missing in lang/{$to}:");

                foreach (array_keys($missing) as $key) {
                    $this->line("  - {$key}");
                    $missingCount++;
                }
            }
        }

        if ($missingCount === 0) {
            $this->info("All {$from} translation keys exist in {$to}! Full parity achieved.");

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn("Total missing keys found: {$missingCount}");

        return self::FAILURE;
    }
}
