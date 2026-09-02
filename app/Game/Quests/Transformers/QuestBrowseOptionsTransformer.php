<?php

namespace App\Game\Quests\Transformers;

use App\Flare\Models\GameMap;
use Illuminate\Support\Collection;

class QuestBrowseOptionsTransformer
{
    /**
     * Transform the ordered Game Maps into the factual Quest browse-options representation.
     *
     * @param  Collection<int, GameMap>  $gameMaps  Ordered Game Maps to expose as browse options.
     * @return array{default_game_map_id: int|null, game_maps: array<int, array{id: int, name: string}>} Quest browse-options representation.
     */
    public function transform(Collection $gameMaps): array
    {
        $defaultGameMap = $gameMaps->first(fn (GameMap $gameMap) => $gameMap->default);

        return [
            'default_game_map_id' => $defaultGameMap?->id,
            'game_maps' => $gameMaps->map(fn (GameMap $gameMap): array => [
                'id' => $gameMap->id,
                'name' => $gameMap->name,
            ])->values()->all(),
        ];
    }
}
