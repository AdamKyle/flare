<?php

namespace App\Admin\LocationGems\Transformers;

use App\Flare\Models\GameLocationGemParamter;
use League\Fractal\TransformerAbstract;

class LocationGemListTransformer extends TransformerAbstract
{
    /**
     * Transform a Location Gem profile into its Admin list-row representation.
     *
     * @param  GameLocationGemParamter  $gameLocationGemParamter  Location Gem profile to transform.
     * @return array{id: int, name: string, game_map: array{id: int, name: string}, location: array{id: int, name: string}, roll_count: int, rolled_gem: array{id: int, name: string, roll_number: int}|null} Admin Location Gem list-row representation.
     */
    public function transform(GameLocationGemParamter $gameLocationGemParamter): array
    {
        return [
            'id' => $gameLocationGemParamter->id,
            'name' => $gameLocationGemParamter->name,
            'game_map' => [
                'id' => $gameLocationGemParamter->location->map->id,
                'name' => $gameLocationGemParamter->location->map->name,
            ],
            'location' => [
                'id' => $gameLocationGemParamter->location->id,
                'name' => $gameLocationGemParamter->location->name,
            ],
            'roll_count' => $gameLocationGemParamter->roll_count,
            'rolled_gem' => is_null($gameLocationGemParamter->rolledGem) ? null : [
                'id' => $gameLocationGemParamter->rolledGem->id,
                'name' => $gameLocationGemParamter->rolledGem->name,
                'roll_number' => $gameLocationGemParamter->rolledGem->roll_number,
            ],
        ];
    }
}
