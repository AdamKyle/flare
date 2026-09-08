<?php

namespace App\Admin\MapGems\Transformers;

use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Game\Gems\Values\GemTypeValue;

class MapGemFormOptionsTransformer
{
    /**
     * Transform the supplied internal Map Gem form option data into its Admin API representation.
     */
    public function transform(array $formOptions): array
    {
        return [
            'game_maps' => $formOptions['game_maps']->map(fn (GameMap $gameMap): array => [
                'id' => $gameMap->id,
                'name' => $gameMap->name,
            ])->values()->all(),
            'crafting_skills' => $formOptions['crafting_skills']->map(fn (GameSkill $gameSkill): array => [
                'id' => $gameSkill->id,
                'name' => $gameSkill->name,
            ])->values()->all(),
            'gem_types' => array_keys(GemTypeValue::getNames()),
        ];
    }
}
