<?php

namespace App\Admin\Npcs\Transformers;

use App\Flare\Models\Npc;

class NpcTransformer
{
    /**
     * Transform an Npc into its Admin API representation.
     */
    public function transform(Npc $npc): array
    {
        return [
            'id' => $npc->id,
            'game_map_id' => $npc->game_map_id,
            'name' => $npc->name,
            'real_name' => $npc->real_name,
            'type' => $npc->type,
            'type_name' => $npc->type()->label(),
            'x_position' => $npc->x_position,
            'y_position' => $npc->y_position,
        ];
    }
}
