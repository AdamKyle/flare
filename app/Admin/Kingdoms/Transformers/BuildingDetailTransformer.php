<?php

namespace App\Admin\Kingdoms\Transformers;

use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;
use Illuminate\Support\Collection;

class BuildingDetailTransformer
{
    /**
     * @param BuildingFormTransformer $buildingFormTransformer
     */
    public function __construct(private readonly BuildingFormTransformer $buildingFormTransformer) {}

    /**
     * Transform a Kingdom Building into its Admin detail representation, including its passive requirement and recruitable Units.
     *
     * @param GameBuilding $gameBuilding
     * @param Collection $recruitableUnits
     * @return array
     */
    public function transform(GameBuilding $gameBuilding, Collection $recruitableUnits): array
    {
        return array_merge($this->buildingFormTransformer->transform($gameBuilding, $recruitableUnits), [
            'passive_skill' => is_null($gameBuilding->passive) ? null : [
                'id' => $gameBuilding->passive->id,
                'name' => $gameBuilding->passive->name,
            ],
            'units' => $recruitableUnits->map(fn (GameBuildingUnit $buildingUnit): array => [
                'unit_id' => $buildingUnit->game_unit_id,
                'unit_name' => $buildingUnit->gameUnit?->name,
                'required_level' => $buildingUnit->required_level,
            ])->values()->all(),
        ]);
    }
}
