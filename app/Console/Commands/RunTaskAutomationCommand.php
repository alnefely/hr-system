<?php

namespace App\Console\Commands;

use App\Services\Tasks\TaskRecurrenceService;
use App\Services\Tasks\TaskReminderService;
use App\Services\Tasks\TaskTimeService;
use Illuminate\Console\Command;

/**
 * أتمتة المهام — بيشتغل كل 5 دقايق من الجدولة:
 *   1) إنشاء المهام المتكررة اللي ميعادها جه
 *   2) تذكير قرب التسليم
 *   3) تنبيه المهام المتأخرة
 *   4) تذكير تحديث نسبة الإنجاز
 *   5) قفل العدادات المنسية
 *
 * آمن لو اتشغّل أكتر من مرة: كل خطوة مابتكرّرش اللي اتعمل قبل كده.
 */
class RunTaskAutomationCommand extends Command
{
    protected $signature = 'tasks:automation';

    protected $description = 'Create recurring tasks, send due/overdue/progress reminders, and close forgotten timers';

    public function handle(TaskRecurrenceService $recurrences, TaskReminderService $reminders, TaskTimeService $time): int
    {
        $results = [
            'recurring tasks created' => $recurrences->runDue(),
            'due soon reminders'      => $reminders->sendDueSoon(),
            'overdue alerts'          => $reminders->sendOverdue(),
            'progress reminders'      => $reminders->sendProgressDue(),
            'stale timers closed'     => $reminders->closeStaleTimers($time),
        ];

        foreach ($results as $label => $count) {
            $this->line(sprintf('%-25s %d', $label, $count));
        }

        return self::SUCCESS;
    }
}
