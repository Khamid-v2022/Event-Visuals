<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventInterest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventInterestController extends Controller
{
    public function store(Request $request, Event $event): JsonResponse
    {
        EventInterest::firstOrCreate([
            'user_id' => $request->user()->id,
            'event_id' => $event->id,
        ]);

        return response()->json(['interested' => true]);
    }

    public function destroy(Request $request, Event $event): JsonResponse
    {
        EventInterest::query()
            ->where('user_id', $request->user()->id)
            ->where('event_id', $event->id)
            ->delete();

        return response()->json(['interested' => false]);
    }
}
