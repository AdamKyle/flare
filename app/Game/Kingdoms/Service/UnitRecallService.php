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
     */
    public function recall(array $unitMovement, Character $character) {
        unset($unitMovement['id']);
        unset($unitMovement['created_at']);
        unset($unitMovement['updated_at']);

        $unitMovement['is_attacking'] = false;
        $unitMovement['is_recalled']  = true;

        $timeToRecall = 0;
        $unitsMoving  = json_decode($unitMovement['units_moving']);

        foreach ($unitsMoving as $unitInfo) {
            $timeToRecall += $unitInfo->time_to_return;
        }

        $unitMovement['units_moving'] = $unitsMoving;
        $unitMovement['completed_at'] = now()->addMinutes($timeToRecall);
        $unitMovement['started_at']   = now();

        $recall = UnitMovementQueue::create($unitMovement);

        $timeForDelay = $timeToRecall;

        if ($timeForDelay > 15) {
            $timeForDispatch = $timeToRecall / 10;
        }

        MoveUnits::dispatch($recall->id, 0, 'recalled', $character, $timeForDispatch)->delay(now()->addMinutes($timeForDispatch));

        UpdateUnitMovementLogs::dispatch($character);
    }

}
