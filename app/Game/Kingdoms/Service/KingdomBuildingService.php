<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Events\UpdateKingdomQueues;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Kingdoms\Jobs\RebuildBuilding;
use App\Game\Kingdoms\Jobs\UpgradeBuilding;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Kingdoms\Values\KingdomResources;
use App\Game\Skills\Values\SkillTypeValue;
use Carbon\Carbon;

class KingdomBuildingService
{
    /**
     * @var float
     */
    private float $completed;

    /**
     * @var float
     */
    private float $totalResources;

    /**
     * @param UpdateKingdomHandler $updateKingdomHandler
     */
    public function __construct(private UpdateKingdomHandler $updateKingdomHandler) {}

    /**
     * Upgrades the building for a kingdom by dispatching a job with a BuildingInQueue record
     *
     * @param KingdomBuilding $building
     * @param Character $character
     * @param integer|null $capitalCityQueueId
     * @return void always
     */
    public function upgradeKingdomBuilding(KingdomBuilding $building, Character $character, ?int $capitalCityQueueId = null): void
    {
        $timeToComplete = now()->addMinutes($this->calculateBuildingTimeReduction($building));

        $queue = BuildingInQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $building->kingdom->id,
            'building_id' => $building->id,
            'to_level' => $building->level + 1,
            'completed_at' => $timeToComplete,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now(),
        ]);

        event(new UpdateKingdomQueues($building->kingdom));

        UpgradeBuilding::dispatch($building, $character->user, $queue->id, $capitalCityQueueId)->delay($timeToComplete);
    }

    /**
     * Rebuild the kingdom by dispatching a job with a BuildingInQueue record
     *
     * @param KingdomBuilding $building
     * @param Character $character
     * @param integer|null $capitalCityBuildingQueueId
     * @return void always
     */
    public function rebuildKingdomBuilding(KingdomBuilding $building, Character $character, ?int $capitalCityBuildingQueueId = null)
    {

        $timeReduction = $building->kingdom->fetchKingBasedSkillValue('building_time_reduction');
        $minutesToRebuild = $building->rebuild_time;

        $minutesToRebuild = $minutesToRebuild - ($minutesToRebuild * $timeReduction);

        $timeToComplete = now()->addMinutes($minutesToRebuild);

        $queue = BuildingInQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $building->kingdom->id,
            'building_id' => $building->id,
            'to_level' => $building->level,
            'completed_at' => $timeToComplete,
            'type' => BuildingQueueType::REPAIR,
            'started_at' => now(),
        ]);

        RebuildBuilding::dispatch($building, $character->user, $queue->id, $capitalCityBuildingQueueId)->delay($timeToComplete);
    }

    /**
     * Update the kingdom resources for a building that wants to upgrade.
     *
     * @param KingdomBuilding $building
     * @param boolean $ignorePop
     * @return Kingdom always
     */
    public function updateKingdomResourcesForKingdomBuildingUpgrade(KingdomBuilding $building, bool $ignorePop = false): Kingdom
    {
        $costs = $this->getBuildingCosts($building);

        $newResources = [
            'current_wood' => $building->kingdom->current_wood,
            'current_clay' => $building->kingdom->current_clay,
            'current_stone' => $building->kingdom->current_stone,
            'current_iron' => $building->kingdom->current_iron,
            'current_steel' => $building->kingdom->current_steel,
            'current_population' => $building->kingdom->current_population,
        ];


        foreach ($costs as $type => $cost) {
            if ($type === KingdomResources::POPULATION->value) {
                if (!$ignorePop) {
                    $populationCost = $building->required_population - $building->required_population * $building->kingdom->fetchPopulationCostReduction();
                    $newResources['current_population'] -= $populationCost;
                }
            } else {
                $newResources['current_' . strtolower($type)] -= $cost;
            }
        }

        $building->kingdom->update(array_map(fn($value) => max($value, 0), $newResources));

        return $building->kingdom->refresh();
    }

    /**
     * Returns the building costs minus any reductions from passive skills.
     *
     * @return float[]|int[]
     */
    public function getBuildingCosts(KingdomBuilding $building): array
    {
        $buildingCostReduction = $building->kingdom->fetchBuildingCostReduction();
        $ironCostReduction = $building->kingdom->fetchIronCostReduction();
        $populationCostReduction = $building->kingdom->fetchPopulationCostReduction();

        $buildingCosts = [];

        foreach (KingdomResources::kingdomResources() as $type) {

            if ($type === KingdomResources::IRON->value) {
                $buildingCosts[$type] =  intVal($building->{$type . '_cost'} * ($buildingCostReduction + $ironCostReduction));

                continue;
            }

            if ($type === KingdomResources::POPULATION->value) {
                $buildingCosts[$type] =  intVal($building->{$type . '_cost'} * ($buildingCostReduction + $populationCostReduction));

                continue;
            }

            $buildingCosts[$type] =  intVal($building->{$type . '_cost'} * $buildingCostReduction);
        }

        return $buildingCosts;
    }

    /**
     * Cancel a building upgrade
     *
     * @param BuildingInQueue $queue
     * @return boolean
     */
    public function cancelKingdomBuildingUpgrade(BuildingInQueue $queue): bool
    {
        $building = $queue->building;
        $kingdom = $building->kingdom;

        $this->resourceCalculation($queue);

        if ($this->completed === 0 || ! $this->totalResources >= .10) {
            return false;
        }

        $kingdom = $this->updateKingdomAfterCancellation($kingdom, $building);

        $queue->delete();

        $this->updateKingdomHandler->refreshPlayersKingdoms($kingdom->character->refresh());

        event(new UpdateKingdomQueues($kingdom));

        return true;
    }

    /**
     * Calculate the building time reduction
     *
     * @param KingdomBuilding $building
     * @param integer $toLevel
     * @return int|float
     */
    private function calculateBuildingTimeReduction(KingdomBuilding $building, int $toLevel = 1): int|float
    {
        $skillBonus = $building->kingdom->character->skills->filter(function ($skill) {
            return $skill->baseSkill->type === SkillTypeValue::EFFECTS_KINGDOM->value;
        })->first()->building_time_reduction;

        if ($toLevel > 1) {
            $time = $building->fetchTimeForMultipleLevels($toLevel);

            return floor($time - $time * $skillBonus);
        }

        return floor($building->time_increase - $building->time_increase * $skillBonus);
    }

    /**
     * Calculate resource percentage
     *
     * @param BuildingInQueue $queue
     * @return void
     */
    private function resourceCalculation(BuildingInQueue $queue): void
    {
        $start = Carbon::parse($queue->started_at)->timestamp;
        $end = Carbon::parse($queue->completed_at)->timestamp;
        $current = Carbon::parse(now())->timestamp;

        $this->completed = (($current - $start) / ($end - $start));

        if ($this->completed === 0) {
            $this->totalResources = 0;
        } else {
            $this->totalResources = 1 - $this->completed;
        }
    }

    /**
     * Update the kingdoms resources after we cancel based on the total % of resources to give back.
     *
     * @param Kingdom $kingdom
     * @param KingdomBuilding $building
     * @return Kingdom
     */
    private function updateKingdomAfterCancellation(Kingdom $kingdom, KingdomBuilding $building): Kingdom
    {
        $updateData = [];

        foreach (KingdomResources::kingdomResources() as $resource) {
            $newAmount = $kingdom->{'current_' . $resource} + ($building->{$resource . '_cost'} * $this->totalResources);
            $maxValue = 'max_' . $resource;

            $updateData['current_' . $resource] = min($newAmount, $kingdom->{$maxValue});
        }

        $kingdom->update($updateData);

        return $kingdom->refresh();
    }
}
