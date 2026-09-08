<?php

namespace App\Admin\MapGems\Transformers;

use App\Flare\Models\GameMapGemParamter;
use League\Fractal\TransformerAbstract;

class MapGemListTransformer extends TransformerAbstract
{
    /**
     * Transform a Map Gem profile into its Admin list-row representation.
     */
    public function transform(GameMapGemParamter $gameMapGemParamter): array
    {
        return [
            'id' => $gameMapGemParamter->id,
            'name' => $gameMapGemParamter->name,
            'game_map' => [
                'id' => $gameMapGemParamter->gameMap->id,
                'name' => $gameMapGemParamter->gameMap->name,
            ],
            'roll_count' => $gameMapGemParamter->roll_count,
            'rolled_gem' => is_null($gameMapGemParamter->rolledGem) ? null : [
                'id' => $gameMapGemParamter->rolledGem->id,
                'name' => $gameMapGemParamter->rolledGem->name,
                'roll_number' => $gameMapGemParamter->rolledGem->roll_number,
            ],
        ];
    }
}
