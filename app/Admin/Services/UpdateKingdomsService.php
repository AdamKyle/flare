<?php

namespace App\Admin\Services;

use App\Admin\Jobs\AssignNewKingdomBuildingsJob;
use App\Admin\Jobs\UpdateKingdomBuildings;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\Kingdom;

class UpdateKingdomsService {

    /**
     * Updates or creates the kingdom building for players.
     *
     * Called from the livewire admin view to handle giving new buildings to players.
     *
     * @param GameBuilding $gameBuilding
     * @param array $selectedUnits
     * @param int|null $levels
     */
    public function updateKingdomKingdomBuildings(GameBuilding $gameBuilding, $selectedUnits = [], int $levels = null) {
        $gameBuilding = $this->reassignUnits($gameBuilding, $selectedUnits, $levels);

        UpdateKingdomBuildings::dispatch($gameBuilding, $selectedUnits, $levels)->delay(now()->addMinutes(1));
    }

    /**
     * Assigns the new building to players.
     *
     * Done on import.
     *
     * @param GameBuilding $gameBuilding
     */
    public function assignNewBuildingsToCharacters(GameBuilding $gameBuilding) {
        AssignNewKingdomBuildingsJob::dispatch($gameBuilding);
    }

    /**
     * Reassigns the game units to a game building.
     *
     * @param GameBuilding $gameBuilding
     * @param array $selectedUnits
     * @param int|null $levels
     * @return GameBuilding
     */
    protected function reassignUnits(GameBuilding $gameBuilding, array $selectedUnits = [], int $levels = null): GameBuilding {
        if (empty($selectedUnits)) {
            return $gameBuilding;
        }

        if ($gameBuilding->units->isNotEmpty()) {
            foreach($gameBuilding->units as $unit) {
                $unit->delete();
            }
        }

        $this->assignUnits($gameBuilding->refresh(), $selectedUnits, $levels);

        return $gameBuilding->refresh();
    }

    /**
     * Assigns the units to the building.
     *
     * @param GameBuilding $gameBuilding
     * @param array $selectedUnits
     * @param int $levels
     * @return void
     */
    private function assignUnits(GameBuilding $gameBuilding, array $selectedUnits, int $levels): void {
        $gameBuilding->units()->create([
            'game_building_id' => $gameBuilding->id,
            'game_unit_id'     => $selectedUnits[0],
            'required_level'   => !is_null($gameBuilding->only_at_level) ? $gameBuilding->only_at_level : 1,
        ]);

        unset($selectedUnits[0]);

        $initialLevel = 1;

        if (empty($selectedUnits)) {
            return;
        }

        foreach($selectedUnits as $unitId) {
            $initialLevel += $levels;

            $gameBuilding->units()->create([
                'game_building_id' => $gameBuilding->id,
                'game_unit_id'     => $unitId,
                'required_level'   => $initialLevel,
            ]);
        }
    }
}
