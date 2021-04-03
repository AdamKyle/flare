<?php

namespace App\Admin\Services;

use App\Admin\Jobs\UpdateKingdomBuildings;
use App\Flare\Models\GameBuilding;

class UpdateKingdomsService {

    public function updateKingdomKingdomBuildings(GameBuilding $gameBuilding, $selectedUnits = [], int $levels = null) {
        UpdateKingdomBuildings::dispatch($gameBuilding, $selectedUnits, $levels)->delay(now()->addMinutes(1));
    }
}
