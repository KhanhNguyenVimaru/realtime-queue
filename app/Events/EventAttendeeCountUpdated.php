<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventAttendeeCountUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $eventId;
    public int $joinedCount;

    public function __construct(int $eventId, int $joinedCount)
    {
        $this->eventId = $eventId;
        $this->joinedCount = $joinedCount;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('event.' . $this->eventId);
    }

    public function broadcastAs(): string
    {
        return 'event.attendees.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'joined_count' => $this->joinedCount,
        ];
    }
}
