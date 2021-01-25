<?php

namespace App\Game\Kingdoms\Service;

use App\Console\Commands\UpdateKingdom;
use App\Flare\Models\Building;
use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Jobs\UpgradeBuilding;
use Carbon\Carbon;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class BuildingService {

    private $building;

    private $completed;

    private $totalResources;

    public function setBuilding(Building $building): BuildingService {
        $this->building = $building;

        return $this;
    }

    public function upgradeBuilding(Character $character) {
        $timeToComplete = now()->addMinutes($this->building->time_increase);
        
        $queue = BuildingInQueue::create([
            'character_id' => $character->id,
            'kingdom_id'   => $this->building->kingdom->id,
            'building_id'  => $this->building->id,
            'to_level'     => $this->building->level + 1,
            'completed_at' => $timeToComplete,
            'started_at'   => now(),
        ]);

        UpgradeBuilding::dispatch($this->building, $character->user, $queue->id)->delay($timeToComplete);
    }

    public function updateKingdomResourcesForBuildingUpgrade(Building $building): Kingdom {
        $building->kingdom->update([
            'current_wood'       => $building->kingdom->current_wood - $building->wood_cost,
            'current_clay'       => $building->kingdom->current_clay - $building->clay_cost,
            'current_stone'      => $building->kingdom->current_stone - $building->stone_cost,
            'current_iron'       => $building->kingdom->current_iron - $building->iron_cost,
            'current_population' => $building->kingdom->current_population - $building->required_population,
        ]);

        return $building->kingdom->refresh();
    }

    public function cancelBuildingUpgrade(BuildingInQueue $queue, Manager $manager, KingdomTransformer $transformer): bool {
        $this->resourceCalculation($queue);
        
        if (!($this->totalResources >= .10) || $this->completed === 0) {
           return false;
        }

        $building = $queue->building;
        $kingdom  = $building->kingdom; 

        $queue->delete();

        $kingdom = $this->updateKingdomAfterCancelation($kingdom, $building);
        
        $user    = $kingdom->character->user;

        $kingdom = new Item($kingdom, $transformer);

        $kingdom = $manager->createData($kingdom)->toArray();

        event(new UpdateKingdom($user, $kingdom));

        return true;
    }

    protected function resourceCalculation(BuildingInQueue $queue) {
        $start   = Carbon::parse($queue->started_at)->timestamp;
        $end     = Carbon::parse($queue->completed_at)->timestamp;
        $current = Carbon::parse(now())->timestamp;

        $this->completed      = (($current - $start) / ($end - $start));
        $this->totalResources = 1 - $this->completed;
    }

    protected function updateKingdomAfterCancelation(Kingdom $kingdom, Building $building) {
        $kingdom->update([
            'current_wood'       => $kingdom->current_wood + ($building->wood_cost * $this->totalResources),
            'current_clay'       => $kingdom->current_clay + ($building->clay_cost * $this->totalResources),
            'current_stone'      => $kingdom->current_stone + ($building->stone_cost * $this->totalResources),
            'current_iron'       => $kingdom->current_iron + ($building->iron_cost * $this->totalResources),
            'current_population' => $kingdom->current_population + ($building->required_population * $this->totalResources)
        ]);
        
        return $kingdom->refresh();
    }
}