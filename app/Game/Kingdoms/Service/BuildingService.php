<?php

namespace App\Game\Kingdoms\Service;


use App\Flare\Models\Building;
use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\Character;
use App\Game\Kingdoms\Jobs\UpgradeBuilding;

class BuildingService {

    private $building;

    public function setBuilding(Building $building): BuildingService {
        $this->building = $building;

        return $this;
    }

    public function upgradeBuilding(Character $character) {
        $timeToComplete = now()->addMinutes($this->building->time_increase);
        
        BuildingInQueue::create([
            'character_id' => $character->id,
            'kingdom_id'   => $this->building->kingdom->id,
            'building_id'  => $this->building->id,
            'to_level'     => $this->building->level + 1,
            'completed_at' => $timeToComplete,
        ]);

        UpgradeBuilding::dispatch($this->building, $character->user)->delay($timeToComplete);
    }
}