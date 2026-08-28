<?php

namespace App\Admin\GameMaps\Transformers;

use App\Flare\Models\Location;
use Illuminate\Database\Eloquent\Collection;

class GameMapFormOptionsTransformer
{
    /**
     * Transform the supplied internal Game Map form option data into its Admin API representation.
     *
     * @param  array{event_types: array<int,string>, locations: Collection<int,Location>}  $formOptions  Internal Game Map form options.
     * @return array{event_types: array<int,array{value: int, label: string}>, locations: array<int,array{id: int, name: string}>} Admin Game Map form options.
     */
    public function transform(array $formOptions): array
    {
        /** @var Collection<int, Location> $locations */
        $locations = $formOptions['locations'];

        return [
            'event_types' => collect($formOptions['event_types'])
                ->map(fn (string $label, int $value): array => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values()
                ->all(),
            'locations' => $locations->map(fn (Location $location): array => [
                'id' => $location->id,
                'name' => $location->name,
            ])->values()->all(),
        ];
    }
}
