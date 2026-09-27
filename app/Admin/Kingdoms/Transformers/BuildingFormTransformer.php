<?php

namespace App\Admin\Kingdoms\Transformers;

use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;
use Illuminate\Support\Collection;

class BuildingFormTransformer
{
    /**
     * Transform a Kingdom Building into its Admin save-response / form-value representation, with Unit ids in recruitment order.
     *
     * @param GameBuilding $gameBuilding
     * @param Collection $recruitableUnits
     * @return array
     */
    public function transform(GameBuilding $gameBuilding, Collection $recruitableUnits): array
    {
        return [
            'id' => $gameBuilding->id,
            'name' => $gameBuilding->name,
            'description' => $gameBuilding->description,
            'max_level' => $gameBuilding->max_level,
            'base_durability' => $gameBuilding->base_durability,
            'base_defence' => $gameBuilding->base_defence,
            'required_population' => $gameBuilding->required_population,
            'is_walls' => $gameBuilding->is_walls,
            'is_church' => $gameBuilding->is_church,
            'is_farm' => $gameBuilding->is_farm,
            'is_resource_building' => $gameBuilding->is_resource_building,
            'trains_units' => $gameBuilding->trains_units,
            'is_locked' => $gameBuilding->is_locked,
            'is_special' => $gameBuilding->is_special,
            'wood_cost' => $gameBuilding->wood_cost,
            'clay_cost' => $gameBuilding->clay_cost,
            'stone_cost' => $gameBuilding->stone_cost,
            'iron_cost' => $gameBuilding->iron_cost,
            'steel_cost' => $gameBuilding->steel_cost,
            'increase_population_amount' => $gameBuilding->increase_population_amount,
            'increase_morale_amount' => $gameBuilding->increase_morale_amount,
            'decrease_morale_amount' => $gameBuilding->decrease_morale_amount,
            'increase_wood_amount' => $gameBuilding->increase_wood_amount,
            'increase_clay_amount' => $gameBuilding->increase_clay_amount,
            'increase_stone_amount' => $gameBuilding->increase_stone_amount,
            'increase_iron_amount' => $gameBuilding->increase_iron_amount,
            'increase_durability_amount' => $gameBuilding->increase_durability_amount,
            'increase_defence_amount' => $gameBuilding->increase_defence_amount,
            'time_to_build' => $gameBuilding->time_to_build,
            'time_increase_amount' => $gameBuilding->time_increase_amount,
            'units_per_level' => $gameBuilding->units_per_level,
            'only_at_level' => $gameBuilding->only_at_level,
            'passive_skill_id' => $gameBuilding->passive_skill_id,
            'level_required' => $gameBuilding->level_required,
            'unit_ids' => $recruitableUnits->map(fn (GameBuildingUnit $buildingUnit): int => $buildingUnit->game_unit_id)->values()->all(),
        ];
    }
}
