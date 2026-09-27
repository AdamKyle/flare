<?php

namespace App\Admin\Kingdoms\Transformers;

use App\Flare\Models\GameUnit;
use League\Fractal\TransformerAbstract;

class UnitListTransformer extends TransformerAbstract
{
    /**
     * Transform a Kingdom Unit into its Admin list-row representation.
     *
     * @param GameUnit $gameUnit
     * @return array
     */
    public function transform(GameUnit $gameUnit): array
    {
        return [
            'id' => $gameUnit->id,
            'name' => $gameUnit->name,
            'attack' => $gameUnit->attack,
            'defence' => $gameUnit->defence,
            'time_to_recruit' => $gameUnit->time_to_recruit,
            'is_special' => $gameUnit->is_special,
        ];
    }
}
