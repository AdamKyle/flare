<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Kingdoms\Events\UpdateKingdomQueues;
use App\Game\Kingdoms\Jobs\MoveUnits;
use App\Game\Kingdoms\Service\UnitMovementService;

class ReturnSurvivingUnitHandler
{
    private UnitMovementService $unitMovementService;

    private array $newAttackingUnits;

    public function __construct(UnitMovementService $unitMovementService)
    {
        $this->unitMovementService = $unitMovementService;
    }

    /**
     * Set the remaining attacking units.
     */
    public function setNewAttackingUnits(array $newAttackingUnits): ReturnSurvivingUnitHandler
    {
        $this->newAttackingUnits = $newAttackingUnits;

        return $this;
    }

    /**
     * Return all surviving units.
     */
    public function returnSurvivingUnits(Kingdom $attackingKingdom, Kingdom $defendingKingdom): void
    {

        if (! $this->isThereAnySurvivingUnits()) {
            return;
        }

        $character = $attackingKingdom->character;

        $time = $this->unitMovementService->getDistanceTime($character, $attackingKingdom, $defendingKingdom);

        $minutes = now()->addMinutes($time);

        $unitMovementQueue = UnitMovementQueue::create([
            'character_id' => $character->id,
            'from_kingdom_id' => $defendingKingdom->id,
            'to_kingdom_id' => $attackingKingdom->id,
            'units_moving' => $this->newAttackingUnits,
            'completed_at' => $minutes,
            'started_at' => now(),
            'moving_to_x' => $attackingKingdom->x_position,
            'moving_to_y' => $attackingKingdom->y_position,
            'from_x' => $defendingKingdom->x_position,
            'from_y' => $defendingKingdom->y_position,
            'is_attacking' => false,
            'is_recalled' => false,
            'is_returning' => true,
            'is_moving' => false,
        ]);

        event(new UpdateKingdomQueues($defendingKingdom));
        event(new UpdateKingdomQueues($attackingKingdom));

        MoveUnits::dispatch($unitMovementQueue->id)->delay($minutes);
    }

    /**
     * Do we have any surviving units?
     */
    protected function isThereAnySurvivingUnits(): bool
    {
        $attackingUnitAmount = 0;

        foreach ($this->newAttackingUnits as $attackingUnit) {
            if ($attackingUnit['amount'] > 0) {
                $attackingUnitAmount = $attackingUnit['amount'];
            }
        }

        return $attackingUnitAmount > 0;
    }
}
