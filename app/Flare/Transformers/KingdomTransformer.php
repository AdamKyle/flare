<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Models\Kingdom;
use App\Flare\Transformers\Traits\BuildingsTransfromerTrait;

class KingdomTransformer extends TransformerAbstract {

    use BuildingsTransfromerTrait;

    /**
     * Gets the response data for the character sheet
     * 
     * @param Character $character
     * @return mixed
     */
    public function transform(Kingdom $kingdom) {

        return [
            'character_id'       => $kingdom->character_id,
            'game_map_id'        => $kingdom->game_map_id,
            'name'               => $kingdom->name,
            'color'              => $this->getHexColor($kingdom->color),
            'max_stone'          => $kingdom->max_stone,
            'max_wood'           => $kingdom->max_wood,
            'max_clay'           => $kingdom->max_clay,
            'max_iron'           => $kingdom->max_iron,
            'current_stone'      => $kingdom->current_stone,
            'current_wood'       => $kingdom->current_wood,
            'current_clay'       => $kingdom->current_clay,
            'current_iron'       => $kingdom->current_iron,
            'current_population' => $kingdom->current_population,
            'max_population'     => $kingdom->max_population,
            'x_position'         => $kingdom->x_position,
            'y_position'         => $kingdom->y_position,
            'current_morale'     => $kingdom->current_morale,
            'max_morale'         => $kingdom->max_morale,
            'treasury'           => $kingdom->treasurey,
            'buildings'          => $kingdom->buildings->load('gameBuilding'),
        ];
    }

    protected function getHexColor(array $color) {
        return sprintf("#%02x%02x%02x", $color[0], $color[1], $color[2]);
    }
}
