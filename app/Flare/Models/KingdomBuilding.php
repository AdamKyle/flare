<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\KingdomBuildingFactory;

class KingdomBuilding extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'game_building_id',
        'kingdom_id',
        'level',
        'current_defence',
        'current_durability',
        'max_defence',
        'max_durability',
        'is_locked',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'level'              => 'integer',
        'current_defence'    => 'integer',
        'current_durability' => 'integer',
        'max_defence'        => 'integer',
        'max_durability'     => 'integer',
        'is_locked'          => 'boolean',
    ];

    protected $appends = [
        'name',
        'description',
        'durability',
        'defence',
        'future_durability',
        'future_defence',
        'required_population',
        'base_population',
        'is_walls',
        'is_farm',
        'is_church',
        'gives_resources',
        'trains_units',
        'wood_cost',
        'clay_cost',
        'stone_cost',
        'iron_cost',
        'steel_cost',
        'base_wood_cost',
        'base_clay_cost',
        'base_stone_cost',
        'base_iron_cost',
        'base_steel_cost',
        'population_increase',
        'future_population_increase',
        'time_increase',
        'rebuild_time',
        'morale_increase',
        'morale_decrease',
        'increase_in_wood',
        'future_increase_in_wood',
        'increase_in_clay',
        'future_increase_in_clay',
        'increase_in_stone',
        'future_increase_in_stone',
        'increase_in_iron',
        'future_increase_in_iron',
        'is_at_max_level',
    ];

    public function getNameAttribute() {
        return $this->gameBuilding->name;
    }

    public function getDescriptionAttribute() {
        return $this->gameBuilding->description;
    }

    public function getDurabilityAttribute() {
        if ($this->level > 1) {
            return ($this->level * $this->gameBuilding->increase_durability_amount) + $this->gameBuilding->base_durability;
        }

        return $this->gameBuilding->base_durability;
    }

    public function getDefenceAttribute() {
        if ($this->level > 1) {
            return ($this->level * $this->gameBuilding->increase_defence_amount) + $this->gameBuilding->base_defence;
        }

        return $this->gameBuilding->base_defence;
    }

    public function getFutureDurabilityAttribute() {
        return (($this->level + 1) * $this->gameBuilding->increase_durability_amount) + $this->gameBuilding->base_durability;
    }

    public function getFutureDefenceAttribute() {
        return (($this->level + 1) * $this->gameBuilding->increase_defence_amount) + $this->gameBuilding->base_defence;
    }

    public function getRequiredPopulationAttribute() {
        return ($this->level + 1) * $this->gameBuilding->required_population;
    }

    public function getBasePopulationAttribute() {
        return $this->gameBuilding->required_population;
    }

    public function getIsWallsAttribute() {
        return $this->gameBuilding->is_walls;
    }

    public function getIsFarmAttribute() {
        return $this->gameBuilding->is_farm;
    }

    public function getIsChurchAttribute() {
        return $this->gameBuilding->is_church;
    }

    public function getGivesResourcesAttribute() {
        return $this->gameBuilding->is_resource_building;
    }

    public function getTrainsUnitsAttribute() {
        return $this->gameBuilding->trains_units;
    }

    public function getWoodCostAttribute() {

        if ($this->is_max_level)  {
            return $this->level * $this->gameBuilding->wood_cost;
        }

        return ($this->level + 1) * $this->gameBuilding->wood_cost;
    }

    public function getClayCostAttribute() {

        if ($this->is_max_level)  {
            return $this->level * $this->gameBuilding->clay_cost;
        }

        return ($this->level + 1) * $this->gameBuilding->clay_cost;
    }

    public function getStoneCostAttribute() {

        if ($this->is_max_level)  {
            return $this->level * $this->gameBuilding->stone_cost;
        }

        return ($this->level + 1) * $this->gameBuilding->stone_cost;
    }

    public function getIronCostAttribute() {

        if ($this->is_max_level)  {
            return $this->level * $this->gameBuilding->iron_cost;
        }

        return ($this->level + 1) * $this->gameBuilding->iron_cost;
    }

    public function getSteelCostAttribute() {

        if ($this->is_max_level)  {
            return $this->level * $this->gameBuilding->steel_cost;
        }

        return ($this->level + 1) * $this->gameBuilding->steel_cost;
    }

    public function getBaseWoodCostAttribute() {
        return $this->gameBuilding->wood_cost;
    }

    public function getBaseClayCostAttribute() {
        return $this->gameBuilding->clay_cost;
    }

    public function getBaseStoneCostAttribute() {
        return $this->gameBuilding->stone_cost;
    }

    public function getBaseIronCostAttribute() {
        return $this->gameBuilding->iron_cost;
    }

    public function getBaseSteelCostAttribute() {
        return $this->gameBuilding->steel_cost;
    }

    public function getPopulationIncreaseAttribute() {
        return $this->level * $this->gameBuilding->increase_population_amount;
    }

    public function getFuturePopulationIncreaseAttribute() {
        return ($this->level + 1) * $this->gameBuilding->increase_population_amount;
    }

    public function getTimeIncreaseAttribute() {
        $nextLevel    = $this->level + 1;
        $timeIncrease = $this->gameBuilding->time_to_build;
        $time         = $nextLevel + $timeIncrease;
        $time         = $time + $time * $this->gameBuilding->time_increase_amount;

        $now  = now();
        $time = $now->diffInMinutes($now->copy()->addMinutes($time));

        return $time;
    }

    public function fetchTimeForMultipleLevels(int $level): int {
        $nextLevel    = $this->level + $level;
        $timeIncrease = $this->gameBuilding->time_to_build;
        $time         = $nextLevel + $timeIncrease;
        $time         = $time + $time * $this->gameBuilding->time_increase_amount;

        $now  = now();
        $time = $now->diffInMinutes($now->copy()->addMinutes($time));

        return $time;
    }

    public function getRebuildTimeAttribute() {
        $time = ($this->level * $this->gameBuilding->time_to_build);

        if ($this->level > 1) {
            $time = ($this->level * ($this->gameBuilding->time_to_build) * (1 + $this->gameBuilding->time_increase_amount));
        }

        $now  = now();
        $time = $now->diffInMinutes($now->copy()->addMinutes($time));

        return $time;
    }

    public function getMoraleIncreaseAttribute() {
        return $this->gameBuilding->increase_morale_amount;
    }

    public function getMoraleDecreaseAttribute() {
        return $this->gameBuilding->decrease_morale_amount;
    }

    public function getIncreaseInWoodAttribute() {
        return $this->level * $this->gameBuilding->increase_wood_amount;
    }

    public function getFutureIncreaseInWoodAttribute() {
        return ($this->level + 1) * $this->gameBuilding->increase_wood_amount;
    }

    public function getIncreaseInClayAttribute() {
        return $this->level * $this->gameBuilding->increase_clay_amount;
    }

    public function getFutureIncreaseInClayAttribute() {
        return ($this->level + 1) * $this->gameBuilding->increase_clay_amount;
    }

    public function getIncreaseInStoneAttribute() {
        return $this->level * $this->gameBuilding->increase_stone_amount;
    }

    public function getFutureIncreaseInStoneAttribute() {
        return ($this->level + 1) * $this->gameBuilding->increase_stone_amount;
    }

    public function getIncreaseInIronAttribute() {
        return $this->level * $this->gameBuilding->increase_iron_amount;
    }

    public function getFutureIncreaseInIronAttribute() {
        return ($this->level + 1) * $this->gameBuilding->increase_iron_amount;
    }

    public function getIsAtMaxLevelAttribute() {
        return $this->gameBuilding->max_level === $this->level;
    }

    public function gameBuilding() {
        return $this->belongsTo(GameBuilding::class, 'game_building_id', 'id');
    }

    public function kingdom() {
        return $this->belongsTo(Kingdom::class, 'kingdom_id', 'id');
    }

    protected static function newFactory() {
        return KingdomBuildingFactory::new();
    }
}
