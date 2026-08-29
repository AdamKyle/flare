<?php

namespace App\Admin\GameMaps\Transformers;

use App\Flare\Models\Npc;

class GameMapNpcMarkerTransformer
{
    /**
     * Transform an Npc into its Game Map editor marker representation.
     *
     * @param  Npc  $npc  NPC to transform.
     * @return array{id: int, real_name: string, type: int, x_position: int, y_position: int} Game Map editor NPC marker representation.
     */
    public function transform(Npc $npc): array
    {
        return [
            'id' => $npc->id,
            'real_name' => $npc->real_name,
            'type' => $npc->type,
            'x_position' => $npc->x_position,
            'y_position' => $npc->y_position,
        ];
    }
}
