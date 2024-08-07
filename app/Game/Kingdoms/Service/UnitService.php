<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Skill;
use App\Flare\Models\UnitInQueue;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateKingdomQueues;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Kingdoms\Jobs\RecruitUnits;
use App\Game\Skills\Values\SkillTypeValue;
use Carbon\Carbon;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;

class UnitService
{
    use ResponseBuilder;

    private float $totalResources;

    private UpdateKingdomHandler $updateKingdomHandler;

    public function __construct(UpdateKingdomHandler $updateKingdomHandler)
    {
        $this->updateKingdomHandler = $updateKingdomHandler;
    }

    public function handlePayment(GameUnit $gameUnit, Kingdom $kingdom, int $amount): array
    {
        if (ResourceValidation::shouldRedirectUnits($gameUnit, $kingdom, $amount)) {
            return $this->errorResult("You don't have the resources.");
        }

        $this->updateKingdomResources($kingdom, $gameUnit, $amount);

        return [];
    }

    /**
     * Recruit a specific unit for a kingdom
     *
     * Will dispatch a job delayed for an amount of time.
     */
    public function recruitUnits(Kingdom $kingdom, GameUnit $gameUnit, int $amount, ?int $capitalCityQueueId = null): void
    {
        $character = $kingdom->character;
        $totalTime = $gameUnit->time_to_recruit * $amount;
        $totalTime = $totalTime - $totalTime * $this->fetchTimeReduction($character)->unit_time_reduction;
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

    public function getCostsRequired(Kingdom $kingdom, GameUnit $gameUnit, int $amount): array
    {
        $kingdomUnitCostReduction = $kingdom->fetchUnitCostReduction();
        $ironCostReduction = $kingdom->fetchIronCostReduction();

        $woodRequired = ($gameUnit->wood_cost * $amount);
        $woodRequired -= $woodRequired * $kingdomUnitCostReduction;

        $clayRequired = ($gameUnit->clay_cost * $amount);
        $clayRequired -= $clayRequired * $kingdomUnitCostReduction;

        $stoneRequired = ($gameUnit->stone_cost * $amount);
        $stoneRequired -= $stoneRequired * $kingdomUnitCostReduction;

        $ironRequired = ($gameUnit->iron_cost * $amount);
        $ironRequired -= $ironRequired * ($kingdomUnitCostReduction + $ironCostReduction);

        $steelCost = ($gameUnit->steel_cost * $amount);
        $steelCost -= $steelCost * $kingdomUnitCostReduction;

        $populationRequired = ($gameUnit->required_population * $amount);
        $populationRequired -= $populationRequired * $kingdomUnitCostReduction;

        return [
            'wood' => $woodRequired,
            'clay' => $clayRequired,
            'stone' => $stoneRequired,
            'iron' => $ironRequired,
            'steel' => $steelCost,
            'population' => $populationRequired,
        ];
    }

    /**
     * Update the kingdom resources based on the cost.
     *
     * Subtracts cost from current amount.
     */
    public function updateKingdomResources(Kingdom $kingdom, GameUnit $gameUnit, int $amount): Kingdom
    {

        $costs = $this->getCostsRequired($kingdom, $gameUnit, $amount);

        $newWood = $kingdom->current_wood - $costs['wood'];
        $newClay = $kingdom->current_clay - $costs['clay'];
        $newStone = $kingdom->current_stone - $costs['stone'];
        $newIron = $kingdom->current_iron - $costs['iron'];
        $newPop = $kingdom->current_population - $costs['population'];
        $newSteel = $kingdom->current_steel - $costs['steel'];

        $kingdom->update([
            'current_wood' => $newWood > 0 ? $newWood : 0,
            'current_clay' => $newClay > 0 ? $newClay : 0,
            'current_stone' => $newStone > 0 ? $newStone : 0,
            'current_iron' => $newIron > 0 ? $newIron : 0,
            'current_steel' => $newSteel > 0 ? $newSteel : 0,
            'current_population' => $newPop > 0 ? $newPop : 0,
        ]);

        return $kingdom->refresh();
    }

    /**
     * Cancel a recruitment order.
     *
     * Can return false if resources gained back are too little.
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
     * Calculate resources needed.
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
     * Fetch the time reduction for recruitment.
     */
    public function fetchTimeReduction(Character $character): Skill
    {
        return $character->skills->filter(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::EFFECTS_KINGDOM;
        })->first();
    }

    /**
     * Give back some resources when we cancel the recruitment.
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
