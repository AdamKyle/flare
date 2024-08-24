<?php

namespace App\Flare\Models;

use Database\Factories\CharacterInCelestialFightFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharacterInCelestialFight extends Model
{
    use HasFactory;

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
        'character_max_health' => 'integer',
        'character_current_health' => 'integer',
    ];

    protected static function newFactory()
    {
        return CharacterInCelestialFightFactory::new();
    }
}
