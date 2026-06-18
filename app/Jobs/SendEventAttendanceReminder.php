<?php

namespace App\Jobs;

use App\Mail\EventAttendanceReminderMail;
use App\Models\EventAttendance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendEventAttendanceReminder implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EventAttendance $attendance,
        public string $window,
    ) {}

    public function handle(): void
    {
        $this->attendance->loadMissing(['user', 'event']);

        $label = $this->window === 'three_days' ? '3-day' : '24-hour';

        // Mail::to($this->attendance->user)->send(
        //     new EventAttendanceReminderMail($this->attendance->user, $this->attendance->event, $label),
        // );
        // Email delivery disabled — no mail service configured.

        $column = $this->window === 'three_days'
            ? 'reminder_three_days_sent_at'
            : 'reminder_one_day_sent_at';

        $this->attendance->update([$column => now()]);
    }
}
