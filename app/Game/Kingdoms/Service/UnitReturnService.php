<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;

class UnitReturnService {

    public function returnUnits(UnitMovementQueue $unitMovement) {
        $unitsReturning = $unitMovement->units_moving['new_units'];

        $kingdom = Kingdom::find($unitMovement->kingdom_id);
        dump($unitsReturning);
        foreach ($unitsReturning as $unitInfo) {
            dump($unitInfo);
            $foundUnits = $kingdom->units()->where('game_unit_id', $unitInfo['unit_id'])->first();

            $foundUnits->update([
                'amount' => $foundUnits->amount + $unitInfo['amount'],
            ]);
        }
    }
}
