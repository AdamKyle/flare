<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Skill;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateKingdom;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitInQueue;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Kingdoms\Jobs\RecruitUnits;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Kingdoms\Values\UnitCosts;
use App\Game\Skills\Values\SkillTypeValue;
use Carbon\Carbon;
use Exception;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class UnitService {

    use ResponseBuilder;

    /**
     * @var mixed $completed
     */
    private $completed;

    /**
     * @var mixed $totalResources
     */
    private $totalResources;

    /**
     * @var bool $paidGold
     */
    private bool $paidGold = false;

    /**
     * @var UpdateKingdomHandler $updateKingdomHandler
     */
    private UpdateKingdomHandler $updateKingdomHandler;

    /**
     * @param UpdateKingdomHandler $updateKingdomHandler
     */
    public function __construct(UpdateKingdomHandler $updateKingdomHandler) {
        $this->updateKingdomHandler = $updateKingdomHandler;
    }

    /**
     * Get whether we paid gold or not.
     *
     * @return bool
     */
    public function getPaidGold(): bool {
        return $this->paidGold;
    }

    /**
     * @param GameUnit $gameUnit
     * @param Kingdom $kingdom
     * @param string $recruitmentType
     * @param int $amount
     * @return array
     * @throws Exception
     */
    public function handlePayment(GameUnit $gameUnit, Kingdom $kingdom, string $recruitmentType, int $amount): array {
        if ($recruitmentType === 'resources') {
            if (ResourceValidation::shouldRedirectUnits($gameUnit, $kingdom, $amount)) {
                return $this->errorResult("You don't have the resources.");
            }

            $this->updateKingdomResources($kingdom, $gameUnit, $amount);
        } else {
            $amount              = $gameUnit->required_population * $amount;
            $populationReduction = $kingdom->fetchPopulationCostReduction();

            $amount = ceil($amount - $amount * $populationReduction);

            if ($amount > $kingdom->current_population) {
                return $this->errorResult("You don't have enough population to purchase with gold alone.");
            }

            $this->updateCharacterGold($kingdom, $gameUnit, $amount);

            $newPop = $kingdom->current_population - $amount;

            $kingdom->update([
                'current_population' => $newPop > 0 ? $newPop : 0
            ]);

            $this->paidGold = true;
        }

        return [];
    }

    /**
     * Recruit a specific unit for a kingdom
     *
     * Will dispatch a job delayed for an amount of time.
     *
     * @param Kingdom $kingdom
     * @param GameUnit $gameUnit
     * @param int $amount
     * @param bool $paidGold
     * @throws Exception
     */
    public function recruitUnits(Kingdom $kingdom, GameUnit $gameUnit, int $amount, bool $paidGold = false): void {
        $character        = $kingdom->character;
        $totalTime        = $gameUnit->time_to_recruit * $amount;
        $totalTime        = $totalTime - $totalTime * $this->fetchTimeReduction($character)->unit_time_reduction;

        $timeTillFinished = now()->addSeconds($totalTime);

        $goldPaid = null;

        if ($paidGold) {
            $goldPaid = (new UnitCosts($gameUnit->name))->fetchCost() * $amount;
            $goldPaid = $goldPaid - $goldPaid * $kingdom->fetchUnitCostReduction();
        }

        $queue = UnitInQueue::create([
            'character_id' => $character->id,
            'kingdom_id'   => $kingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount'       => $amount,
            'gold_paid'    => $goldPaid,
            'completed_at' => $timeTillFinished,
            'started_at'   => now(),
        ]);

        if ($totalTime > 900) {
            RecruitUnits::dispatch($gameUnit, $kingdom, $amount, $queue->id)->delay(now()->addMinutes(15));
        } else {
            RecruitUnits::dispatch($gameUnit, $kingdom, $amount, $queue->id)->delay($timeTillFinished);
        }
    }

    /**
     * Update the kingdom resources based on the cost.
     *
     * Subtracts cost from current amount.
     *
     * @param Kingdom $kingdom
     * @param GameUnit $gameUnit
     * @param int $amount
     * @return Kingdom
     */
    public function updateKingdomResources(Kingdom $kingdom, GameUnit $gameUnit, int $amount): Kingdom {
        $kingdomUnitCostReduction = $kingdom->fetchUnitCostReduction();
        $ironCostReduction        = $kingdom->fetchIronCostReduction();

        $woodRequired = ($gameUnit->wood_cost * $amount);
        $woodRequired -= $woodRequired * $kingdomUnitCostReduction;

        $clayRequired = ($gameUnit->clay_cost * $amount);
        $clayRequired -= $clayRequired * $kingdomUnitCostReduction;

        $stoneRequired = ($gameUnit->stone_cost * $amount);
        $stoneRequired -= $stoneRequired * $kingdomUnitCostReduction;

        $ironRequired = ($gameUnit->iron_cost * $amount);
        $ironRequired -= $ironRequired * ($kingdomUnitCostReduction + $ironCostReduction);

        $populationRequired = ($gameUnit->required_population * $amount);
        $populationRequired -= $populationRequired * $kingdomUnitCostReduction;

        $newWood  = $kingdom->current_wood - $woodRequired;
        $newClay  = $kingdom->current_clay - $clayRequired;
        $newStone = $kingdom->current_stone - $stoneRequired;
        $newIron  = $kingdom->current_iron - $ironRequired;
        $newPop   = $kingdom->current_population - $populationRequired;

        $kingdom->update([
            'current_wood'       => $newWood > 0 ? $newWood : 0,
            'current_clay'       => $newClay > 0 ? $newClay : 0,
            'current_stone'      => $newStone > 0 ? $newStone : 0,
            'current_iron'       => $newIron > 0 ? $newIron : 0,
            'current_population' => $newPop > 0 ? $newPop : 0,
        ]);

        return $kingdom->refresh();
    }

    /**
     * Allows the player to purchase units with gold.
     *
     * @param Kingdom $kingdom
     * @param GameUnit $gameUnit
     * @param int $amount
     * @throws Exception
     */
    public function updateCharacterGold(Kingdom $kingdom, GameUnit $gameUnit, int $amount) {
        $character         = $kingdom->character;
        $unitCostReduction = $kingdom->fetchUnitCostReduction();

        $totalCost = (new UnitCosts($gameUnit->name))->fetchCost() * $amount;
        $totalCost = $totalCost - $totalCost * $unitCostReduction;

        $character->gold -= $totalCost;

        if ($character->gold < 0) {
            $character->gold = 0;
        }

        $character->save();

        event(new UpdateTopBarEvent($character->refresh()));
    }

    /**
     * Cancel a recruitment order.
     *
     * Can return false if resources gained back are too little.
     *
     * @param UnitInQueue $queue
     * @return Kingdom|null
     */
    public function cancelRecruit(UnitInQueue $queue): ?Kingdom {

        $kingdom = $queue->kingdom;
        $user    = $kingdom->character->user;

        if (!is_null($queue->gold_paid)) {
            if ($this->calculateElapsedTimePercent($queue) >= 85) {
                 return false;
            }

            $character = $queue->character;

            $currentPopulation    = $kingdom->current_population;
            $currentCharacterGold = $character->gold;

            $populationToGiveBack = $queue->amount * 0.25;
            $goldToGiveBack       = $queue->gold_paid * 0.25;

            $newPopulation = ($currentPopulation + $populationToGiveBack);
            $newGold       = ($currentCharacterGold + $goldToGiveBack);

            if ($newGold > MaxCurrenciesValue::MAX_GOLD) {
                $newGold = MaxCurrenciesValue::MAX_GOLD;
            }

            if ($newPopulation > KingdomMaxValue::MAX_CURRENT_POPULATION) {
                $newPopulation = KingdomMaxValue::MAX_CURRENT_POPULATION;
            }

            $character->update([
                'gold' => $newGold
            ]);

            $kingdom->update([
                'current_population' => $newPopulation
            ]);

            $character = $character->refresh();

            event(new updateTopBarEvent($character));
        } else {
            $this->resourceCalculation($queue);

            if (!($this->totalResources >= .10)) {
                return null;
            }

            $unit    = $queue->unit;
            $kingdom = $this->updateKingdomAfterCancellation($kingdom, $unit, $queue);
        }

        $queue->delete();

        return $kingdom->refresh();
    }

    /**
     * Calculate elapsed time percent.
     *
     * @param UnitInQueue $queue
     * @return int
     */
    protected function calculateElapsedTimePercent(UnitInQueue $queue): int {
        $startedAt   = Carbon::parse($queue->started_at);
        $completedAt = Carbon::parse($queue->completed_at);
        $now         = now();

        $elapsedTime = $now->diffInMinutes($startedAt);
        $totalTime   = $completedAt->diffInMinutes($startedAt);

        if ($elapsedTime === 0) {
            return 0;
        }

        return 100 - (100 - ceil($elapsedTime/$totalTime));
    }

    /**
     * Calculate resources needed.
     *
     * @param UnitInQueue $queue
     * @return void
     */
    protected function resourceCalculation(UnitInQueue $queue): void {
        $start   = Carbon::parse($queue->started_at)->timestamp;
        $end     = Carbon::parse($queue->completed_at)->timestamp;
        $current = Carbon::parse(now())->timestamp;

        $this->completed      = (($current - $start) / ($end - $start));
        $this->totalResources = 1 - $this->completed;
    }

    /**
     * Fetch the time reduction for recruitment.
     *
     * @param Character $character
     * @return Skill
     */
    protected function fetchTimeReduction(Character $character): Skill  {
        return $character->skills->filter(function($skill) {
            return $skill->baseSkill->type === SkillTypeValue::EFFECTS_KINGDOM;
        })->first();
    }

    /**
     * Give back some resources when we cancel the recruitment.
     *
     * @param Kingdom $kingdom
     * @param GameUnit $unit
     * @param UnitInQueue $queue
     * @return Kingdom
     */
    protected function updateKingdomAfterCancellation(Kingdom $kingdom, GameUnit $unit, UnitInQueue $queue): Kingdom {
        $kingdom->update([
            'current_wood'       => $kingdom->current_wood + (($unit->wood_cost * $queue->amount) * $this->totalResources),
            'current_clay'       => $kingdom->current_clay + (($unit->clay_cost * $queue->amount) * $this->totalResources),
            'current_stone'      => $kingdom->current_stone + (($unit->stone_cost * $queue->amount) * $this->totalResources),
            'current_iron'       => $kingdom->current_iron + (($unit->iron_cost * $queue->amount) * $this->totalResources),
            'current_population' => $kingdom->current_population + (($unit->required_population * $queue->amount) * $this->totalResources)
        ]);

        return $kingdom->refresh();
    }
}
