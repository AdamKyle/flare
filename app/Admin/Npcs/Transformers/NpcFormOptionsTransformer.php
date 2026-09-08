<?php

namespace App\Admin\Npcs\Transformers;

use App\Flare\Models\GameMap;
use App\Game\Maps\Values\Coordinates;
use App\Game\Npcs\Values\NpcType;

class NpcFormOptionsTransformer
{
    /**
     * Transform the supplied internal Npc form option data into its Admin API representation.
     */
    public function transform(array $formOptions): array
    {
        /** @var GameMap $gameMap */
        $gameMap = $formOptions['game_map'];

        /** @var Coordinates $coordinates */
        $coordinates = $formOptions['coordinates'];

        return [
            'game_map' => [
                'id' => $gameMap->id,
                'name' => $gameMap->name,
            ],
            'npc_types' => array_map(
                fn (NpcType $npcType): int => $npcType->value,
                $formOptions['npc_types']
            ),
            'coordinates' => [
                'x' => $coordinates->x,
                'y' => $coordinates->y,
            ],
        ];
    }
}
