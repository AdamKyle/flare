<?php

namespace App\Game\Npcs\Transformers;

use App\Flare\Models\Npc;

class NpcDetailTransformer
{
    /**
     * Transform the given NPC into its Player-safe factual detail representation.
     */
    public function transform(Npc $npc): array
    {
        return [
            'id' => $npc->id,
            'game_map' => [
                'id' => $npc->game_map_id,
                'name' => $npc->gameMap?->name ?? '',
            ],
            'real_name' => $npc->real_name,
            'type' => $npc->type,
            'x_position' => $npc->x_position,
            'y_position' => $npc->y_position,
        ];
    }
}
