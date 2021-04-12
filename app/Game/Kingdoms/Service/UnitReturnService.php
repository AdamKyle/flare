<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Events\KingdomServerMessageEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Models\UnitMovementQueue;

class UnitReturnService {

    public function returnUnits(UnitMovementQueue $unitMovement, Character $character) {
        $unitsReturning = $unitMovement->units_moving['new_units'];

        $kingdom = Kingdom::find($unitMovement->from_kingdom_id);

        foreach ($unitsReturning as $unitInfo) {
            $foundUnits = $kingdom->units()->where('game_unit_id', $unitInfo['unit_id'])->first();

            $foundUnits->update([
                'amount' => $foundUnits->amount + $unitInfo['amount'],
            ]);
        }

        $log = KingdomLog::where('character_id', $character->id)
                         ->where('to_kingdom_id', $unitMovement->from_kingdom_id)
                         ->where('published', false);

        $log->update([
            'published' => true,
        ]);

        $defender = $unitMovement->from_kingdom;
        $message  = 'Your units have returned from their attack at (X/Y): ' .
            $defender->x_position . '/' . $defender->y_position . ' on ' . $defender->gameMap->name . ' plane. Check your attack logs for more information.';

        event(new KingdomServerMessageEvent($character->user, 'units-returned', $message));
    }
}
