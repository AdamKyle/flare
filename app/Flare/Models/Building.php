<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\BuildingFactory;

class Building extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'game_building_id',
        'kingdoms_id',
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

    public function getNameAttribute() {
        return $this->gameBuilding->name;
    }

    public function getDescriptionAttribute() {
        return $this->gameBuilding->description;
    }

    public function getDurabilityAttribute() {
        if ($this->level === 1) {
            return $this->gameBuilding->base_durability;
        }

        return $this->gameBuilding->base_durability * (($this->level + 1) + $this->gameBuilding->increase_durability_amount);
    }

    public function getDefenceAttribute() {
        if ($this->level === 1) {
            return $this->gameBuilding->base_defence;
        }

        return $this->gameBuilding->base_defence * (($this->level + 1) + $this->gameBuilding->increase_defence_amount);
    }

    public function getRequiredPopulationAttribute() {
        return ($this->level + 1) * $this->gameBuilding->required_population;
    }

    public function getIsWallsAttribute() {
        return $this->gameBuilding->is_wall;
    }

    public function getIsFarmAttribute() {
        return $this->gameBuilding->is_farm;
    }

    public function getIsChurchAttribute() {
        return $this->gameBuilding->is_chruch;
    }

    public function getGivesResourcesAttribute() {
        return $this->gameBuilding->is_resource_building;
    }

    public function getTrainsUnitsAttribute() {
        return $this->gameBuilding->trains_units;
    }

    public function getWoodCostAttribute() {
        return ($this->level + 1) * $this->gameBuilding->wood_cost;
    }

    public function getClayCostAttribute() {
        return ($this->level + 1) * $this->gameBuilding->clay_cost;
    }

    public function getStoneCostAttribute() {
        return ($this->level + 1) * $this->gameBuilding->stone_cost;
    }

    public function getIronCostAttribute() {
        return ($this->level + 1) * $this->gameBuilding->iron_cost;
    }

    public function getPopulationIncreaseAttribute() {
        return $this->gameBuilding->increase_population_amount;
    }

    public function getTimeIncreaseAttribute() {
        $time = (($this->level + 1) * ($this->gameBuilding->time_to_build) * (1 + $this->gameBuilding->time_increase_amount));

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
        return $this->gameBuilding->increase_wood_amount;
    }

    public function getIncreaseInClayAttribute() {
        return $this->gameBuilding->increase_clay_amount;
    }

    public function getIncreaseInStoneAttribute() {
        return $this->gameBuilding->increase_stone_amount;
    }

    public function getIncreaseInIronAttribute() {
        return $this->gameBuilding->increase_iron_amount;
    }

    public function getIsAtMaxLevelAttribute() {
        return $this->gameBuilding->max_level === $this->level;
    }

    public function gameBuilding() {
        return $this->belongsTo(GameBuilding::class, 'game_building_id', 'id');
    }

    public function kingdom() {
        return $this->belongsTo(Kingdom::class, 'kingdoms_id', 'id');
    }

    protected static function newFactory() {
        return BuildingFactory::new();
    }
}
