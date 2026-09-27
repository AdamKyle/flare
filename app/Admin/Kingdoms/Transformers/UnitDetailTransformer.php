<?php

namespace App\Admin\Kingdoms\Transformers;

use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use Illuminate\Support\Collection;

class UnitDetailTransformer
{
    /**
     * @param UnitFormTransformer $unitFormTransformer
     */
    public function __construct(private readonly UnitFormTransformer $unitFormTransformer) {}

    /**
     * Transform a Kingdom Unit into its Admin detail representation, including every Building that recruits it.
     *
     * @param GameUnit $gameUnit
     * @param Collection $recruitingBuildings
     * @return array
     */
    public function transform(GameUnit $gameUnit, Collection $recruitingBuildings): array
    {
        return array_merge($this->unitFormTransformer->transform($gameUnit), [
            'recruited_from' => $recruitingBuildings->map(fn (GameBuildingUnit $buildingUnit): array => [
                'building_id' => $buildingUnit->game_building_id,
                'building_name' => $buildingUnit->gameBuilding?->name,
                'required_level' => $buildingUnit->required_level,
            ])->values()->all(),
        ]);
    }
}
