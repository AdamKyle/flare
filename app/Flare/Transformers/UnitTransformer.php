<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Models\GameUnit;

class UnitTransformer extends TransformerAbstract {

    /**
     * Gets the response data for the character sheet
     * 
     * @param Character $character
     * @return mixed
     */
    public function transform(GameUnit $unit) {

        return [
            'name'                 => $unit->name,
            'description'          => $unit->description,
            'attack'               => $unit->attack,
            'deffense'             => $unit->defense,
            'can_heal'             => $unit->can_heal,
            'heal_amount'          => $unit->heal_amount,
            'siege_weapon'         => $unit->siege_weapon,
            'attacker'             => $unit->attacker,
            'defender'             => $unit->defender,
            'weak_against_unit'    => GameUnit::find($unit->weak_against_unit_id),
            'travel_time'          => $unit->travel_time,
            'wood_cost'            => $unit->wood_cost,
            'clay_cost'            => $unit->clay_cost,
            'stone_cost'           => $unit->stone_cost,
            'iron_cost'            => $unit->iron_cost,
            'required_population'  => $unit->required_population,
            'time_to_recruit'      => $unit->time_to_recruit,
            'current_amount'       => $unit->kingdom_current_amount,
            'max_amount'           => $unit->kingdom_max_amount,
        ];
    }
}
