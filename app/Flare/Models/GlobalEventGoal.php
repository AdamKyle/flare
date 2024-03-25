<?php

namespace App\Flare\Models;

use App\Game\Events\Values\EventType;
use App\Flare\Values\ItemSpecialtyType;
use Database\Factories\GlobalEventGoalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlobalEventGoal extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'max_kills',
        'max_crafts',
        'reward_every',
        'next_reward_at',
        'event_type',
        'item_specialty_type_reward',
        'should_be_unique',
        'unique_type',
        'should_be_mythic',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'max_kills'                     => 'integer',
        'max_crafts'                    => 'integer',
        'reward_every'                  => 'integer',
        'next_reward_at'                => 'integer',
        'event_type'                    => 'integer',
        'should_be_unique'              => 'boolean',
        'unique_type'                   => 'integer',
        'should_be_mythic'              => 'boolean',
    ];

    protected $appends = [
        'total_kills',
        'total_crafts',
    ];

    public function globalEventParticipation(): HasMany {
        return $this->hasMany(GlobalEventParticipation::class, 'global_event_goal_id', 'id');
    }

    public function globalEventKills(): HasMany {
        return $this->hasMany(GlobalEventKill::class, 'global_event_goal_id', 'id');
    }

    public function eventType(): EventType {
        return new EventType($this->event_type);
    }

    public function itemSpecialtyType(): itemSpecialtyType {
        return new ItemSpecialtyType($this->item_specialty_type_reward);
    }

    public function getTotalKillsAttribute(): int {
        return $this->globalEventParticipation->sum('current_kills');
    }

    public function getTotalCraftsAttribute(): int {
        return $this->globalEventParticipation->sum('current_crafts');
    }

    protected static function newFactory() {
        return GlobalEventGoalFactory::new();
    }
}
