<?php

namespace App\Admin\GameMaps\Transformers;

use App\Flare\Models\Location;

class GameMapLocationMarkerTransformer
{
    /**
     * Transform a Location into its Game Map editor marker representation.
     */
    public function transform(Location $location): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'x' => $location->x,
            'y' => $location->y,
            'is_port' => $location->is_port,
            'is_corrupted' => $location->is_corrupted,
            'pin_css_class' => $location->pin_css_class,
        ];
    }
}
