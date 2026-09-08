<?php

namespace App\Admin\GameMaps\Transformers;

use App\Flare\Models\Monster;
use League\Fractal\TransformerAbstract;

class GameMapRelatedMonsterTransformer extends TransformerAbstract
{
    /**
     * Transform a Monster into its compact Game Map relationship representation. No combat scaling is applied.
     */
    public function transform(Monster $monster): array
    {
        return [
            'id' => $monster->id,
            'name' => $monster->name,
            'is_celestial_entity' => $monster->is_celestial_entity,
            'is_raid_monster' => $monster->is_raid_monster,
            'is_raid_boss' => $monster->is_raid_boss,
            'only_for_location_type' => $monster->only_for_location_type,
        ];
    }
}
