<?php

namespace App\Flare\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'started_at',
        'ends_at',
        'raid_id',
        'scheduled_event_id',
        'event_goal_steps',
        'current_event_goal_step',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'type' => 'integer',
        'raid_id' => 'integer',
        'scheduled_event_id' => 'integer',
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'event_goal_steps' => 'array',
        'current_event_goal_step' => 'string',
    ];

    public function raid()
    {
        return $this->hasOne(Raid::class, 'id', 'raid_id');
    }

    public function announcement()
    {
        return $this->hasOne(Announcement::class, 'id', 'event_id');
    }

    public function scheduledEvent(): BelongsTo
    {
        return $this->belongsTo(ScheduledEvent::class, 'scheduled_event_id', 'id');
    }

    public function globalEventGoals(): HasMany
    {
        return $this->hasMany(GlobalEventGoal::class, 'event_id', 'id');
    }

    protected static function newFactory()
    {
        return EventFactory::new();
    }
}
