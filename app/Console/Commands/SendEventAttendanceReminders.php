<?php

namespace App\Console\Commands;

use App\Jobs\SendEventAttendanceReminder;
use App\Models\EventAttendance;
use Illuminate\Console\Command;

class SendEventAttendanceReminders extends Command
{
    protected $signature = 'attendances:send-reminders';

    protected $description = 'Dispatch reminder emails for upcoming event attendances';

    public function handle(): int
    {
        $dispatched = 0;

        EventAttendance::query()
            ->with('event')
            ->cursor()
            ->each(function (EventAttendance $attendance) use (&$dispatched) {
                $startsAt = $attendance->event->startsAt();

                if ($startsAt <= 0) {
                    return;
                }

                if (
                    $attendance->reminder_three_days_sent_at === null
                    && $this->isReminderDue($startsAt, 3 * 24 * 3600)
                ) {
                    SendEventAttendanceReminder::dispatch($attendance, 'three_days');
                    $dispatched++;
                }

                if (
                    $attendance->reminder_one_day_sent_at === null
                    && $this->isReminderDue($startsAt, 24 * 3600)
                ) {
                    SendEventAttendanceReminder::dispatch($attendance, 'one_day');
                    $dispatched++;
                }
            });

        $this->info("Dispatched {$dispatched} reminder job(s).");

        return self::SUCCESS;
    }

    /**
     * True when the reminder moment fell within the last hour (hourly scheduler).
     */
    private function isReminderDue(int $eventStartsAt, int $secondsBefore): bool
    {
        $reminderAt = $eventStartsAt - $secondsBefore;
        $now = time();

        return $reminderAt <= $now && $reminderAt > $now - 3600;
    }
}
