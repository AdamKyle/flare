<?php

namespace App\Game\Maps\Transformers;

use App\Flare\Models\Location;
use League\Fractal\TransformerAbstract;

class LocationsTransformer extends TransformerAbstract
{

    /**
     * Gets the response data for the character sheet
     */
    public function transform(Location $location): array
    {

        return [
            'id'   => $location->id,
            'name' => $location->name,
            'x_position'    => $location->x,
            'y_position'    => $location->y,
            'is_corrupted' => $location->is_corrupted,
            'is_port' => $location->is_port,
        ];
    }
}
