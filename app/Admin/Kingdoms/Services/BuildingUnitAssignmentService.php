<?php

namespace App\Admin\Kingdoms\Services;

use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;

class BuildingUnitAssignmentService
{
    /**
     * Synchronize the Building's recruitable Units to exactly the ordered selection, recalculating each Unit's required Building level.
     *
     * @param GameBuilding $building
     * @param array $orderedUnitIds
     * @param ?int $unitsPerLevel
     * @param ?int $onlyAtLevel
     * @return void
     */
    public function sync(GameBuilding $building, array $orderedUnitIds, ?int $unitsPerLevel, ?int $onlyAtLevel): void
    {
        $unitIds = $building->trains_units ? array_values(array_unique($orderedUnitIds)) : [];

        GameBuildingUnit::where('game_building_id', $building->id)
            ->whereNotIn('game_unit_id', $unitIds)
            ->delete();

        foreach ($unitIds as $position => $unitId) {
            GameBuildingUnit::updateOrCreate([
                'game_building_id' => $building->id,
                'game_unit_id' => $unitId,
            ], [
                'required_level' => $this->requiredLevel($position, $unitsPerLevel, $onlyAtLevel),
            ]);
        }
    }

    /**
     * Resolve the Building level a Unit unlocks at from its position in the selection.
     *
     * A Building restricted to a single level recruits every Unit at that level; otherwise the first Unit
     * unlocks at level one and each following Unit unlocks the configured number of levels later.
     *
     * @param int $position
     * @param ?int $unitsPerLevel
     * @param ?int $onlyAtLevel
     * @return int
     */
    private function requiredLevel(int $position, ?int $unitsPerLevel, ?int $onlyAtLevel): int
    {
        if (! empty($onlyAtLevel)) {
            return $onlyAtLevel;
        }

        return 1 + ($position * ($unitsPerLevel ?? 0));
    }
}
