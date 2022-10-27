<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Jobs\MoveUnits;
use App\Game\Kingdoms\Validators\MoveUnitsValidator;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class KingdomAttackService {

    use ResponseBuilder;

    /**
     * @var UnitMovementService $unitMovementService
     */
    private UnitMovementService $unitMovementService;

    /**
     * @var MoveUnitsValidator $moveUnitsValidator;
     */
    private MoveUnitsValidator $moveUnitsValidator;

    /**
     * @var UpdateKingdom $updateKingdom
     */
    private UpdateKingdom $updateKingdom;

    /**
     * @param UnitMovementService $unitMovementService
     * @param MoveUnitsValidator $moveUnitsValidator
     * @param UpdateKingdom $updateKingdom
     */
    public function __construct(UnitMovementService $unitMovementService,
                                MoveUnitsValidator $moveUnitsValidator,
                                UpdateKingdom $updateKingdom
    ) {
        $this->unitMovementService = $unitMovementService;
        $this->moveUnitsValidator  = $moveUnitsValidator;
        $this->updateKingdom       = $updateKingdom;
    }

    /**
     * Attack the kingdom.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param array $params
     * @return array
     */
    public function attackKingdom(Character $character, Kingdom $kingdom, array $params): array {
        if (!$this->moveUnitsValidator->setUnitsToMove($params['units_to_move'])->isValid($character)) {
            return $this->errorResult('Invalid input.');
        }

        if ($kingdom->character_id === $character->id) {
            return $this->errorResult('Cannot do that');
        }

        if ($kingdom->game_map_id !== $character->map->game_map_id) {
            return $this->errorResult('Cannot attack across plane.');
        }

        if (!is_null($kingdom->protected_until)) {
            return $this->errorResult('Cannot do that');
        }

        $unitsToMove = $this->unitMovementService->buildUnitsToMoveBasedOnKingdom($kingdom, $params['units_to_move']);

        $this->unitMovementService->removeUnitsFromKingdom($params['units_to_move']);

        $this->createAttackQueue($character, $kingdom, $unitsToMove);

        $this->updateKingdom->updateKingdomAllKingdoms($character->refresh());

        if (!is_null($kingdom->character_id)) {
            $defender = $kingdom->character;

            $this->updateKingdom->updateKingdomAllKingdoms($defender->refresh());
        }

        $mapName = $kingdom->gameMap->name;

        event(new GlobalMessageEvent($character->name . ' Has launched an attack against: ' .
            $kingdom->name . ' on the plane: ' . $mapName . ' At (X/Y): ' . $kingdom->x_position . '/' . $kingdom->y_position));

        return $this->successResult(['message' => 'Units have been sent to attack!']);
    }

    /**
     * Create the attack queue for each kingdom attacking.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param array $unitData
     * @return void
     */
    protected function createAttackQueue(Character $character, Kingdom $kingdom, array $unitData): void {
        foreach ($unitData as $kingdomId => $units) {
            $this->attackQueueCreation($character, $kingdom, $units, $kingdomId);
        }
    }

    /**
     * Create attacking queue.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param array $unitData
     * @param int $fromKingdomId
     * @return void
     */
    private function attackQueueCreation(Character $character, Kingdom $kingdom, array $unitData, int $fromKingdomId): void {
        $fromKingdom   = $character->kingdoms()->find($fromKingdomId);

        $time          = $this->unitMovementService->determineTimeRequired($character, $kingdom, $fromKingdomId);

        $minutes       = now()->addMinutes($time);

        $unitMovementQueue = UnitMovementQueue::create([
            'character_id'      => $character->id,
            'from_kingdom_id'   => $fromKingdom->id,
            'to_kingdom_id'     => $kingdom->id,
            'units_moving'      => $unitData,
            'completed_at'      => $minutes,
            'started_at'        => now(),
            'moving_to_x'       => $kingdom->x_position,
            'moving_to_y'       => $kingdom->y_position,
            'from_x'            => $fromKingdom->x_position,
            'from_y'            => $fromKingdom->y_position,
            'is_attacking'      => true,
            'is_recalled'       => false,
            'is_returning'      => false,
            'is_moving'         => false,
        ]);

        MoveUnits::dispatch($unitMovementQueue->id)->delay($minutes);

        if (!is_null($kingdom->character_id)) {
            $defender = $kingdom->character;

            $fromMapName = $fromKingdom->gameMap->name;

            event(new ServerMessageEvent($defender->user,$defender->name . ' Your kingdom is under attack! Kingdom: ' .
                $kingdom->name . ' on the plane: ' . $fromMapName . ' At (X/Y): ' . $kingdom->x_position . '/' . $kingdom->y_position . 'from: ' .
                $fromKingdom->name . ' At (X/Y): ' . $fromKingdom->x_position . '/' . $fromKingdom->y_position
            ));
        }
    }
}
