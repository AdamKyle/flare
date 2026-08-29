<?php

namespace App\Admin\Locations\Transformers;

use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Game\Maps\Values\Coordinates;
use App\Game\Maps\Values\LocationPin;
use App\Game\Maps\Values\LocationType;
use Illuminate\Database\Eloquent\Collection;

class LocationFormOptionsTransformer
{
    /**
     * Transform the supplied internal Location form option data into its Admin API representation.
     *
     * @param  array{game_map: GameMap, quest_items: Collection<int, Item>, location_types: array<int, LocationType>, special_pins: array<int, LocationPin>, coordinates: Coordinates}  $formOptions  Internal Location form option data.
     * @return array{game_map: array{id: int, name: string}, quest_items: array<int, array{value: int, label: string}>, location_types: array<int, int>, special_pins: array<int, string>, coordinates: array{x: array<int, int>, y: array<int, int>}} Admin Location form-options representation.
     */
    public function transform(array $formOptions): array
    {
        /** @var GameMap $gameMap */
        $gameMap = $formOptions['game_map'];

        /** @var Collection<int, Item> $questItems */
        $questItems = $formOptions['quest_items'];

        /** @var Coordinates $coordinates */
        $coordinates = $formOptions['coordinates'];

        return [
            'game_map' => [
                'id' => $gameMap->id,
                'name' => $gameMap->name,
            ],
            'quest_items' => $questItems->map(fn (Item $item): array => [
                'value' => $item->id,
                'label' => $item->name,
            ])->values()->all(),
            'location_types' => array_map(
                fn (LocationType $locationType): int => $locationType->value,
                $formOptions['location_types']
            ),
            'special_pins' => array_map(
                fn (LocationPin $locationPin): string => $locationPin->value,
                $formOptions['special_pins']
            ),
            'coordinates' => [
                'x' => $coordinates->x,
                'y' => $coordinates->y,
            ],
        ];
    }
}
