<?php

namespace App\Admin\GameMaps\Transformers;

use App\Flare\Models\Npc;
use League\Fractal\TransformerAbstract;

class GameMapRelatedNpcTransformer extends TransformerAbstract
{
    /**
     * Transform an NPC into its compact Game Map relationship representation.
     *
     * @param  Npc  $npc  NPC to transform.
     * @return array{id: int, name: string, type: int, x_position: int, y_position: int} Compact NPC relationship representation.
     */
    public function transform(Npc $npc): array
    {
        return [
            'id' => $npc->id,
            'name' => $npc->real_name,
            'type' => $npc->type,
            'x_position' => $npc->x_position,
            'y_position' => $npc->y_position,
        ];
    }
}
