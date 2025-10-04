<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Events\KingdomServerMessageEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Models\Notification;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Core\Events\UpdateNotificationsBroadcastEvent;
use App\Game\Kingdoms\Events\UpdateKingdomLogs;
use App\Game\Kingdoms\Events\UpdateUnitMovementLogs;

class UnitReturnService
{
    public function returnUnits(UnitMovementQueue $unitMovement, Character $character)
    {
        $unitsReturning = $unitMovement->units_moving['new_units'];

        $kingdom = Kingdom::find($unitMovement->from_kingdom_id);
        $defender = $unitMovement->from_kingdom;

        foreach ($unitsReturning as $unitInfo) {
            $foundUnits = $kingdom->units()->where('game_unit_id', $unitInfo['unit_id'])->first();

            $foundUnits->update([
                'amount' => $foundUnits->amount + $unitInfo['amount'],
            ]);
        }

        $log = KingdomLog::where('character_id', $character->id)
            ->where('from_kingdom_id', $unitMovement->from_kingdom_id)
            ->where('published', false)
            ->first();

        $log->update([
            'published' => true,
        ]);

        $message = 'Your units have returned from their attack at (X/Y): '.
            $defender->x_position.'/'.$defender->y_position.' on '.$defender->gameMap->name.' plane.';

        Notification::create([
            'character_id' => $character->id,
            'title' => 'Units Returned',
            'message' => $message,
            'status' => 'success',
            'type' => 'kingdom',
            'url' => route('game.kingdom.attack-log', [
                'character' => $character->id,
                'kingdomLog' => $log->id,
            ]),
        ]);

        event(new UpdateNotificationsBroadcastEvent($character->refresh()->notifications()->where('read', false)->get(), $character->user));

        event(new UpdateKingdomLogs($character->refresh()));

        $message = 'Your units have returned from their attack at (X/Y): '.
            $defender->x_position.'/'.$defender->y_position.' on '.$defender->gameMap->name.' plane. Check your attack logs for more information.';

        $unitMovement->delete();

        UpdateUnitMovementLogs::dispatch($character);

        event(new KingdomServerMessageEvent($character->user, 'units-returned', $message));
    }

    public function recallUnits(UnitMovementQueue $unitMovement, Character $character)
    {
        $unitsReturning = $unitMovement->units_moving;

        $kingdom = Kingdom::find($unitMovement->from_kingdom_id);

        foreach ($unitsReturning as $unitInfo) {
            if (count($unitInfo) == count($unitInfo, COUNT_RECURSIVE)) {
                $this->updateUnits($kingdom, $unitInfo);
            } else {
                foreach ($unitInfo as $unit) {
                    $this->updateUnits($kingdom, $unit);
                }
            }
        }

        $defender = $unitMovement->to_kingdom;

        $message = 'Your units have returned home after being recalled from their attack on '.$defender->name.' (X/Y): '.
            $defender->x_position.'/'.$defender->y_position.' on '.$defender->gameMap->name.'.';

        $unitMovement->delete();

        UpdateUnitMovementLogs::dispatch($character);

        event(new KingdomServerMessageEvent($character->user, 'units-recalled', $message));
    }

    protected function updateUnits(Kingdom $kingdom, array $unitInfo)
    {
        $foundUnits = $kingdom->units()->where('game_unit_id', $unitInfo['unit_id'])->first();

        if (! is_null($foundUnits)) {
            $foundUnits->update([
                'amount' => $foundUnits->amount + $unitInfo['amount'],
            ]);
        }
    }
}
