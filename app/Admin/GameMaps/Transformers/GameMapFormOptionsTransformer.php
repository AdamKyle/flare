<?php

namespace App\Admin\GameMaps\Transformers;

use App\Flare\Models\Location;
use Illuminate\Database\Eloquent\Collection;

class GameMapFormOptionsTransformer
{
    /**
     * Transform the supplied internal Game Map form option data into its Admin API representation.
     */
    public function transform(array $formOptions): array
    {
        /** @var Collection<int, Location> $locations */
        $locations = $formOptions['locations'];

        return [
            'event_types' => $formOptions['event_types'],
            'locations' => $locations->map(fn (Location $location): array => [
                'id' => $location->id,
                'name' => $location->name,
            ])->values()->all(),
        ];
    }
}
