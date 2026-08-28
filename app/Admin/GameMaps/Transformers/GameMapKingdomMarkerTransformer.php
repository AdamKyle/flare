<?php

namespace App\Admin\GameMaps\Transformers;

use App\Flare\Models\Kingdom;

class GameMapKingdomMarkerTransformer
{
    /**
     * Transform a Kingdom into its Game Map editor marker representation.
     */
    public function transform(Kingdom $kingdom): array
    {
        return [
            'id' => $kingdom->id,
            'name' => $kingdom->name,
            'npc_owned' => $kingdom->npc_owned,
            'owner_type' => $kingdom->npc_owned ? 'npc' : 'player',
            'x_position' => $kingdom->x_position,
            'y_position' => $kingdom->y_position,
        ];
    }
}
