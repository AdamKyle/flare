<?php

namespace App\Admin\Services;

use App\Admin\Jobs\UpdateKingdomBuildings;
use App\Flare\Models\GameKingdomBuilding;

class UpdateKingdomsService {

    public function updateKingdomKingdomBuildings(GameKingdomBuilding $gameKingdomBuilding, $selectedUnits = [], int $levels = null) {
        UpdateKingdomBuildings::dispatch($gameKingdomBuilding, $selectedUnits, $levels)->delay(now()->addMinutes(1));
    }

    public function assignUnits(GameKingdomBuilding $gameKingdomBuilding, array $selectedUnits, int $levels) {
        $gameKingdomBuilding->units()->create([
            'game_building_id' => $gameKingdomBuilding->id,
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

            $gameKingdomBuilding->units()->create([
                'game_building_id' => $gameKingdomBuilding->id,
                'game_unit_id'     => $unitId,
                'required_level'   => $initialLevel,
            ]);
        }
    }
}