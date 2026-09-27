<?php

namespace App\Admin\Kingdoms\Transformers;

use App\Flare\Models\GameUnit;
use App\Flare\Models\PassiveSkill;

class BuildingFormOptionsTransformer
{
    /**
     * Transform the internal Kingdom Building form option data into its Admin API representation.
     *
     * @param array $formOptions
     * @return array
     */
    public function transform(array $formOptions): array
    {
        return [
            'passive_skills' => $formOptions['passive_skills']->map(fn (PassiveSkill $passiveSkill): array => [
                'id' => $passiveSkill->id,
                'name' => $passiveSkill->name,
            ])->values()->all(),
            'units' => $formOptions['units']->map(fn (GameUnit $gameUnit): array => [
                'id' => $gameUnit->id,
                'name' => $gameUnit->name,
            ])->values()->all(),
        ];
    }
}
