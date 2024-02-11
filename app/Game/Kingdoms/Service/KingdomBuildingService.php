<?php

namespace App\Game\Kingdoms\Service;


use App\Game\Kingdoms\Events\UpdateKingdomQueues;
use App\Game\Kingdoms\Values\BuildingQueueType;
use Carbon\Carbon;
use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Kingdoms\Jobs\RebuildBuilding;
use App\Game\Kingdoms\Jobs\UpgradeBuilding;
use App\Game\Skills\Values\SkillTypeValue;

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
     * @var UpdateKingdomHandler
     */
    private UpdateKingdomHandler $updateKingdomHandler;

    /**
     * @param UpdateKingdomHandler $updateKingdomHandler
     */
    public function __construct(UpdateKingdomHandler $updateKingdomHandler) {
        $this->updateKingdomHandler = $updateKingdomHandler;
    }

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
        $timeToComplete = now()->addMinutes($this->calculateBuildingTimeReduction($building));

        $queue = BuildingInQueue::create([
            'character_id' => $character->id,
            'kingdom_id'   => $building->kingdom->id,
            'building_id'  => $building->id,
            'to_level'     => $building->level + 1,
            'completed_at' => $timeToComplete,
            'type'         => BuildingQueueType::UPGRADE,
            'started_at'   => now(),
        ]);

        event(new UpdateKingdomQueues($building->kingdom));

        UpgradeBuilding::dispatch($building, $character->user, $queue->id)->delay($timeToComplete);
    }

    /**
     * Rebuild the building.
     *
     * @param KingdomBuilding $building
     * @param Character $character
     */
    public function rebuildKingdomBuilding(KingdomBuilding $building, Character $character) {

        $timeReduction    = $building->kingdom->fetchKingBasedSkillValue('building_time_reduction');
        $minutesToRebuild = $building->rebuild_time;

        $minutesToRebuild = $minutesToRebuild - ($minutesToRebuild * $timeReduction);

        $timeToComplete = now()->addMinutes($minutesToRebuild);

        $queue = BuildingInQueue::create([
            'character_id' => $character->id,
            'kingdom_id'   => $building->kingdom->id,
            'building_id'  => $building->id,
            'to_level'     => $building->level,
            'completed_at' => $timeToComplete,
            'type'         => BuildingQueueType::REPAIR,
            'started_at'   => now(),
        ]);

        RebuildBuilding::dispatch($building, $character->user, $queue->id)->delay($timeToComplete);
    }

    /**
     * Updates the kingdoms resources based on building cost.
     *
     * @param KingdomBuilding $building
     * @param bool $ignorePop
     * @return Kingdom
     */
    public function updateKingdomResourcesForKingdomBuildingUpgrade(KingdomBuilding $building, bool $ignorePop = false): Kingdom {
        $buildingCostReduction   = $building->kingdom->fetchBuildingCostReduction();
        $ironCostReduction       = $building->kingdom->fetchIronCostReduction();
        $populationCostReduction = $building->kingdom->fetchPopulationCostReduction();

        $woodCost       = $building->wood_cost - $building->wood_cost * $buildingCostReduction;
        $clayCost       = $building->clay_cost - $building->clay_cost * $buildingCostReduction;
        $stoneCost      = $building->stone_cost - $building->stone_cost * $buildingCostReduction;
        $ironCost       = round($building->iron_cost - $building->iron_cost * ($buildingCostReduction + $ironCostReduction));
        $steelCost      = $building->steel_cost - $building->steel_cost * $buildingCostReduction;

        if (!$ignorePop) {
            $populationCost = $building->required_population - $building->required_population * ($populationCostReduction);

            $newPop = $building->kingdom->current_population - $populationCost;

            if ($newPop < 0) {
                $newPop = 0;
            }
        } else {
            $newPop = $building->kingdom->current_population;
        }

        $newWood  = $building->kingdom->current_wood - $woodCost;
        $newClay  = $building->kingdom->current_clay - $clayCost;
        $newStone = $building->kingdom->current_stone - $stoneCost;
        $newIron  = $building->kingdom->current_iron - $ironCost;
        $newSteel = $building->kingdom->current_steel - $steelCost;

        $building->kingdom->update([
            'current_wood'       => $newWood > 0 ? $newWood : 0,
            'current_clay'       => $newClay > 0 ? $newClay : 0,
            'current_stone'      => $newStone > 0 ? $newStone : 0,
            'current_iron'       => $newIron > 0 ? $newIron : 0,
            'current_steel'      => $newSteel > 0 ? $newSteel : 0,
            'current_population' => $newPop > 0 ? $newPop : 0,
        ]);

        return $building->kingdom->refresh();
    }

    public function updateKingdomResourcesForRebuildKingdomBuilding(KingdomBuilding $building): Kingdom {
        $buildingCostReduction   = $building->kingdom->fetchBuildingCostReduction();
        $ironCostReduction       = $building->kingdom->fetchIronCostReduction();
        $populationCostReduction = $building->kingdom->fetchPopulationCostReduction();

        $woodCost       = $building->level * $building->base_wood_cost;
        $clayCost       = $building->level * $building->base_clay_cost;
        $stoneCost      = $building->level * $building->base_stone_cost;
        $ironCost       = $building->level * $building->base_iron_cost;
        $populationCost = $building->level * $building->base_population;
        $steelCost      = $building->level * $building->base_steel_cost;

        $woodCost       -= $woodCost * $buildingCostReduction;
        $clayCost       -= $clayCost * $buildingCostReduction;
        $stoneCost      -= $stoneCost * $buildingCostReduction;
        $ironCost       -= $ironCost * ($buildingCostReduction + $ironCostReduction);
        $populationCost -= $populationCost * ($buildingCostReduction + $populationCostReduction);
        $steelCost      -= $steelCost * $buildingCostReduction;


        $building->kingdom->update([
            'current_wood'       => $building->kingdom->current_wood - $woodCost,
            'current_clay'       => $building->kingdom->current_clay - $clayCost,
            'current_stone'      => $building->kingdom->current_stone - $stoneCost,
            'current_iron'       => $building->kingdom->current_iron - $ironCost,
            'current_population' => $building->kingdom->current_population - $populationCost,
            'current_steel'      => $building->kingdom->current_steel - $steelCost,
        ]);

        return $building->kingdom->refresh();
    }

    /**
     * Cancels the building upgrade.
     *
     * Will cancel the resources if the total resources are above 10%.
     *
     * Can return false if there is not enough time left or too little resources given back.
     *
     * @codeCoverageIgnore
     * @param BuildingInQueue $queue
     * @return bool
     */
    public function cancelKingdomBuildingUpgrade(BuildingInQueue $queue): bool {
        $building = $queue->building;
        $kingdom  = $building->kingdom;

        $this->resourceCalculation($queue);

        if ($this->completed === 0 || !$this->totalResources >= .10) {
            return false;
        }

        $kingdom = $this->updateKingdomAfterCancellation($kingdom, $building);

        $queue->delete();

        $this->updateKingdomHandler->refreshPlayersKingdoms($kingdom->character->refresh());

        event(new UpdateKingdomQueues($kingdom));

        return true;
    }

    /**
     * Calculate the buildings time reduction.
     *
     * @param KingdomBuilding $building
     * @param int $toLevel
     * @return float
     */
    protected function calculateBuildingTimeReduction(KingdomBuilding $building, int $toLevel = 1) {
        $skillBonus = $building->kingdom->character->skills->filter(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::EFFECTS_KINGDOM;
        })->first()->building_time_reduction;

        if ($toLevel > 1) {
            $time = $building->fetchTimeForMultipleLevels($toLevel);

            return floor($time - $time * $skillBonus);
        }

        return floor($building->time_increase - $building->time_increase * $skillBonus);
    }

    /**
     * @codeCoverageIgnore
     * @param BuildingInQueue $queue
     */
    protected function resourceCalculation(BuildingInQueue $queue) {
        $start   = Carbon::parse($queue->started_at)->timestamp;
        $end     = Carbon::parse($queue->completed_at)->timestamp;
        $current = Carbon::parse(now())->timestamp;

        $this->completed      = (($current - $start) / ($end - $start));

        if ($this->completed === 0) {
            $this->totalResources = 0;
        } else {
            $this->totalResources = 1 - $this->completed;
        }
    }

    /**
     * @codeCoverageIgnore
     * @param Kingdom $kingdom
     * @param KingdomBuilding $building
     * @return Kingdom
     */
    protected function updateKingdomAfterCancellation(Kingdom $kingdom, KingdomBuilding $building): Kingdom {

        $newWood  = $kingdom->current_wood + ($building->wood_cost * $this->totalResources);
        $newClay  = $kingdom->current_clay + ($building->clay_cost * $this->totalResources);
        $newStone = $kingdom->current_stone + ($building->stone_cost * $this->totalResources);
        $newIron  = $kingdom->current_iron + ($building->iron_cost * $this->totalResources);
        $newSteel = $kingdom->current_steel + ($building->steel_cost * $this->totalResources);
        $newPop   = $kingdom->current_population + ($building->required_population * $this->totalResources);

        $kingdom->update([
            'current_wood'       => $newWood > $kingdom->max_wood ? $kingdom->max_wood : $newWood,
            'current_clay'       => $newClay > $kingdom->max_clay ? $kingdom->max_clay : $newClay,
            'current_stone'      => $newStone > $kingdom->max_stone ? $kingdom->max_stone : $newStone,
            'current_iron'       => $newIron > $kingdom->max_iron ? $kingdom->max_iron : $newIron,
            'current_steel'      => $newSteel > $kingdom->max_steel ? $kingdom->max_steel : $newSteel,
            'current_population' => $newPop > $kingdom->max_population ? $kingdom->max_population : $newPop
        ]);

        return $kingdom->refresh();
    }
}
