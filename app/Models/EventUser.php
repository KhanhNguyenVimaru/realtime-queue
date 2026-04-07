<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventUser extends Model
{
    /** @use HasFactory<\Database\Factories\EventUserFactory> */
    use HasFactory;

    public const STATUS_USER_REQUEST = 'user_request';
    public const STATUS_HOST_INVITATION = 'host_invitation';
    public const STATUS_JOINED = 'joined';

    protected $table = 'event_user';

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
