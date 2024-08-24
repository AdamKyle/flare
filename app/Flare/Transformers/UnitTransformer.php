<?php

namespace App\Flare\Transformers;

use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Kingdoms\Values\UnitCosts;
use Exception;
use League\Fractal\TransformerAbstract;

class UnitTransformer extends TransformerAbstract
{
    /**
     * Gets the response data for the character sheet
     *
     * @throws Exception
     */
    public function transform(GameUnit $unit): array
    {
        return [
            'id' => $unit->id,
            'name' => $unit->name,
            'description' => $unit->description,
            'attack' => $unit->attack,
            'defence' => $unit->defence,
            'can_heal' => $unit->can_heal,
            'heal_percentage' => $unit->heal_percentage,
            'siege_weapon' => $unit->siege_weapon,
            'attacker' => $unit->attacker,
            'defender' => $unit->defender,
            'travel_time' => $unit->travel_time,
            'wood_cost' => $unit->wood_cost,
            'clay_cost' => $unit->clay_cost,
            'stone_cost' => $unit->stone_cost,
            'iron_cost' => $unit->iron_cost,
            'steel_cost' => $unit->steel_cost,
            'required_population' => $unit->required_population,
            'time_to_recruit' => $unit->time_to_recruit,
            'max_amount' => KingdomMaxValue::MAX_UNIT,
            'cost_per_unit' => (new UnitCosts($unit->name))->fetchCost(),
            'pop_cost_gold' => (new UnitCosts(UnitCosts::PERSON))->fetchCost(),
            'recruited_from' => GameBuildingUnit::where('game_unit_id', $unit->id)->first(),
            'required_building_level' => $this->getRequiredBuildingLevel($unit),
            'is_special' => $unit->is_special,
        ];
    }

    protected function getRequiredBuildingLevel(GameUnit $unit): int
    {
        $gameBuilding = GameBuildingUnit::where('game_unit_id', $unit->id)->first();

        if (is_null($gameBuilding)) {
            throw new Exception('Missing Game Building for unit: '.$unit->name);
        }

        return $gameBuilding->required_level;
    }
}
