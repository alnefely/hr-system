<?php

namespace App\Console\Commands;

use App\Services\Finance\PettyCashService;
use Illuminate\Console\Command;

/**
 * ترحيل أرصدة العهدة النقدية للشهر الحالي.
 *
 * المنطق ده كان بيتنفذ داخل ExpenseController@index — يعني كتابة في
 * قاعدة البيانات مع كل زيارة GET لصفحة المصاريف، وبعدد استعلامات
 * بيزيد مع كل فرع جديد. دلوقتي بيتنفذ مرة واحدة أول كل شهر.
 *
 *   php artisan petty-cash:carry-over
 *   php artisan petty-cash:carry-over --year=2026 --month=10
 */
class CarryOverPettyCashCommand extends Command
{
    protected $signature = 'petty-cash:carry-over
        {--year= : السنة المستهدفة (الافتراضي: السنة الحالية)}
        {--month= : الشهر المستهدف (الافتراضي: الشهر الحالي)}';

    protected $description = 'ترحيل المتبقي من عهدة الشهر السابق لرصيد الشهر الحالي لكل الفروع';

    public function handle(PettyCashService $service): int
    {
        $year  = (int) ($this->option('year') ?: now()->year);
        $month = (int) ($this->option('month') ?: now()->month);

        if ($month < 1 || $month > 12) {
            $this->error('الشهر لازم يكون بين 1 و 12.');

            return self::FAILURE;
        }

        $this->line(sprintf('ترحيل أرصدة العهدة لشهر <fg=cyan>%02d/%d</>...', $month, $year));

        $carried = $service->carryOverAll($year, $month);

        if ($carried === 0) {
            $this->info('مفيش أرصدة محتاجة ترحيل. ✔');

            return self::SUCCESS;
        }

        $this->info("تم ترحيل الرصيد لـ {$carried} فرع. ✔");

        return self::SUCCESS;
    }
}
