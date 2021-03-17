<?php

namespace App\Admin\Services;

use App\Admin\Jobs\UpdateKingdomBuildings;
use App\Flare\Models\GameBuilding;

class UpdateKingdomsService {

    public function updateKingdomKingdomBuildings(GameBuilding $gameBuilding, $selectedUnits = [], int $levels = null) {
        UpdateKingdomBuildings::dispatch($gameBuilding, $selectedUnits, $levels)->delay(now()->addMinutes(1));
    }

    public function assignUnits(GameBuilding $gameBuilding, array $selectedUnits, int $levels) {
        $gameBuilding->units()->create([
            'game_building_id' => $gameBuilding->id,
            'game_unit_id'     => $selectedUnits[0],
            'required_level'   => 1,
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