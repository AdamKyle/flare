<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalEventParticipation extends Model {

    protected $table = 'global_event_participation';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'global_event_goal_id',
        'character_id',
        'current_kills'
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
    ];

    public function globalEventGoal(): BelongsTo {
        return $this->belongsTo(GlobalEventGoal::class, 'global_event_goal_id', 'id');
    }

    public function character(): BelongsTo {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }
}
