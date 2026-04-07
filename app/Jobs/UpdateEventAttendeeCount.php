<?php

namespace App\Jobs;

use App\Events\EventAttendeeCountUpdated;
use App\Models\EventUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class UpdateEventAttendeeCount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $eventId;

    public function __construct(int $eventId)
    {
        $this->eventId = $eventId;
    }

    public function handle(): void
    {
        $joinedCount = EventUser::query()
            ->where('event_id', $this->eventId)
            ->where('status', EventUser::STATUS_JOINED)
            ->count();

        Cache::put(self::cacheKey($this->eventId), $joinedCount);

        broadcast(new EventAttendeeCountUpdated($this->eventId, $joinedCount));
    }

    public static function cacheKey(int $eventId): string
    {
        return 'event:' . $eventId . ':joined_count';
    }
}
