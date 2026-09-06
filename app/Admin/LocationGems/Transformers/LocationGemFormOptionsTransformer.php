<?php

namespace App\Admin\LocationGems\Transformers;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Location;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Database\Eloquent\Collection;

class LocationGemFormOptionsTransformer
{
    /**
     * Transform the supplied internal Location Gem form option data into its Admin API representation.
     *
     * @param  array{locations: Collection<int, Location>, crafting_skills: Collection<int, GameSkill>}  $formOptions  Internal Location Gem form option data.
     * @return array{locations: array<int,array{id:int,name:string,type:int|null,map:array{id:int,name:string}|null}>, crafting_skills: array<int,array{id:int,name:string}>, gem_types: array<int,int>} Admin Location Gem form-options representation.
     */
    public function transform(array $formOptions): array
    {
        return [
            'locations' => $formOptions['locations']->map(fn (Location $location): array => [
                'id' => $location->id,
                'name' => $location->name,
                'type' => $location->type,
                'map' => is_null($location->map) ? null : [
                    'id' => $location->map->id,
                    'name' => $location->map->name,
                ],
            ])->values()->all(),
            'crafting_skills' => $formOptions['crafting_skills']->map(fn (GameSkill $gameSkill): array => [
                'id' => $gameSkill->id,
                'name' => $gameSkill->name,
            ])->values()->all(),
            'gem_types' => array_keys(GemTypeValue::getNames()),
        ];
    }
}
