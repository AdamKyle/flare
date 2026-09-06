<?php

namespace App\Admin\MapGems\Transformers;

use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Database\Eloquent\Collection;

class MapGemFormOptionsTransformer
{
    /**
     * Transform the supplied internal Map Gem form option data into its Admin API representation.
     *
     * @param  array{game_maps: Collection<int, GameMap>, crafting_skills: Collection<int, GameSkill>}  $formOptions  Internal Map Gem form option data.
     * @return array{game_maps: array<int,array{id:int,name:string}>, crafting_skills: array<int,array{id:int,name:string}>, gem_types: array<int,int>} Admin Map Gem form-options representation.
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
