<?php

namespace App\Admin\Services;

use App\Admin\Jobs\UpdateBuildings;
use App\Flare\Models\GameBuilding;

class UpdateKingdomsService {

    public function updateKingdomBuildings(GameBuilding $gameBuilding) {
        UpdateBuildings::dispatch($gameBuilding)->delay(now()->addMinutes(1));
    }
}