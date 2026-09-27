<?php

namespace App\Admin\Services;

use App\Admin\Jobs\AssignNewKingdomBuildingsJob;
use App\Admin\Jobs\UpdateKingdomBuildings;
use App\Flare\Models\GameBuilding;

class UpdateKingdomsService
{
    /**
     * Give the Building definition to every player Kingdom that does not already have it.
     *
     * @param GameBuilding $gameBuilding
     * @return void
     */
    public function assignNewBuildingsToCharacters(GameBuilding $gameBuilding): void
    {
        AssignNewKingdomBuildingsJob::dispatch($gameBuilding);
    }

    /**
     * Refresh every player Kingdom Building built from the changed Building definition.
     *
     * @param GameBuilding $gameBuilding
     * @return void
     */
    public function updateKingdomBuildings(GameBuilding $gameBuilding): void
    {
        UpdateKingdomBuildings::dispatch($gameBuilding)->delay(now()->addMinutes(1));
    }
}
