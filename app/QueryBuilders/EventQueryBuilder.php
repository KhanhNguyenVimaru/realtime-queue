<?php

namespace App\QueryBuilders;

use App\Models\Event;
use App\Models\EventLog;
use App\Models\EventUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class EventQueryBuilder
{
    public static function paginateIndex(Request $request): LengthAwarePaginator
    {
        $perPage = max(1, min($request->integer('per_page', 10), 100));

        return static::buildQuery(
            $request->only(['search', 'sort_by', 'host_id', 'end_date', 'joined_only']),
            $request->user()?->id
        )->paginate($perPage);
    }

    public static function findDetail(int $eventId, ?int $userId): Event
    {
        return static::applyEnrollmentMeta(
            Event::query()->select(['id', 'host_id', 'title', 'description', 'img', 'limit', 'starts_at', 'ends_at', 'created_at', 'updated_at']),
            $userId
        )
            ->whereKey($eventId)
            ->firstOrFail();
    }

    public static function buildQuery(array $filters, ?int $userId = null): Builder
    {
        $query = static::apply(
            Event::query()->select(['id', 'host_id', 'title', 'description', 'img', 'limit', 'starts_at', 'ends_at', 'created_at', 'updated_at']),
            $filters,
            $userId
        );

        return static::applyEnrollmentMeta($query, $userId);
    }

    public static function apply(Builder $query, array $filters, ?int $userId = null): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $innerQuery) use ($search): void {
                $like = '%' . $search . '%';

                $innerQuery
                    ->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        $sortBy = $filters['sort_by'] ?? 'latest';
        if ($sortBy === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $hostId = $filters['host_id'] ?? null;
        if ($hostId !== null && $hostId !== '') {
            $query->where('host_id', $hostId);
        }

        $endDate = $filters['end_date'] ?? null;
        if ($endDate !== null && $endDate !== '') {
            $query->whereDate('ends_at', $endDate);
        }

        $joinedOnly = filter_var($filters['joined_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($joinedOnly) {
            if (! $userId) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('attendees', function (Builder $attendees) use ($userId): void {
                    $attendees
                        ->where('event_user.status', EventUser::STATUS_JOINED)
                        ->where('users.id', $userId);
                });
            }
        }

        return $query;
    }

    public static function applyEnrollmentMeta(Builder $query, ?int $userId): Builder
    {
        $query->withCount([
            'attendees as joined_count' => function (Builder $attendees): void {
                $attendees->where('event_user.status', EventUser::STATUS_JOINED);
            },
        ]);

        if ($userId) {
            $query->withExists([
                'attendees as joined' => function (Builder $attendees) use ($userId): void {
                    $attendees
                        ->where('event_user.status', EventUser::STATUS_JOINED)
                        ->where('users.id', $userId);
                },
            ]);
        } else {
            $query->selectRaw('false as joined');
        }

        return $query;
    }

    public static function buildDashboardPayload(Event $event, ?int $userId, int $perPage): array
    {
        $detail = static::findDetail($event->id, $userId);

        $logs = EventLog::query()
            ->where('event_id', $event->id)
            ->with('user:id,name,email')
            ->latest()
            ->paginate($perPage);

        return [
            'event' => $detail,
            'logs' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ];
    }
}
