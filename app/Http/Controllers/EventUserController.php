<?php

namespace App\Http\Controllers;

use App\Models\EventUser;
use App\Models\Event;
use App\Jobs\UpdateEventAttendeeCount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventUserController extends Controller
{
    public function join(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        $eventUser = EventUser::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            ['status' => EventUser::STATUS_JOINED],
        );

        UpdateEventAttendeeCount::dispatch($event->id);

        return response()->json([
            'message' => 'Joined event successfully.',
            'event_user' => $eventUser->fresh(),
        ], 202);
    }

    public function leave(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        $eventUser = EventUser::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        if ($eventUser) {
            $eventUser->delete();
            UpdateEventAttendeeCount::dispatch($event->id);
        }

        return response()->json([
            'message' => 'Left event successfully.',
        ]);
    }
}
