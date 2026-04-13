<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Http\Requests\EventHostRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\QueryBuilders\EventQueryBuilder;
use App\QueryBuilders\UserQueryBuilder;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(private EventService $eventService){
    }

    public function index(Request $request): JsonResponse
    {
        $events = EventQueryBuilder::paginateIndex($request);

        return response()->json(paginate_payload($events, 'events'));
    }

    public function show(Event $event): JsonResponse
    {
        $detail = EventQueryBuilder::findDetail($event->id, request()->user()?->id);

        return response()->json([
            'event' => $detail,
        ]);
    }

    public function dashboard(EventHostRequest $request, Event $event): JsonResponse
    {
        $perPage = max(1, min($request->integer('per_page', 10), 50));
        return response()->json(
            EventQueryBuilder::buildDashboardPayload($event, $request->user()?->id, $perPage)
        );
    }

    public function users(EventHostRequest $request, Event $event): JsonResponse
    {
        $perPage = max(1, min($request->integer('per_page', 10), 100));
        $users = UserQueryBuilder::buildByEventId($event->id)->paginate($perPage);

        return response()->json(paginate_payload($users, 'users'));
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = $this->eventService->create($request->user()->id, $request->validated());

        return response()->json([
            'message' => 'Event created successfully.',
            'event' => $event->fresh(),
        ], 201);
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $event = $this->eventService->update($event, $request->validated());

        return response()->json([
            'message' => 'Event updated successfully.',
            'event' => $event->fresh(),
        ]);
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully.',
        ]);
    }

}
