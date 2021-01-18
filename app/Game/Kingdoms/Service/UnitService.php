<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitInQueue;
use App\Game\Kingdoms\Jobs\RecruitUnits;

class UnitService {

    private $unit;

    private $kingdom;

    public function setUnit(GameUnit $unit): UnitService {
        $this->unit = $unit;

        return $this;
    }

    public function setKingdom(Kingdom $kingdom): UnitService {
        $this->kingdom = $kingdom;

        return $this;
    }

    public function recruitUnits(Character $character, int $amount) {
        $timeTillFinished = $this->unit->time_to_recruit * $amount;
        $timeTillFinished = now()->addMinutes($timeTillFinished);
        
        $queue = UnitInQueue::create([
            'character_id' => $character->id,
            'kingdom_id'   => $this->kingdom->id,
            'game_unit_id' => $this->unit->id,
            'amount'       => $amount,
            'completed_at' => $timeTillFinished,
            'started_at'   => now(),
        ]);


        RecruitUnits::dispatch($this->unit, $this->kingdom, $amount, $queue)->delay($timeTillFinished);
    }
}