<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Game\Kingdoms\Events\UpdateKingdom;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitInQueue;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Jobs\RecruitUnits;
use App\Game\Kingdoms\Values\UnitCosts;
use App\Game\Skills\Values\SkillTypeValue;
use Carbon\Carbon;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class UnitService {

    /**
     * @var mixed $compled
     */
    private $completed;

    /**
     * @var mixed $totalResources
     */
    private $totalResources;

    /**
     * Recruit a specific unit for a kingdom
     *
     * Will dispatch a job delayed for an amount of time.
     *
     * @param Kingdom $kingdom
     * @param GameUnit $gameUnit
     * @param int $amount
     */
    public function recruitUnits(Kingdom $kingdom, GameUnit $gameUnit, int $amount, bool $paidGold = false) {
        $character        = $kingdom->character;
        $totalTime        = $gameUnit->time_to_recruit * $amount;
        $timeTillFinished = now()->addSeconds($this->calculatueUnitRecrutmentTime($character, $totalTime));

        $goldPaid = null;

        if ($paidGold) {
            $goldPaid = (new UnitCosts($gameUnit->name))->fetchCost() * $amount;
        }

        $queue = UnitInQueue::create([
            'character_id' => $kingdom->character->id,
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
        $populationCostReduction  = $kingdom->fetchUnitCostReduction();

        $woodRequired = ($gameUnit->wood_cost * $amount);
        $woodRequired -= $woodRequired * $kingdomUnitCostReduction;

        $clayRequired = ($gameUnit->clay_cost * $amount);
        $clayRequired -= $clayRequired * $kingdomUnitCostReduction;

        $stoneRequired = ($gameUnit->stone_cost * $amount);
        $stoneRequired -= $stoneRequired * $kingdomUnitCostReduction;

        $ironRequired = ($gameUnit->iron_cost * $amount);
        $ironRequired -= $ironRequired * ($kingdomUnitCostReduction + $ironCostReduction);

        $populationRequired = ($gameUnit->required_population * $amount);
        $populationRequired -= $populationRequired * ($kingdomUnitCostReduction + $populationCostReduction);

        $kingdom->update([
            'current_wood'       => $kingdom->current_wood - $woodRequired,
            'current_clay'       => $kingdom->current_clay - $clayRequired,
            'current_stone'      => $kingdom->current_stone - $stoneRequired,
            'current_iron'       => $kingdom->current_iron - $ironRequired,
            'current_population' => $kingdom->current_population - $populationRequired,
        ]);

        return $kingdom->refresh();
    }

    /**
     * Allows the player to purchase units with gold.
     *
     * @param Kingdom $kingdom
     * @param GameUnit $gameUnit
     * @param int $amount
     */
    public function updateCharacterGold(Kingdom $kingdom, GameUnit $gameUnit, int $amount) {
        $character         = $kingdom->character;
        $unitCostReduction = $kingdom->fetchUnitCostReduction();

        $totalCost = (new UnitCosts($gameUnit->name))->fetchCost() * $amount;
        $totalCost -= $totalCost & $unitCostReduction;

        $character->gold -= $totalCost;

        $character->save();

        event(new UpdateTopBarEvent($character->refresh()));
    }

    /**
     * Cancel a recruitment order.
     *
     * Can return false if resources gained back are too little.
     *
     * @param UnitInQueue $queue
     * @param Manager $manager
     * @param KingdomTransformer $transfromer
     */
    public function cancelRecruit(UnitInQueue $queue, Manager $manager, KingdomTransformer $transfromer): bool {

        $kingdom = $queue->kingdom;
        $user    = $kingdom->character->user;

        if (!is_null($queue->gold_paid)) {

            if ($this->calculateElapsedTimePercent($queue) >= 85) {
                 return false;
            }

            $character = $queue->character;

            $character->gold += $queue->gold_paid * 0.75;

            $character->save();

            $kingdom->update([
                'current_population' => $kingdom->current_population + $queue->amount * 0.75
            ]);

            event(new UpdateTopBarEvent($character->refresh()));
        } else {
            $this->resourceCalculation($queue);

            if (!($this->totalResources >= .10)) {
                return false;
            }

            $unit    = $queue->unit;
            $kingdom = $this->updateKingdomAfterCancelation($kingdom, $unit, $queue);
        }

        $queue->delete();

        $kingdom  = new Item($kingdom->refresh(), $transfromer);

        $kingdom = $manager->createData($kingdom)->toArray();

        event(new UpdateKingdom($user, $kingdom));

        return true;
    }

    protected function calculatueUnitRecrutmentTime(Character $character, int $time)  {
        $skillBonus = $character->skills->filter(function($skill) {
            return $skill->baseSkill->type === SkillTypeValue::EFFECTS_KINGDOM;
        })->first();

        return floor($time - $time * $skillBonus->unit_time_reduction);
    }

    protected function calculateElapsedTimePercent(UnitInQueue $queue): int {
        $startedAt   = Carbon::parse($queue->started_at);
        $completedAt = Carbon::parse($queue->completed_at);
        $now         = now();

        $elapsedTime = $now->diffInMinutes($startedAt);
        $totalTime   = $completedAt->diffInMinutes($startedAt);

        return 100 - ceil($elapsedTime/$totalTime);
    }

    protected function resourceCalculation(UnitInQueue $queue) {
        $start   = Carbon::parse($queue->started_at)->timestamp;
        $end     = Carbon::parse($queue->completed_at)->timestamp;
        $current = Carbon::parse(now())->timestamp;

        $this->completed      = (($current - $start) / ($end - $start));
        $this->totalResources = 1 - $this->completed;
    }

    protected function updateKingdomAfterCancelation(Kingdom $kingdom, GameUnit $unit, UnitInQueue $queue): Kingdom {
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
