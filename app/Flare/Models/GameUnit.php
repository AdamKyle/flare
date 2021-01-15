<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Flare\Models\Traits\WithSearch;
use Database\Factories\GameUnitFactory;

class GameUnit extends Model
{

    use HasFactory, WithSearch;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'attack',
        'deffense',
        'can_heal',
        'unit_can_heal',
        'siege_weapon',
        'travel_time',
        'wood_cost',
        'clay_cost',
        'stone_cost',
        'iron_cost',
        'required_population',
        'time_to_recruit',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'attack'              => 'integer',
        'deffense'            => 'integer',
        'can_heal'            => 'boolean',
        'unit_can_heal'       => 'boolean',
        'siege_weapon'        => 'boolean',
        'travel_time'         => 'integer',
        'wood_cost'           => 'integer',
        'clay_cost'           => 'integer',
        'stone_cost'          => 'integer',
        'iron_cost'           => 'integer',
        'required_population' => 'integer',
        'time_to_recruit'     => 'integer',
    ];

    protected static function newFactory() {
        return GameUnitFactory::new();
    }
}
