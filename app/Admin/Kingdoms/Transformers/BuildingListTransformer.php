<?php

namespace App\Admin\Kingdoms\Transformers;

use App\Flare\Models\GameBuilding;
use League\Fractal\TransformerAbstract;

class BuildingListTransformer extends TransformerAbstract
{
    /**
     * Transform a Kingdom Building into its Admin list-row representation.
     *
     * @param GameBuilding $gameBuilding
     * @return array
     */
    public function transform(GameBuilding $gameBuilding): array
    {
        return [
            'id' => $gameBuilding->id,
            'name' => $gameBuilding->name,
            'max_level' => $gameBuilding->max_level,
            'trains_units' => $gameBuilding->trains_units,
            'is_resource_building' => $gameBuilding->is_resource_building,
            'is_locked' => $gameBuilding->is_locked,
            'is_special' => $gameBuilding->is_special,
        ];
    }
}
