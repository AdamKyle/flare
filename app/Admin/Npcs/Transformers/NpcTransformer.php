<?php

namespace App\Admin\Npcs\Transformers;

use App\Flare\Models\Npc;

class NpcTransformer
{
    /**
     * Transform an Npc into its Admin API representation.
     *
     * @param  Npc  $npc  NPC to transform.
     * @return array{id: int, game_map_id: int, name: string, real_name: string, type: int, x_position: int, y_position: int} Admin NPC representation.
     */
    public function transform(Npc $npc): array
    {
        return [
            'id' => $npc->id,
            'game_map_id' => $npc->game_map_id,
            'name' => $npc->name,
            'real_name' => $npc->real_name,
            'type' => $npc->type,
            'x_position' => $npc->x_position,
            'y_position' => $npc->y_position,
        ];
    }
}
