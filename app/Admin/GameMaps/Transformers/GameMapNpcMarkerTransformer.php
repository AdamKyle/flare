<?php

namespace App\Admin\GameMaps\Transformers;

use App\Flare\Models\Npc;

class GameMapNpcMarkerTransformer
{
    /**
     * Transform an Npc into its Game Map editor marker representation.
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
