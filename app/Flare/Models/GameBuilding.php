<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Flare\Models\Traits\WithSearch;
use Database\Factories\GameBuildingFactory;

class GameBuilding extends Model
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
        'max_level',
        'base_durability',
        'base_defence',
        'required_population',
        'is_walls',
        'is_church',
        'is_farm',
        'is_resource_building',
        'trains_units',
        'wood_cost',
        'clay_cost',
        'stone_cost',
        'iron_cost',
        'increase_population_amount',
        'increase_morale_amount',
        'decrease_morale_amount',
        'increase_wood_amount',
        'increase_clay_amount',
        'increase_stone_amount',
        'increase_iron_amount',
        'increase_durability_amount',
        'increase_defence_amount',
        'time_to_build',
        'time_increase_amount',
        'units_per_level',
        'only_at_level',
        'is_locked',
        'passive_skill_id',
        'level_required',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'max_level'                   => 'integer',
        'base_durability'             => 'integer',
        'base_defence'                => 'integer',
        'required_population'         => 'integer',
        'level_required'              => 'integer',
        'is_walls'                    => 'boolean',
        'is_church'                   => 'boolean',
        'is_farm'                     => 'boolean',
        'is_resource_building'        => 'boolean',
        'trains_units'                => 'boolean',
        'is_locked'                   => 'boolean',
        'wood_cost'                   => 'integer',
        'clay_cost'                   => 'integer',
        'stone_cost'                  => 'integer',
        'iron_cost'                   => 'integer',
        'increase_population_amount'  => 'integer',
        'decrease_morale_amount'      => 'float',
        'increase_morale_amount'      => 'float',
        'increase_wood_amount'        => 'float',
        'increase_clay_amount'        => 'float',
        'increase_stone_amount'       => 'float',
        'increase_iron_amount'        => 'float',
        'increase_durability_amount'  => 'float',
        'increase_defence_amount'     => 'float',
        'time_to_build'               => 'float',     
        'time_increase_amount'        => 'float',
        'units_per_level'             => 'integer',
        'only_at_level'               => 'integer',
        'is_locked'                   => 'boolean',
    ];

    public function units() {
        return $this->hasMany(GameBuildingUnit::class);
    }

    public function passive() {
        return $this->hasOne(PassiveSkill::class, 'id', 'passive_skill_id');
    }

    protected static function newFactory() {
        return GameBuildingFactory::new();
    }
}
