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
}
