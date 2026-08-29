<?php

namespace App\Admin\Npcs\Transformers;

use App\Flare\Models\GameMap;
use App\Game\Maps\Values\Coordinates;
use App\Game\Npcs\Values\NpcType;

class NpcFormOptionsTransformer
{
    /**
     * Transform the supplied internal Npc form option data into its Admin API representation.
     *
     * @param  array{game_map: GameMap, npc_types: array<int, NpcType>, coordinates: Coordinates}  $formOptions  Internal NPC form option data.
     * @return array{game_map: array{id: int, name: string}, npc_types: array<int, int>, coordinates: array{x: array<int, int>, y: array<int, int>}} Admin NPC form-options representation.
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
