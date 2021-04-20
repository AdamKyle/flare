<?php

namespace App\Game\Kingdoms\Service;

use App\Game\Kingdoms\Events\UpdateKingdom;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitInQueue;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Jobs\RecruitUnits;
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
    public function recruitUnits(Kingdom $kingdom, GameUnit $gameUnit, int $amount) {
        $timeTillFinished = $gameUnit->time_to_recruit * $amount;
        $timeTillFinished = now()->addSeconds($timeTillFinished);

        $queue = UnitInQueue::create([
            'character_id' => $kingdom->character->id,
            'kingdom_id'   => $kingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount'       => $amount,
            'completed_at' => $timeTillFinished,
            'started_at'   => now(),
        ]);


        RecruitUnits::dispatch($gameUnit, $kingdom, $amount, $queue->id)->delay($timeTillFinished);
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
        $kingdom->update([
            'current_wood'       => $kingdom->current_wood - ($gameUnit->wood_cost * $amount),
            'current_clay'       => $kingdom->current_clay - ($gameUnit->clay_cost * $amount),
            'current_stone'      => $kingdom->current_stone - ($gameUnit->strone_cost * $amount),
            'current_iron'       => $kingdom->current_iron - ($gameUnit->iron_cost * $amount),
            'current_population' => $kingdom->current_population - ($gameUnit->required_population * $amount),
        ]);

        return $kingdom->refresh();
    }

    /**
     * Cancel a recturiment order.
     *
     * Can return false if resources gained back are too little.
     *
     * @param UnitInQueue $queue
     * @param Manager $manager
     * @param KingdomTransformer $transfromer
     */
    public function cancelRecruit(UnitInQueue $queue, Manager $manager, KingdomTransformer $transfromer): bool {

        $this->resourceCalculation($queue);

        if (!($this->totalResources >= .10)) {
           return false;
        }

        $unit    = $queue->unit;
        $kingdom = $queue->kingdom;
        $user    = $kingdom->character->user;

        $kingdom = $this->updateKingdomAfterCancelation($kingdom, $unit, $queue);

        $queue->delete();

        $kingdom  = new Item($kingdom->refresh(), $transfromer);

        $kingdom = $manager->createData($kingdom)->toArray();

        event(new UpdateKingdom($user, $kingdom));

        return true;
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
