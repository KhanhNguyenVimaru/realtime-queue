<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\QueryBuilders\EventQueryBuilder;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function __construct(private EventService $eventService)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'events' => EventQueryBuilder::buildQuery(request())
                ->get()
                ->map(fn (Event $event) => $this->eventPayload($event)),
        ]);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = $this->eventService->create($request->user()->id, $request->validated());

        return response()->json([
            'message' => 'Event created successfully.',
            'event' => $this->eventPayload($event->fresh()),
        ], 201);
    }

    public function show(Event $event): JsonResponse
    {
        return response()->json([
            'event' => $this->eventPayload($event),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $event = $this->eventService->update($event, $request->validated());

        return response()->json([
            'message' => 'Event updated successfully.',
            'event' => $this->eventPayload($event->fresh()),
        ]);
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully.',
        ]);
    }

    private function eventPayload(Event $event): array
    {
        return [
            'id' => $event->id,
            'host_id' => $event->host_id,
            'title' => $event->title,
            'description' => $event->description,
            'starts_at' => optional($event->starts_at)?->toIso8601String(),
            'ends_at' => optional($event->ends_at)?->toIso8601String(),
            'created_at' => optional($event->created_at)?->toIso8601String(),
            'updated_at' => optional($event->updated_at)?->toIso8601String(),
        ];
    }
}
