<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Kingdoms\Events\UpdateUnitMovementLogs;
use App\Game\Kingdoms\Jobs\MoveUnits;
use Carbon\Carbon;

class UnitRecallService {

    /**
     * Get the time left in the unit movement.
     *
     * @param UnitMovementQueue $queue
     * @return float|int
     */
    public function getTimeLeft(UnitMovementQueue $queue) {
        $start   = Carbon::parse($queue->started_at)->timestamp;
        $end     = Carbon::parse($queue->completed_at)->timestamp;
        $current = Carbon::parse(now())->timestamp;

        return (($current - $start) / ($end - $start));
    }

    /**
     * Recall the units.
     *
     * @param array $unitMovement
     * @param Character $character
     * @param int $elapsedTime
     */
    public function recall(array $unitMovement, Character $character, int $elapsedTime = 0, bool $inSeconds = false) {
        unset($unitMovement['id']);
        unset($unitMovement['created_at']);
        unset($unitMovement['updated_at']);

        if ($elapsedTime === 0) {
            $unitsMoving  = json_decode($unitMovement['units_moving']);

            foreach ($unitsMoving as $unitInfo) {

                if (is_array($unitInfo)) {
                    foreach ($unitInfo as $unit) {
                        $elapsedTime += $unit->time_to_return;
                    }
                } else {
                    $elapsedTime += $unitInfo->time_to_return;
                }
            }
        }

        $time = $inSeconds ? now()->addSeconds($elapsedTime) : now()->addMinutes($elapsedTime);

        $unitMovement['is_attacking'] = false;
        $unitMovement['is_recalled']  = true;
        $unitsMoving                  = json_decode($unitMovement['units_moving']);
        $unitMovement['units_moving'] = $unitsMoving;
        $unitMovement['completed_at'] = $time;
        $unitMovement['started_at']   = now();

        $recall = UnitMovementQueue::create($unitMovement);

        MoveUnits::dispatch($recall->id, 0, 'recalled', $character, $elapsedTime)->delay($time);

        UpdateUnitMovementLogs::dispatch($character);
    }

}
