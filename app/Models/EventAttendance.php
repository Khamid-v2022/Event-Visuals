<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAttendance extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'confirmation_sent_at' => 'datetime',
            'reminder_three_days_sent_at' => 'datetime',
            'reminder_one_day_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
