<?php

namespace App\Admin\Locations\Transformers;

use App\Flare\Models\Location;
use League\Fractal\TransformerAbstract;

class LocationListTransformer extends TransformerAbstract
{
    /**
     * Transform a Location into its Admin list-row representation.
     */
    public function transform(Location $location): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'map_name' => $location->map?->name,
            'type' => $location->type,
            'x' => $location->x,
            'y' => $location->y,
        ];
    }
}
