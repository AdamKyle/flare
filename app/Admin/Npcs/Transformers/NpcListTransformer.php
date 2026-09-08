<?php

namespace App\Admin\Npcs\Transformers;

use App\Flare\Models\Npc;
use League\Fractal\TransformerAbstract;

class NpcListTransformer extends TransformerAbstract
{
    /**
     * Transform an NPC into its Admin list-row representation.
     */
    public function transform(Npc $npc): array
    {
        return [
            'id' => $npc->id,
            'real_name' => $npc->real_name,
            'type' => $npc->type,
            'map_name' => $npc->gameMap?->name,
            'x_position' => $npc->x_position,
            'y_position' => $npc->y_position,
        ];
    }
}
