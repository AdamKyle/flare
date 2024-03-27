<?php

namespace App\Flare\Models;

use Database\Factories\GlobalEventParticipationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalEventParticipation extends Model {

    use HasFactory;

    protected $table = 'global_event_participation';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'global_event_goal_id',
        'character_id',
        'current_kills',
        'current_crafts',
        'current_enchants',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'global_event_goal_id' => 'integer',
        'character_id'         => 'integer',
        'current_kills'        => 'integer',
        'current_crafts'       => 'integer',
        'current_enchants'     => 'integer',
    ];

    public function globalEventGoal(): BelongsTo {
        return $this->belongsTo(GlobalEventGoal::class, 'global_event_goal_id', 'id');
    }

    public function character(): BelongsTo {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    protected static function newFactory() {
        return GlobalEventParticipationFactory::new();
    }
}
