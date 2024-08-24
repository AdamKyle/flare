<?php

namespace App\Flare\Models;

use Database\Factories\GameUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameUnit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'attack',
        'defence',
        'can_heal',
        'heal_percentage',
        'siege_weapon',
        'is_airship',
        'wood_cost',
        'clay_cost',
        'stone_cost',
        'iron_cost',
        'steel_cost',
        'attacker',
        'defender',
        'required_population',
        'time_to_recruit',
        'can_not_be_healed',
        'is_settler',
        'reduces_morale_by',
        'is_special',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'attack' => 'integer',
        'defence' => 'integer',
        'can_heal' => 'boolean',
        'heal_percentage' => 'float',
        'unit_can_heal' => 'boolean',
        'siege_weapon' => 'boolean',
        'is_airship' => 'boolean',
        'attacker' => 'boolean',
        'defender' => 'boolean',
        'can_not_be_healed' => 'boolean',
        'is_settler' => 'boolean',
        'is_special' => 'boolean',
        'reduces_morale_by' => 'float',
        'wood_cost' => 'integer',
        'clay_cost' => 'integer',
        'stone_cost' => 'integer',
        'iron_cost' => 'integer',
        'steel_cost' => 'integer',
        'required_population' => 'integer',
        'time_to_recruit' => 'integer',
    ];

    protected static function newFactory()
    {
        return GameUnitFactory::new();
    }
}
