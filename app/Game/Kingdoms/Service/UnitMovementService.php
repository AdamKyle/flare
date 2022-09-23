<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\KingdomUnit;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Jobs\MoveUnits;
use App\Game\Kingdoms\Validators\MoveUnitsValidator;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Values\SkillTypeValue;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Maps\Calculations\DistanceCalculation;

class UnitMovementService {

    use ResponseBuilder;

    /**
     * @var DistanceCalculation $distanceCalculation
     */
    private DistanceCalculation $distanceCalculation;

    /**
     * @var MoveUnitsValidator $moveUnitsValidator
     */
    private MoveUnitsValidator $moveUnitsValidator;

    /**
     * @var UpdateKingdom $updateKingdom
     */
    private UpdateKingdom $updateKingdom;

    /**
     * @param DistanceCalculation $distanceCalculation
     * @param MoveUnitsValidator $moveUnitsValidator
     * @param UpdateKingdom $updateKingdom
     */
    public function __construct(DistanceCalculation $distanceCalculation,
                                MoveUnitsValidator $moveUnitsValidator,
                                UpdateKingdom $updateKingdom
    ) {
        $this->distanceCalculation = $distanceCalculation;
        $this->moveUnitsValidator  = $moveUnitsValidator;
        $this->updateKingdom       = $updateKingdom;
    }

    /**
     * Get kingdom movement information
     *
     * Only gets kingdoms who have at least 1 unit.
     *
     * - Returns units for the kingdom.
     * - return s time from the kingdom to your kingdom.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @return array
     */
    public function getKingdomUnitTravelData(Character $character, Kingdom $kingdom): array {
        $kingdomData = [];

        $gameMapId = $character->map->game_map_id;

        $playerKingdoms = Kingdom::where('game_map_id', $gameMapId)
                                 ->where('character_id', $character->id)
                                 ->where('id', '!=', $kingdom->id)
                                 ->get();

        if ($playerKingdoms->isEmpty()) {
            return $kingdomData;
        }

        foreach ($playerKingdoms as $playerKingdom) {
            if ($playerKingdom->units->count() === 0) {
                continue;
            }

            $pixelDistance = $this->distanceCalculation->calculatePixel($kingdom->x_position, $kingdom->y_position,
                $playerKingdom->x_position, $playerKingdom->y_position);

            $timeToKingdom = $this->distanceCalculation->calculateMinutes($pixelDistance);

            $units = $playerKingdom->units->transform(function($unit) {
                $unit->name = $unit->gameUnit->name;

                return $unit;
            });

            $unitData = $this->getUnitData($units, $playerKingdom);

            if (empty($unitData)) {
                continue;
            }

            $kingdomData[] = [
                'kingdom_name' => $playerKingdom->name,
                'kingdom_id'   => $playerKingdom->id,
                'units'        => $unitData,
                'time'         => $timeToKingdom < 1 ? 1 : $timeToKingdom,
            ];
        }


        return $kingdomData;
    }

    /**
     * @param Character $character
     * @param Kingdom $kingdom
     * @param array $params
     * @return array
     */
    public function moveUnitsToKingdom(Character $character, Kingdom $kingdom, array $params): array {

        if (!$this->moveUnitsValidator->setUnitsToMove($params['units_to_move'])->isValid($character)) {
            return $this->errorResult(['Invalid input.']);
        }

        $unitsToMove = $this->buildUnitsToMoveBasedOnKingdom($kingdom, $params['units_to_move']);

        $this->removeUnitsFromKingdom($params['units_to_move']);

        $this->createMovementQueues($character, $kingdom, $unitsToMove);

        $this->updateKingdom->updateKingdomAllKingdoms($character->refresh());

        event(new ServerMessageEvent($character->user, 'You have requested units to be sent to: ' . $kingdom->name . ' they are aon their way!'));

        return $this->successResult(['message' => 'Units are on their way!']);
    }

    /**
     * Recall the units back.
     *
     * @param UnitMovementQueue $unitMovementQueue
     * @param Character $character
     * @return array
     */
    public function recallUnits(UnitMovementQueue $unitMovementQueue, Character $character): array {
        $timeLeft    = $this->getTimeLeft($unitMovementQueue);
        $elapsedTime = $unitMovementQueue->completed_at->diffInSeconds(now()) * $timeLeft;

        $toKingdom   = $unitMovementQueue->from_kingdom_id;
        $fromKingdom = $unitMovementQueue->to_kingdom_id;

        $timeLeft    = now()->addSeconds($elapsedTime);

        $queue = UnitMovementQueue::create([
            'character_id'     => $character->id,
            'from_kingdom_id'  => $fromKingdom,
            'to_kingdom_id'    => $toKingdom,
            'units_moving'     => $unitMovementQueue->units_moving,
            'completed_at'     => $timeLeft,
            'started_at'       => now(),
            'moving_to_x'      => $unitMovementQueue->from_x,
            'moving_to_y'      => $unitMovementQueue->from_y,
            'from_x'           => $unitMovementQueue->moving_to_x,
            'from_y'           => $unitMovementQueue->moving_to_y,
            'is_attacking'     => false,
            'is_recalled'      => true,
            'is_returning'     => false,
            'is_moving'        => false,
        ]);

        MoveUnits::dispatch($queue->id)->delay($timeLeft);

        $unitMovementQueue->delete();

        $kingdom = Kingdom::find($toKingdom);

        event(new ServerMessageEvent($character->user, 'You have recalled your units to: ' . $kingdom->name));

        $this->updateKingdom->updateKingdomAllKingdoms($character->refresh());

        return $this->successResult();
    }

    /**
     * Create one or more queues of units moving.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param array $unitData
     * @return void
     */
    protected function createMovementQueues(Character $character, Kingdom $kingdom, array $unitData): void {
        foreach ($unitData as $kingdomId => $units) {
            $this->moveUnits($character, $kingdom, $units, $kingdomId);
        }
    }

    /**
     * Removes the units we want to move from the kingdom they come from.
     *
     * @param array $unitData
     * @return void
     */
    public function removeUnitsFromKingdom(array $unitData): void {
        foreach ($unitData as $unitData) {
            $kingdom = Kingdom::find($unitData['kingdom_id']);

            $unit    = $kingdom->units()->find($unitData['unit_id']);

            $unit->update([
                'amount' => $unit->amount - $unitData['amount']
            ]);
        }
    }

    /**
     * Builds a more concrete array of kingdoms and their units to move.
     *
     * @param Kingdom $kingdom
     * @param array $unitData
     * @return array
     */
    public function buildUnitsToMoveBasedOnKingdom(Kingdom $kingdom, array $unitData): array {
        $kingdomUnitsToMove = [];

        foreach ($unitData as $unitData) {
            if (!isset($kingdomUnitsToMove[$unitData['kingdom_id']])) {
                $kingdomUnitsToMove[$unitData['kingdom_id']][] = [
                    'unit_id' => $unitData['unit_id'],
                    'amount'  => $unitData['amount']
                ];
            } else {
                $kingdomUnitsToMove[$unitData['kingdom_id']][] = [
                    'unit_id' => $unitData['unit_id'],
                    'amount'  => $unitData['amount']
                ];
            }
        }

        return $kingdomUnitsToMove;
    }

    /**
     * Move the units.
     *
     * - Calculates time based on pixel distance.
     * - Dispatches job for unit movement.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param array $unitData
     * @param int $fromKingdomId
     * @return void
     */
    protected function moveUnits(Character $character, Kingdom $kingdom, array $unitData, int $fromKingdomId): void {

        $fromKingdom   = $character->kingdoms()->find($fromKingdomId);

        $time          = $this->determineTimeRequired($character, $kingdom, $fromKingdomId);

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
            'is_attacking'      => false,
            'is_recalled'       => false,
            'is_returning'      => false,
            'is_moving'         => true,
        ]);

        MoveUnits::dispatch($unitMovementQueue->id)->delay($minutes);
    }

    /**
     * Determine time required to move units.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param int $fromKingdomId
     * @return int
     */
    public function determineTimeRequired(Character $character, Kingdom $kingdom, int $fromKingdomId): int {
        $fromKingdom = $character->kingdoms()->find($fromKingdomId);

        return $this->getDistanceTime($character, $kingdom, $fromKingdom);
    }

    /**
     * Get the distance time when the fromKingdom is known.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param Kingdom $fromKingdom
     * @return int
     */
    public function getDistanceTime(Character $character, Kingdom $kingdom, Kingdom $fromKingdom): int {
        $pixelDistance = $this->distanceCalculation->calculatePixel(
            $fromKingdom->x_position,
            $fromKingdom->y_position,
            $kingdom->x_position,
            $kingdom->y_position
        );

        $timeToKingdom = $this->distanceCalculation->calculateMinutes($pixelDistance);

        $skill = $character->skills()->where('skill_type', SkillTypeValue::EFFECTS_KINGDOM)->first();

        $timeToKingdom -= ($timeToKingdom * $skill->unit_movement_time_reduction);

        if ($timeToKingdom < 1) {
            $timeToKingdom = 1;
        }

        return $timeToKingdom;
    }

    /**
     * Get unit data.
     *
     * @param Collection $units
     * @param Kingdom $playerKingdom
     * @return array
     */
    protected function getUnitData(Collection $units, Kingdom $playerKingdom): array {
        $unitData = [];

        foreach ($units as $unit) {
            if ($unit->amount === 0) {
                continue;
            }

            $unitData[] = [
                'kingdom_id' => $playerKingdom->id,
                'id'         => $unit->id,
                'name'       => $unit->name,
                'amount'     => $unit->amount,
            ];
        }

        return $unitData;
    }

    /**
     * Fetch the amount we can send based on the amount already in the kingdom.
     *
     * @param Kingdom $kingdom
     * @param int $fromKingdomId
     * @param int $unitId
     * @param int $amount
     * @return int
     */
    protected function fetchAmountToMove(Kingdom $kingdom, int $fromKingdomId, int $unitId, int $amount): int {
        $foundUnit = $this->getKingdomUnit($kingdom, $fromKingdomId, $unitId);

        if (!is_null($foundUnit)) {
            $amount = $amount + $foundUnit->amount;

            if ($amount > KingdomMaxValue::MAX_UNIT) {
                $amount = $amount - KingdomMaxValue::MAX_UNIT;
            }
        }

        return $amount;
    }

    /**
     * Get the time left in the movement.
     *
     * @param UnitMovementQueue $queue
     * @return float
     */
    protected function getTimeLeft(UnitMovementQueue $queue): float {
        $start   = Carbon::parse($queue->started_at)->timestamp;
        $end     = Carbon::parse($queue->completed_at)->timestamp;
        $current = Carbon::parse(now())->timestamp;

        return (($current - $start) / ($end - $start));
    }

    /**
     * Get the unit information if the kingdom requesting has the units already.
     *
     * @param Kingdom $kingdom
     * @param int $fromKingdomId
     * @param int $unitId
     * @return KingdomUnit|null
     */
    private function getKingdomUnit(Kingdom $kingdom, int $fromKingdomId, int $unitId): ?KingdomUnit {
        $unit     = Kingdom::find($fromKingdomId)->units()->find($unitId);
        $unitName = $unit->gameUnit->name;

        $unit = $kingdom->units->filter(function($unit) use($unitName) {
            return $unit->gameUnit->name === $unitName;
        })->first();

        return $unit;
    }
}
