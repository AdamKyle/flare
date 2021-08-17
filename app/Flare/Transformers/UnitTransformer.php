<?php

namespace App\Flare\Transformers;

use App\Flare\Models\GameBuildingUnit;
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
            'id'                   => $unit->id,
            'name'                 => $unit->name,
            'description'          => $unit->description,
            'attack'               => $unit->attack,
            'defence'              => $unit->defence,
            'can_heal'             => $unit->can_heal,
            'heal_percentage'      => $unit->heal_percentage,
            'siege_weapon'         => $unit->siege_weapon,
            'attacker'             => $unit->attacker,
            'defender'             => $unit->defender,
            'travel_time'          => $unit->travel_time,
            'wood_cost'            => $unit->wood_cost,
            'clay_cost'            => $unit->clay_cost,
            'stone_cost'           => $unit->stone_cost,
            'iron_cost'            => $unit->iron_cost,
            'required_population'  => $unit->required_population,
            'time_to_recruit'      => $unit->time_to_recruit,
            'current_amount'       => $unit->kingdom_current_amount,
            'max_amount'           => $unit->kingdom_max_amount,
            'kd_max'               => $unit->max_recruitable,
            'can_recruit_more'     => $unit->can_recruit_more,
            'recruited_from'       => GameBuildingUnit::where('game_unit_id', $unit->id)->first()->gameBuilding,
        ];
    }
}
