<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterInCelestialFight extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'celestial_fight_id',
        'character_max_health',
        'character_current_health',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'character_max_health'     => 'integer',
        'character_current_health' => 'integer',
    ];
}
