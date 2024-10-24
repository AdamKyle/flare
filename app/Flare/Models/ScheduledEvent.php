<?php

namespace App\Flare\Models;

use App\Game\Events\Values\EventType;
use Database\Factories\ScheduledEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledEvent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'event_type',
        'raid_id',
        'start_date',
        'end_date',
        'description',
        'currently_running',
    ];

    protected $casts = [
        'event_type' => 'integer',
        'raid_id' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'currently_running' => 'boolean',
    ];

    public function raid()
    {
        return $this->hasOne(Raid::class, 'id', 'raid_id');
    }

    public function getTitleOfEvent(): string
    {
        if (! is_null($this->raid)) {
            return $this->raid->name;
        }

        $type = new EventType($this->event_type);

        if ($type->isWeeklyCelestials()) {
            return 'Weekly Celestials';
        }

        if ($type->isWeeklyCurrencyDrops()) {
            return 'Weekly Currency Drops';
        }

        if ($type->isWeeklyFactionLoyaltyEvent()) {
            return 'Weekly Faction Loyalty Event';
        }

        if ($type->isWinterEvent()) {
            return 'The Ice Queen\'s Realm';
        }

        if ($type->isDelusionalMemoriesEvent()) {
            return 'Delusional Memories Event';
        }

        if ($type->isFeedbackEvent()) {
            return 'Tlessa\'s Feedback Event';
        }

        return 'Event Name';
    }

    protected static function newFactory()
    {
        return ScheduledEventFactory::new();
    }
}
