<?php

namespace App\Admin\GameMaps\Transformers;

use App\Flare\Models\Location;
use League\Fractal\TransformerAbstract;

class GameMapRelatedLocationTransformer extends TransformerAbstract
{
    /**
     * Transform a Location into its compact Game Map relationship representation.
     */
    public function transform(Location $location): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'type' => $location->type,
            'x' => $location->x,
            'y' => $location->y,
        ];
    }
}
