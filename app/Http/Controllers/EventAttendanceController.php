<?php

namespace App\Http\Controllers;

use App\Jobs\SendEventAttendanceConfirmation;
use App\Models\Event;
use App\Models\EventAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventAttendanceController extends Controller
{
    public function store(Request $request, Event $event): JsonResponse
    {
        $attendance = EventAttendance::firstOrCreate([
            'user_id' => $request->user()->id,
            'event_id' => $event->id,
        ]);

        if ($attendance->wasRecentlyCreated) {
            SendEventAttendanceConfirmation::dispatch($attendance);
        }

        return response()->json(['booked' => true]);
    }

    public function destroy(Request $request, Event $event): JsonResponse
    {
        EventAttendance::query()
            ->where('user_id', $request->user()->id)
            ->where('event_id', $event->id)
            ->delete();

        return response()->json(['booked' => false]);
    }
}
