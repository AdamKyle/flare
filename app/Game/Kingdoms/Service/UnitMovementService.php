<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomUnit;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateKingdomQueues;
use App\Game\Kingdoms\Jobs\MoveUnits;
use App\Game\Kingdoms\Validators\MoveUnitsValidator;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use App\Game\Skills\Values\SkillTypeValue;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class UnitMovementService
{
    use ResponseBuilder;

    private DistanceCalculation $distanceCalculation;

    private MoveUnitsValidator $moveUnitsValidator;

    private UpdateKingdom $updateKingdom;

    public function __construct(DistanceCalculation $distanceCalculation,
        MoveUnitsValidator $moveUnitsValidator,
        UpdateKingdom $updateKingdom
    ) {
        $this->distanceCalculation = $distanceCalculation;
        $this->moveUnitsValidator = $moveUnitsValidator;
        $this->updateKingdom = $updateKingdom;
    }

    /**
     * Get kingdom movement information
     *
     * Only gets kingdoms who have at least 1 unit.
     *
     * - Returns units for the kingdom.
     * - return s time from the kingdom to your kingdom.
     */
    public function getKingdomUnitTravelData(Character $character, Kingdom $kingdom): array
    {
        $kingdomData = [];

        $playerKingdoms = Kingdom::where('game_map_id', $kingdom->game_map_id)
            ->where('character_id', $character->id)
            ->where('id', '!=', $kingdom->id)
            ->whereNull('protected_until')
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

            $units = $playerKingdom->units->transform(function ($unit) {
                $unit->name = $unit->gameUnit->name;

                return $unit;
            });

            $unitData = $this->getUnitData($units, $playerKingdom);

            if (empty($unitData)) {
                continue;
            }

            $kingdomData[] = [
                'kingdom_name' => $playerKingdom->name,
                'kingdom_id' => $playerKingdom->id,
                'units' => $unitData,
                'time' => $timeToKingdom < 1 ? 1 : $timeToKingdom,
            ];
        }

        return $kingdomData;
    }

    public function moveUnitsToKingdom(Character $character, Kingdom $kingdom, array $params): array
    {

        if (! $this->moveUnitsValidator->setUnitsToMove($params['units_to_move'])->isValid($character, $kingdom)) {
            return $this->errorResult('Invalid input.');
        }

        $unitsToMove = $this->buildUnitsToMoveBasedOnKingdom($kingdom, $params['units_to_move']);

        $this->removeUnitsFromKingdom($params['units_to_move']);

        $this->createMovementQueues($character, $kingdom, $unitsToMove);

        $this->updateKingdom->updateKingdomAllKingdoms($character->refresh());

        event(new ServerMessageEvent($character->user, 'You have requested units to be sent to: '.$kingdom->name.' they are aon their way!'));

        return $this->successResult(['message' => 'Units are on their way!']);
    }

    /**
     * Recall the units back.
     */
    public function recallUnits(UnitMovementQueue $unitMovementQueue, Character $character): array
    {
        $timeLeft = $this->getTimeLeft($unitMovementQueue);
        $elapsedTime = $unitMovementQueue->completed_at->diffInSeconds(now()) * $timeLeft;

        $toKingdom = $unitMovementQueue->from_kingdom_id;
        $fromKingdom = $unitMovementQueue->to_kingdom_id;

        $timeLeft = now()->addSeconds($elapsedTime);

        $queue = UnitMovementQueue::create([
            'character_id' => $character->id,
            'from_kingdom_id' => $fromKingdom,
            'to_kingdom_id' => $toKingdom,
            'units_moving' => $unitMovementQueue->units_moving,
            'completed_at' => $timeLeft,
            'started_at' => now(),
            'moving_to_x' => $unitMovementQueue->from_x,
            'moving_to_y' => $unitMovementQueue->from_y,
            'from_x' => $unitMovementQueue->moving_to_x,
            'from_y' => $unitMovementQueue->moving_to_y,
            'is_attacking' => false,
            'is_recalled' => true,
            'is_returning' => false,
            'is_moving' => false,
        ]);

        event(new UpdateKingdomQueues(Kingdom::find($toKingdom)));
        event(new UpdateKingdomQueues(Kingdom::find($fromKingdom)));

        MoveUnits::dispatch($queue->id)->delay($timeLeft);

        $unitMovementQueue->delete();

        $kingdom = Kingdom::find($toKingdom);

        $this->updateKingdom->updateKingdomAllKingdoms($character->refresh());

        event(new UpdateKingdomQueues($kingdom));

        return $this->successResult([
            'message' => 'Units have been recalled to: '.$kingdom->name,
        ]);
    }

    /**
     * Create one or more queues of units moving.
     */
    protected function createMovementQueues(Character $character, Kingdom $kingdom, array $unitData): void
    {
        foreach ($unitData as $kingdomId => $units) {
            $this->moveUnits($character, $kingdom, $units, $kingdomId);
        }
    }

    /**
     * Removes the units we want to move from the kingdom they come from.
     */
    public function removeUnitsFromKingdom(array $unitData): void
    {
        foreach ($unitData as $unitData) {
            $kingdom = Kingdom::find($unitData['kingdom_id']);

            $unit = $kingdom->units()->find($unitData['unit_id']);

            $unit->update([
                'amount' => $unit->amount - $unitData['amount'],
            ]);
        }
    }

    /**
     * Builds a more concrete array of kingdoms and their units to move.
     */
    public function buildUnitsToMoveBasedOnKingdom(Kingdom $kingdom, array $unitData): array
    {
        $kingdomUnitsToMove = [];

        foreach ($unitData as $unitData) {
            if (! isset($kingdomUnitsToMove[$unitData['kingdom_id']])) {
                $kingdomUnitsToMove[$unitData['kingdom_id']][] = [
                    'unit_id' => $unitData['unit_id'],
                    'amount' => $unitData['amount'],
                ];
            } else {
                $kingdomUnitsToMove[$unitData['kingdom_id']][] = [
                    'unit_id' => $unitData['unit_id'],
                    'amount' => $unitData['amount'],
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
     */
    protected function moveUnits(Character $character, Kingdom $kingdom, array $unitData, int $fromKingdomId): void
    {

        $fromKingdom = $character->kingdoms()->find($fromKingdomId);

        $time = $this->determineTimeRequired($character, $kingdom, $fromKingdomId);

        $minutes = now()->addMinutes($time);

        $unitMovementQueue = UnitMovementQueue::create([
            'character_id' => $character->id,
            'from_kingdom_id' => $fromKingdom->id,
            'to_kingdom_id' => $kingdom->id,
            'units_moving' => $unitData,
            'completed_at' => $minutes,
            'started_at' => now(),
            'moving_to_x' => $kingdom->x_position,
            'moving_to_y' => $kingdom->y_position,
            'from_x' => $fromKingdom->x_position,
            'from_y' => $fromKingdom->y_position,
            'is_attacking' => false,
            'is_recalled' => false,
            'is_returning' => false,
            'is_moving' => true,
        ]);

        event(new UpdateKingdomQueues($kingdom));
        event(new UpdateKingdomQueues($fromKingdom));

        MoveUnits::dispatch($unitMovementQueue->id)->delay($minutes);
    }

    /**
     * Determine time required to move units.
     */
    public function determineTimeRequired(Character $character, Kingdom $kingdom, int $fromKingdomId, ?int $passiveSkillType = null): int
    {
        $fromKingdom = $character->kingdoms()->find($fromKingdomId);

        return $this->getDistanceTime($character, $kingdom, $fromKingdom, $passiveSkillType);
    }

    /**
     * Get the distance time when the fromKingdom is known.
     */
    public function getDistanceTime(Character $character, Kingdom $kingdom, Kingdom $fromKingdom, ?int $passiveSkillType = null): int
    {
        $pixelDistance = $this->distanceCalculation->calculatePixel(
            $fromKingdom->x_position,
            $fromKingdom->y_position,
            $kingdom->x_position,
            $kingdom->y_position
        );

        $timeToKingdom = $this->distanceCalculation->calculateMinutes($pixelDistance);

        if (is_null($passiveSkillType)) {
            $skill = $character->skills()->where('skill_type', SkillTypeValue::EFFECTS_KINGDOM)->first();

            $timeToKingdom -= ($timeToKingdom * $skill->unit_movement_time_reduction);

            if ($timeToKingdom < 1) {
                $timeToKingdom = 1;
            }

            return $timeToKingdom;
        }

        if ($passiveSkillType === PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_BUILD_TRAVEL_TIME_REDUCTION) {

            $skill = $character->passiveSkills->where('passiveSkill.effect_type', PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_BUILD_TRAVEL_TIME_REDUCTION)->first();

            $timeToKingdom -= ($timeToKingdom * $skill->capital_city_building_request_travel_time_reduction);

            if ($timeToKingdom < 1) {
                $timeToKingdom = 1;
            }

            return $timeToKingdom;
        }

        if ($passiveSkillType === PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION) {

            $skill = $character->passiveSkills->where('passiveSkill.effect_type', PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION)->first();

            $timeToKingdom -= ($timeToKingdom * $skill->capital_city_unit_request_travel_time_reduction);

            if ($timeToKingdom < 1) {
                $timeToKingdom = 1;
            }

            return $timeToKingdom;
        }

        if ($passiveSkillType === PassiveSkillTypeValue::RESOURCE_REQUEST_TIME_REDUCTION) {

            $skill = $character->passiveSkills->where('passiveSkillTypeValue.effect_type', PassiveSkillTypeValue::RESOURCE_REQUEST_TIME_REDUCTION)->first();

            $timeToKingdom -= ($timeToKingdom * $skill->resource_request_time_reduction);

            if ($timeToKingdom < 1) {
                $timeToKingdom = 1;
            }

            return $timeToKingdom;
        }

        if ($timeToKingdom < 1) {
            $timeToKingdom = 1;
        }

        return $timeToKingdom;
    }

    /**
     * Get unit data.
     */
    protected function getUnitData(Collection $units, Kingdom $playerKingdom): array
    {
        $unitData = [];

        foreach ($units as $unit) {
            if ($unit->amount === 0) {
                continue;
            }

            $unitData[] = [
                'kingdom_id' => $playerKingdom->id,
                'id' => $unit->id,
                'name' => $unit->name,
                'amount' => $unit->amount,
            ];
        }

        return $unitData;
    }

    /**
     * Fetch the amount we can send based on the amount already in the kingdom.
     */
    protected function fetchAmountToMove(Kingdom $kingdom, int $fromKingdomId, int $unitId, int $amount): int
    {
        $foundUnit = $this->getKingdomUnit($kingdom, $fromKingdomId, $unitId);

        if (! is_null($foundUnit)) {
            $amount = $amount + $foundUnit->amount;

            if ($amount > KingdomMaxValue::MAX_UNIT) {
                $amount = $amount - KingdomMaxValue::MAX_UNIT;
            }
        }

        return $amount;
    }

    /**
     * Get the time left in the movement.
     */
    protected function getTimeLeft(UnitMovementQueue $queue): float
    {
        $start = Carbon::parse($queue->started_at)->timestamp;
        $end = Carbon::parse($queue->completed_at)->timestamp;
        $current = Carbon::parse(now())->timestamp;

        return ($current - $start) / ($end - $start);
    }

    /**
     * Get the unit information if the kingdom requesting has the units already.
     */
    private function getKingdomUnit(Kingdom $kingdom, int $fromKingdomId, int $unitId): ?KingdomUnit
    {
        $unit = Kingdom::find($fromKingdomId)->units()->find($unitId);
        $unitName = $unit->gameUnit->name;

        $unit = $kingdom->units->filter(function ($unit) use ($unitName) {
            return $unit->gameUnit->name === $unitName;
        })->first();

        return $unit;
    }
}
