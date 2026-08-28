<?php

namespace App\Admin\GameMaps\Transformers;

use App\Flare\Models\GameMap;
use League\Fractal\TransformerAbstract;

class GameMapListTransformer extends TransformerAbstract
{
    /**
     * Transform a Game Map into its Admin list-row representation.
     */
    public function transform(GameMap $gameMap): array
    {
        return [
            'id' => $gameMap->id,
            'name' => $gameMap->name,
        ];
    }
}
