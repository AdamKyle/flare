<?php

namespace App\Flare\Transformers;

use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\KingdomBuilding;
use Exception;
use League\Fractal\TransformerAbstract;

class CapitalCityKingdomBuildingTransformer extends TransformerAbstract
{
    /**
     * Gets the response data for the character sheet
     *
     * @throws Exception
     */
    public function transform(KingdomBuilding $building): array
    {

        $passiveRequired = null;
        $passiveSkill = $building->gameBuilding->passive;

        if (! is_null($passiveSkill)) {
            $passiveRequired = $passiveSkill->name;
        }

        return [
            'id' => $building->id,
            'kingdom_id' => $building->kingdom_id,
            'game_building_id' => $building->gameBuilding->id,
            'name' => $building->name,
            'description' => $building->description,
            'level' => $building->level,
            'current_defence' => $building->current_defence,
            'current_durability' => $building->current_durability,
            'max_defence' => $building->max_defence,
            'max_durability' => $building->max_durability,
            'population_required' => $building->required_population,
            'is_locked' => $building->is_locked,
            'passive_skill_name' => $passiveRequired,
            'wood_cost' => $building->wood_cost,
            'stone_cost' => $building->stone_cost,
            'clay_cost' => $building->clay_cost,
            'iron_cost' => $building->iron_cost,
            'steel_cost' => $building->steel_cost,
            'population_increase' => $building->population_increase,
            'rebuild_time' => $building->rebuild_time,
            'morale_increase' => $building->morale_increase,
            'morale_decrease' => $building->morale_decrease,
            'wood_increase' => $building->increase_in_wood,
            'clay_increase' => $building->increase_in_clay,
            'stone_increase' => $building->increase_in_stone,
            'iron_increase' => $building->increase_in_iron,
            'max_level' => $building->gameBuilding->max_level,
            'units_for_building' => $this->getUnitDetailsForBuilding($building),
            'passive_required_for_building' => $this->fetchPassiveNameRequiredForBuilding($building),
        ];
    }

    private function getUnitDetailsForBuilding(KingdomBuilding $building): array
    {

        $unitsForBuilding = GameBuildingUnit::where('game_building_id', $building->game_building_id)->get();

        if ($unitsForBuilding->isEmpty()) {
            return [];
        }

        return $unitsForBuilding->map(function ($unitForBuilding) {
            return [
                'unit_name' => $unitForBuilding->gameUnit->name,
                'at_building_level' => $unitForBuilding->required_level,
            ];
        })->toArray();
    }

    private function fetchPassiveNameRequiredForBuilding(KingdomBuilding $building): ?array
    {
        $passiveDetails = null;
        $passiveSkill = $building->gameBuilding->passive;

        if (! is_null($passiveSkill)) {

            $character = $building->kingdom->character;

            $characterPassive = CharacterPassiveSkill::where('character_id', $character->id)->where('passive_skill_id', $passiveSkill->id)->first();

            if (is_null($characterPassive)) {
                return [];
            }

            $passiveDetails = [
                'name' => $passiveSkill->name,
                'is_trained' => $characterPassive->current_level >= $passiveSkill->max_level,
                'required_level' => $passiveSkill->max_level,
            ];
        }

        return $passiveDetails;
    }
}
