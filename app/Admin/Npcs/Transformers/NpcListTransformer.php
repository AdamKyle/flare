<?php

namespace App\Admin\Npcs\Transformers;

use App\Flare\Models\Npc;
use League\Fractal\TransformerAbstract;

class NpcListTransformer extends TransformerAbstract
{
    /**
     * Transform an NPC into its Admin list-row representation.
     *
     * @param  Npc  $npc  NPC to transform.
     * @return array{id: int, real_name: string, type: int, map_name: string|null, x_position: int, y_position: int} Admin NPC list-row representation.
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
