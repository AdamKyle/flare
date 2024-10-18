<?php

namespace App\Game\Kingdoms\Service;

use Carbon\Carbon;
use App\Flare\Models\Character;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Skill;
use App\Flare\Models\UnitInQueue;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateKingdomQueues;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Kingdoms\Jobs\RecruitUnits;
use App\Game\Kingdoms\Validation\KingdomUnitResourceValidation;
use App\Game\Skills\Values\SkillTypeValue;

class UnitService
{
    use ResponseBuilder;

    /**
     * @var float $totalResources
     */
    private float $totalResources;

    /**
     * @param UpdateKingdomHandler $updateKingdomHandler
     * @param KingdomUnitResourceValidation $kingdomUnitResourceValidation
     */
    public function __construct(
        private UpdateKingdomHandler $updateKingdomHandler,
        private KingdomUnitResourceValidation $kingdomUnitResourceValidation
    ) {}

    public function handlePayment(GameUnit $gameUnit, Kingdom $kingdom, int $amount): array
    {
        if ($this->kingdomUnitResourceValidation->isMissingResources($kingdom, $gameUnit, $amount)) {
            return $this->errorResult("You don't have the resources.");
        }

        $this->updateKingdomResources($kingdom, $gameUnit, $amount);

        return [];
    }

    /**
     * Start recruting a sset of units.
     *
     * @param Kingdom $kingdom
     * @param GameUnit $gameUnit
     * @param integer $amount
     * @param integer|null $capitalCityQueueId
     * @return void
     */
    public function recruitUnits(Kingdom $kingdom, GameUnit $gameUnit, int $amount, ?int $capitalCityQueueId = null): void
    {
        $character = $kingdom->character;
        $totalTime = $this->getTotalTimeForUnitRecruitment($character, $gameUnit, $amount);

        $timeTillFinished = now()->addSeconds($totalTime);

        $queue = UnitInQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount' => $amount,
            'completed_at' => $timeTillFinished,
            'started_at' => now(),
        ]);

        event(new UpdateKingdomQueues($kingdom));

        if ($totalTime > 900) {
            RecruitUnits::dispatch($gameUnit, $kingdom, $amount, $queue->id, $capitalCityQueueId)->delay(now()->addMinutes(15));
        } else {
            RecruitUnits::dispatch($gameUnit, $kingdom, $amount, $queue->id, $capitalCityQueueId)->delay($timeTillFinished);
        }
    }

    /**
     * Get the total time for the unit recruitment
     *
     * @param Character $character
     * @param GameUnit $gameUnit
     * @param integer $amount
     * @return integer|float
     */
    public function getTotalTimeForUnitRecruitment(Character $character, GameUnit $gameUnit, int $amount): int|float
    {
        $totalTime = $gameUnit->time_to_recruit * $amount;

        return $totalTime - $totalTime * $this->fetchTimeReduction($character)->unit_time_reduction;
    }

    /**
     * Update kingdom resources when paying for a recruitment order for a unit.
     *
     * @param Kingdom $kingdom
     * @param GameUnit $gameUnit
     * @param integer $amount
     * @return Kingdom
     */
    public function updateKingdomResources(Kingdom $kingdom, GameUnit $gameUnit, int $amount): Kingdom
    {

        $costs = $this->kingdomUnitResourceValidation->getCostsRequired($kingdom, $gameUnit, $amount);

        $newResources = [
            'current_wood' => $kingdom->current_wood,
            'current_clay' => $kingdom->current_clay,
            'current_stone' => $kingdom->current_stone,
            'current_iron' => $kingdom->current_iron,
            'current_steel' => $kingdom->current_steel,
            'current_population' => $kingdom->current_population,
        ];


        foreach ($costs as $type => $cost) {
            $newResources['current_' . strtolower($type)] -= $cost;
        }

        $kingdom->update(array_map(fn($value) => max($value, 0), $newResources));

        return $kingdom->refresh();
    }

    /**
     * Update the kingdoms resources based off the total costs for a set of units.
     *
     * @param Kingdom $kingdom
     * @param array $totalCosts
     * @return Kingdom
     */
    public function updateKingdomResourcesForTotalCost(Kingdom $kingdom, array $totalCosts): Kingdom
    {
        $newResources = [
            'current_wood' => $kingdom->current_wood,
            'current_clay' => $kingdom->current_clay,
            'current_stone' => $kingdom->current_stone,
            'current_iron' => $kingdom->current_iron,
            'current_steel' => $kingdom->current_steel,
            'current_population' => $kingdom->current_population,
        ];

        foreach ($totalCosts as $type => $cost) {
            $newResources['current_' . strtolower($type)] -= $cost;
        }

        $kingdom->update(array_map(fn($value) => max($value, 0), $newResources));

        return $kingdom->refresh();
    }

    /**
     * Attempt to cancel a recruitment order
     *
     * @param UnitInQueue $queue
     * @return Kingdom|null
     */
    public function cancelRecruit(UnitInQueue $queue): ?Kingdom
    {

        $kingdom = $queue->kingdom;

        $this->resourceCalculation($queue);

        if (! ($this->totalResources >= .10)) {
            return null;
        }

        $unit = $queue->unit;
        $kingdom = $this->updateKingdomAfterCancellation($kingdom, $unit, $queue);

        $queue->delete();

        event(new UpdateKingdomQueues($kingdom));

        return $kingdom->refresh();
    }

    /**
     * Determine amount of resoources to give back.
     *
     * @param UnitInQueue $queue
     * @return void
     */
    protected function resourceCalculation(UnitInQueue $queue): void
    {
        $start = Carbon::parse($queue->started_at)->timestamp;
        $end = Carbon::parse($queue->completed_at)->timestamp;
        $current = Carbon::parse(now())->timestamp;

        $completed = (($current - $start) / ($end - $start));

        $this->totalResources = 1 - $completed;
    }

    /**
     * Fetch the time reduction
     *
     * @param Character $character
     * @return Skill
     */
    public function fetchTimeReduction(Character $character): Skill
    {
        return $character->skills->filter(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::EFFECTS_KINGDOM;
        })->first();
    }

    /**
     * Give some of the resources back
     *
     * @param Kingdom $kingdom
     * @param GameUnit $unit
     * @param UnitInQueue $queue
     * @return Kingdom
     */
    protected function updateKingdomAfterCancellation(Kingdom $kingdom, GameUnit $unit, UnitInQueue $queue): Kingdom
    {

        $kingdom->update([
            'current_wood' => min($kingdom->current_wood + (($unit->wood_cost * $queue->amount) * $this->totalResources), $kingdom->max_wood),
            'current_clay' => min($kingdom->current_clay + (($unit->clay_cost * $queue->amount) * $this->totalResources), $kingdom->max_clay),
            'current_stone' => min($kingdom->current_stone + (($unit->stone_cost * $queue->amount) * $this->totalResources), $kingdom->max_stone),
            'current_iron' => min($kingdom->current_iron + (($unit->iron_cost * $queue->amount) * $this->totalResources), $kingdom->max_iron),
            'current_steel' => min($kingdom->current_steel + (($unit->steel_cost * $queue->amount) * $this->totalResources), $kingdom->max_steel),
            'current_population' => min($kingdom->current_population + (($unit->required_population * $queue->amount) * $this->totalResources), $kingdom->max_population),
        ]);

        return $kingdom->refresh();
    }
}
