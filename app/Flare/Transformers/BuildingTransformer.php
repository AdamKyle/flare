<?php

namespace App\Flare\Transformers;


use League\Fractal\TransformerAbstract;
use App\Flare\Models\Building;

class BuildingTransformer extends TransformerAbstract {

    /**
     * Gets the response data for the character sheet
     * 
     * @param Character $character
     * @return mixed
     */
    public function transform(Building $building) {

        return [
            'id'                   => $building->id,
            'kingdoms_id'          => $building->kingdom_id,
            'name'                 => $building->name,
            'description'          => $building->description,
            'level'                => $building->level,
            'current_defence'      => $building->current_defence,
            'current_durability'   => $building->current_durability,
            'max_defence'          => $building->max_defence,
            'max_durability'       => $building->max_durability,
            'population_required'  => $building->required_population,
            'is_wall'              => $building->is_wall,
            'is_church'            => $building->is_church,
            'is_farm'              => $building->is_farm,
            'is_resource_building' => $building->gives_resources,
            'trains_units'         => $building->trains_units,
            'wood_cost'            => $building->wood_cost,
            'stone_cost'           => $building->stone_cost,
            'clay_cost'            => $building->clay_cost,
            'iron_cost'            => $building->iron_cost,
            'population_increase'  => $building->population_increase,
            'time_increase'        => $building->time_increase,
            'morale_increase'      => $building->morale_increase,
            'morale_decrease'      => $building->morale_decrease,
            'wood_increase'        => $building->increase_in_wood,
            'clay_increase'        => $building->increase_in_clay,
            'stone_increase'       => $building->increase_in_stone,
            'iron_increase'        => $building->increase_in_iron,
            'future_wood_increase' => $building->future_increase_in_wood,
            'future_clay_increase' => $building->future_increase_in_clay,
            'future_stone_increase'=> $building->future_increase_in_stone,
            'future_iron_increase' => $building->future_increase_in_iron,
            'is_maxed'             => $building->is_at_max_level,
            'defence_increase'     => $building->future_defence,
            'durability_increase'  => $building->future_durability,
        ];
    }
}
