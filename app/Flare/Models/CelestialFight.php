<?php

namespace App\Flare\Models;

use Database\Factories\CelestialFightFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CelestialFight extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'monster_id',
        'character_id',
        'conjured_at',
        'x_position',
        'y_position',
        'damaged_kingdom',
        'stole_treasury',
        'weakened_morale',
        'current_health',
        'max_health',
        'type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'conjured_at'     => 'date',
        'x_position'      => 'integer',
        'y_position'      => 'integer',
        'damaged_kingdom' => 'boolean',
        'stole_treasury'  => 'boolean',
        'weakened_morale' => 'boolean',
        'current_health'  => 'integer',
        'max_health'      => 'integer',
    ];

    public function monster() {
        return $this->belongsTo(Monster::class);
    }

    public function character() {
        return $this->belongsTo(Character::class);
    }

    public function charactersInFight() {
        return $this->charactersInFight(CharacterInCelestialFight::class);
    }

    public function gameMapName(): string {
        return $this->monster->gameMap->name;
    }

    protected static function newFactory() {
        return CelestialFightFactory::new();
    }
}
