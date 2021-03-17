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
        'base_wood_cost',
        'base_clay_cost',
        'base_stone_cost',
        'base_iron_cost',
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
        return $this->gameKingdomBuilding->name;
    }

    public function getDescriptionAttribute() {
        return $this->gameKingdomBuilding->description;
    }

    public function getDurabilityAttribute() {
        return $this->gameKingdomBuilding->base_durability * (1 + ($this->level * $this->gameKingdomBuilding->increase_durability_amount));
    }

    public function getDefenceAttribute() {
        return $this->gameKingdomBuilding->base_defence * (1 + ($this->level * $this->gameKingdomBuilding->increase_defence_amount));
    }

    public function getFutureDurabilityAttribute() {
        return number_format($this->gameKingdomBuilding->base_durability * (1 + (($this->level + 1) * $this->gameKingdomBuilding->increase_durability_amount)), 0);
    }

    public function getFutureDefenceAttribute() {
        return number_format($this->gameKingdomBuilding->base_defence * (1 + (($this->level + 1) * $this->gameKingdomBuilding->increase_defence_amount)), 0);
    }

    public function getRequiredPopulationAttribute() {
        return ($this->level + 1) * $this->gameKingdomBuilding->required_population;
    }

    public function getBasePopulationAttribute() {
        return $this->gameKingdomBuilding->required_population;
    }

    public function getIsWallsAttribute() {
        return $this->gameKingdomBuilding->is_walls;
    }

    public function getIsFarmAttribute() {
        return $this->gameKingdomBuilding->is_farm;
    }

    public function getIsChurchAttribute() {
        return $this->gameKingdomBuilding->is_chruch;
    }

    public function getGivesResourcesAttribute() {
        return $this->gameKingdomBuilding->is_resource_building;
    }

    public function getTrainsUnitsAttribute() {
        return $this->gameKingdomBuilding->trains_units;
    }

    public function getWoodCostAttribute() {
        return ($this->level + 1) * $this->gameKingdomBuilding->wood_cost;
    }

    public function getClayCostAttribute() {
        return ($this->level + 1) * $this->gameKingdomBuilding->clay_cost;
    }

    public function getStoneCostAttribute() {
        return ($this->level + 1) * $this->gameKingdomBuilding->stone_cost;
    }

    public function getIronCostAttribute() {
        return ($this->level + 1) * $this->gameKingdomBuilding->iron_cost;
    }

    public function getBaseWoodCostAttribute() {
        return $this->gameKingdomBuilding->wood_cost;;
    }

    public function getBaseClayCostAttribute() {
        return $this->gameKingdomBuilding->clay_cost;;
    }

    public function getBaseStoneCostAttribute() {
        return $this->gameKingdomBuilding->stone_cost;;
    }

    public function getBaseIronCostAttribute() {
        return $this->gameKingdomBuilding->iron_cost;;
    }

    public function getPopulationIncreaseAttribute() {
        return $this->level * $this->gameKingdomBuilding->increase_population_amount;
    }

    public function getFuturePopulationIncreaseAttribute() {
        return ($this->level + 1) * $this->gameKingdomBuilding->increase_population_amount;
    }

    public function getTimeIncreaseAttribute() {
        $time = (($this->level + 1) * ($this->gameKingdomBuilding->time_to_build) * (1 + $this->gameKingdomBuilding->time_increase_amount));

        $now  = now();
        $time = $now->diffInMinutes($now->copy()->addMinutes($time));

        return $time;
    }

    public function getRebuildTimeAttribute() {
        $time = ($this->level * $this->gameKingdomBuilding->time_to_build);

        if ($this->level > 1) {
            $time = ($this->level * ($this->gameKingdomBuilding->time_to_build) * (1 + $this->gameKingdomBuilding->time_increase_amount));
        }
        
        $now  = now();
        $time = $now->diffInMinutes($now->copy()->addMinutes($time));

        return $time;
    }

    public function getMoraleIncreaseAttribute() {
        return $this->gameKingdomBuilding->increase_morale_amount;
    }

    public function getMoraleDecreaseAttribute() {
        return $this->gameKingdomBuilding->decrease_morale_amount;
    }

    public function getIncreaseInWoodAttribute() {
        return round($this->gameKingdomBuilding->increase_wood_amount * (1 + ($this->level / 10)));
    }

    public function getFutureIncreaseInWoodAttribute() {
        return round($this->gameKingdomBuilding->increase_wood_amount * (1 + (($this->level + 1) / 10)));
    }

    public function getIncreaseInClayAttribute() {
        return $this->gameKingdomBuilding->increase_clay_amount;
    }

    public function getFutureIncreaseInClayAttribute() {
        return round($this->gameKingdomBuilding->increase_clay_amount * (1 + (($this->level + 1) / 10)));
    }

    public function getIncreaseInStoneAttribute() {
        return $this->gameKingdomBuilding->increase_stone_amount;
    }

    public function getFutureIncreaseInStoneAttribute() {
        return round($this->gameKingdomBuilding->increase_stone_amount * (1 + (($this->level + 1) / 10)));
    }

    public function getIncreaseInIronAttribute() {
        return $this->gameKingdomBuilding->increase_iron_amount;
    }

    public function getFutureIncreaseInIronAttribute() {
        return round($this->gameKingdomBuilding->increase_iron_amount * (1 + (($this->level + 1) / 10)));
    }

    public function getIsAtMaxLevelAttribute() {
        return $this->gameKingdomBuilding->max_level === $this->level;
    }

    public function gameKingdomBuilding() {
        return $this->belongsTo(GameKingdomBuilding::class, 'game_building_id', 'id');
    }

    public function kingdom() {
        return $this->belongsTo(Kingdom::class, 'kingdom_id', 'id');
    }

    protected static function newFactory() {
        return KingdomBuildingFactory::new();
    }
}
