<?php

namespace App\Admin\Kingdoms\Transformers;

use App\Flare\Models\GameUnit;

class UnitFormTransformer
{
    /**
     * Transform a Kingdom Unit into its Admin save-response / form-value representation.
     *
     * @param GameUnit $gameUnit
     * @return array
     */
    public function transform(GameUnit $gameUnit): array
    {
        return [
            'id' => $gameUnit->id,
            'name' => $gameUnit->name,
            'description' => $gameUnit->description,
            'attack' => $gameUnit->attack,
            'defence' => $gameUnit->defence,
            'can_heal' => $gameUnit->can_heal,
            'heal_percentage' => $gameUnit->heal_percentage,
            'siege_weapon' => $gameUnit->siege_weapon,
            'is_airship' => $gameUnit->is_airship,
            'attacker' => $gameUnit->attacker,
            'defender' => $gameUnit->defender,
            'can_not_be_healed' => $gameUnit->can_not_be_healed,
            'is_settler' => $gameUnit->is_settler,
            'is_special' => $gameUnit->is_special,
            'reduces_morale_by' => $gameUnit->reduces_morale_by,
            'wood_cost' => $gameUnit->wood_cost,
            'clay_cost' => $gameUnit->clay_cost,
            'stone_cost' => $gameUnit->stone_cost,
            'iron_cost' => $gameUnit->iron_cost,
            'steel_cost' => $gameUnit->steel_cost,
            'required_population' => $gameUnit->required_population,
            'time_to_recruit' => $gameUnit->time_to_recruit,
        ];
    }
}
