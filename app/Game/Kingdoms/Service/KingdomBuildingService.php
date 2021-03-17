<?php

namespace App\Game\Kingdoms\Service;

use App\Console\Commands\UpdateKingdom;
use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\KingdomBuildingInQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Jobs\RebuildBuilding;
use App\Game\Kingdoms\Jobs\UpgradeBuilding;
use Carbon\Carbon;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class KingdomBuildingService {

    /**
     * @var mixed $completed
     */
    private $completed;

    /**
     * @var mixed $totalResources
     */
    private $totalResources;

    /**
     * Upgrades the building.
     * 
     * Create the building queue record and then dispatches based on the buildings time_increase
     * attribute.
     * 
     * @param KingdomBuilding $building
     * @param Character $character
     * @return void
     */
    public function upgradeKingdomBuilding(KingdomBuilding $building, Character $character): void {
        $timeToComplete = now()->addMinutes($building->time_increase);
        
        $queue = BuildingInQueue::create([
            'character_id' => $character->id,
            'kingdom_id'   => $building->kingdom->id,
            'building_id'  => $building->id,
            'to_level'     => $building->level + 1,
            'completed_at' => $timeToComplete,
            'started_at'   => now(),
        ]);

        UpgradeBuilding::dispatch($building, $character->user, $queue->id)->delay($timeToComplete);
    }

    public function rebuildKingdomBuilding(KingdomBuilding $building, Character $character) {
        $timeToComplete = now()->addMinutes($building->rebuild_time);
        
        $queue = BuildingInQueue::create([
            'character_id' => $character->id,
            'kingdom_id'   => $building->kingdom->id,
            'building_id'  => $building->id,
            'to_level'     => $building->level,
            'completed_at' => $timeToComplete,
            'started_at'   => now(),
        ]);

        RebuildBuilding::dispatch($building, $character->user, $queue->id)->delay($timeToComplete);
    }

    /**
     * Updates the kingdoms resources based on building cost.
     * 
     * @param KingdomBuilding $building
     * @return Kingdom
     */
    public function updateKingdomResourcesForKingdomBuildingUpgrade(KingdomBuilding $building): Kingdom {
        $building->kingdom->update([
            'current_wood'       => $building->kingdom->current_wood - $building->wood_cost,
            'current_clay'       => $building->kingdom->current_clay - $building->clay_cost,
            'current_stone'      => $building->kingdom->current_stone - $building->stone_cost,
            'current_iron'       => $building->kingdom->current_iron - $building->iron_cost,
            'current_population' => $building->kingdom->current_population - $building->required_population,
        ]);

        return $building->kingdom->refresh();
    }

    public function updateKingdomResourcesForRebuildKingdomBuilding(KingdomBuilding $building): Kingdom {
        $building->kingdom->update([
            'current_wood'       => $building->kingdom->current_wood - ($building->level * $building->base_wood_cost),
            'current_clay'       => $building->kingdom->current_clay - ($building->level * $building->base_clay_cost),
            'current_stone'      => $building->kingdom->current_stone - ($building->level * $building->base_stone_cost),
            'current_iron'       => $building->kingdom->current_iron - ($building->level * $building->base_iron_cost),
            'current_population' => $building->kingdom->current_population - ($building->level * $building->base_population),
        ]);

        return $building->kingdom->refresh();
    }

    /**
     * Cancels the building upgrade.
     * 
     * Will cancel the resources if the total resources are above 10%.
     * 
     * Can return false if there is not enough time left or the too little resources given back.
     * 
     * @param KingdomBuildingInQueue $queue
     * @param Manager $manager
     * @param KingdomTransformer $transformer
     * @return bool
     */
    public function cancelKingdomBuildingUpgrade(BuildingInQueue $queue, Manager $manager, KingdomTransformer $transformer): bool {
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

    protected function updateKingdomAfterCancelation(Kingdom $kingdom, KingdomBuilding $building) {
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