<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class Faction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'game_map_id',
        'current_level',
        'current_points',
        'points_needed',
        'maxed',
        'title',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'current_level' => 'integer',
        'current_points' => 'integer',
        'points_needed' => 'integer',
        'maxed' => 'boolean',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function gameMap()
    {
        return $this->belongsTo(GameMap::class, 'game_map_id', 'id');
    }
}
