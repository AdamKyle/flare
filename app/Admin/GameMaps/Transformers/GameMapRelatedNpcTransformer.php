<?php

namespace App\Admin\GameMaps\Transformers;

use App\Flare\Models\Npc;
use League\Fractal\TransformerAbstract;

class GameMapRelatedNpcTransformer extends TransformerAbstract
{
    /**
     * Transform an NPC into its compact Game Map relationship representation.
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
