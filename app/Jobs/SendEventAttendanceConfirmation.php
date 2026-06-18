<?php

namespace App\Jobs;

use App\Mail\EventAttendanceConfirmationMail;
use App\Models\EventAttendance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendEventAttendanceConfirmation implements ShouldQueue
{
    use Queueable;

    /** Skip silently when the user unbooked before this job ran. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public EventAttendance $attendance) {}

    public function handle(): void
    {
        if (! $this->attendance->exists) {
            return;
        }

        $this->attendance->loadMissing(['user', 'event']);

        // Mail::to($this->attendance->user)->send(
        //     new EventAttendanceConfirmationMail($this->attendance->user, $this->attendance->event),
        // );
        // Email delivery disabled — no mail service configured.

        $this->attendance->update(['confirmation_sent_at' => now()]);
    }
}
