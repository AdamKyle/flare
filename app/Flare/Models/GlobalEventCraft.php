<?php

namespace App\Flare\Models;

use Database\Factories\GlobalEventCraftFactory;
use Database\Factories\GlobalEventKillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalEventCraft extends Model {

    use HasFactory;

    protected $table = 'event_goal_participation_crafts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'global_event_goal_id',
        'character_id',
        'crafts',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'global_event_goal_id' => 'integer',
        'character_id'         => 'integer',
        'crafts'               => 'integer',
    ];

    public function globalEventGoal(): BelongsTo {
        return $this->belongsTo(GlobalEventGoal::class, 'global_event_goal_id', 'id');
    }

    public function character(): BelongsTo {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    protected static function newFactory() {
        return GlobalEventCraftFactory::new();
    }
}
