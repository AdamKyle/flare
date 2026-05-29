<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Events\UpdateKingdomQueues;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Kingdoms\Jobs\RebuildBuilding;
use App\Game\Kingdoms\Jobs\UpgradeBuilding;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\KingdomResources;
use App\Game\Skills\Values\SkillTypeValue;
use Carbon\Carbon;

class KingdomBuildingService
{
    private float $completed;

    private float $totalResources;

    public function __construct(private UpdateKingdomHandler $updateKingdomHandler) {}

    /**
     * Upgrades the building for a kingdom by dispatching a job with a BuildingInQueue record
     *
     * @return void always
     */
    public function upgradeKingdomBuilding(KingdomBuilding $building, Character $character, ?int $capitalCityQueueId = null): void
    {
        $timeToComplete = now()->addMinutes($this->calculateBuildingTimeReduction($building));

        $queue = BuildingInQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $building->kingdom->id,
            'building_id' => $building->id,
            'from_level' => $building->level,
            'to_level' => $building->level + 1,
            'completed_at' => $timeToComplete,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now(),
            'capital_city_building_queue_id' => $capitalCityQueueId,
        ]);

        event(new UpdateKingdomQueues($building->kingdom));

        UpgradeBuilding::dispatch($building, $character->user, $queue->id, $capitalCityQueueId)->delay($timeToComplete);
    }

    public function hasActiveBuildingUpgrade(KingdomBuilding $building): bool
    {
        if (BuildingInQueue::where('kingdom_id', $building->kingdom_id)
            ->where('building_id', $building->id)
            ->exists()
        ) {
            return true;
        }

        return CapitalCityBuildingQueue::query()
            ->where('kingdom_id', $building->kingdom_id)
            ->whereNotIn('status', [
                CapitalCityQueueStatus::REJECTED,
                CapitalCityQueueStatus::FINISHED,
                CapitalCityQueueStatus::CANCELLED,
                CapitalCityQueueStatus::CANCELLATION_REJECTED,
            ])
            ->get()
            ->contains(function (CapitalCityBuildingQueue $queue) use ($building) {
                return collect($queue->building_request_data)
                    ->reject(function (array $request) {
                        return in_array($request['secondary_status'] ?? null, [
                            CapitalCityQueueStatus::REJECTED,
                            CapitalCityQueueStatus::FINISHED,
                            CapitalCityQueueStatus::CANCELLED,
                            CapitalCityQueueStatus::CANCELLATION_REJECTED,
                        ], true);
                    })
                    ->contains(fn (array $request) => ($request['name'] ?? $request['building_name'] ?? null) === $building->name);
            });
    }

    public function cannotUpgradePastMaxLevel(KingdomBuilding $building, int $toLevel): bool
    {
        if ($building->level >= $building->gameBuilding->max_level) {
            return true;
        }

        if ($building->level + 1 > $building->gameBuilding->max_level) {
            return true;
        }

        return $toLevel > $building->gameBuilding->max_level;
    }

    public function hasInvalidUpgradeLevels(KingdomBuilding $building, ?int $fromLevel, int $toLevel): bool
    {
        if (! is_null($fromLevel) && $fromLevel !== $building->level) {
            return true;
        }

        return $toLevel !== $building->level + 1;
    }

    /**
     * Rebuild the kingdom by dispatching a job with a BuildingInQueue record
     *
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
            'capital_city_building_queue_id' => $capitalCityBuildingQueueId,
        ]);

        RebuildBuilding::dispatch($building, $character->user, $queue->id, $capitalCityBuildingQueueId)->delay($timeToComplete);
    }

    /**
     * Update the kingdom resources for a building that wants to upgrade.
     *
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
                if (! $ignorePop) {
                    $populationCost = $building->required_population - $building->required_population * $building->kingdom->fetchPopulationCostReduction();
                    if ($newResources['current_population'] < $populationCost) {
                        return $building->kingdom->refresh();
                    }

                    $newResources['current_population'] -= $populationCost;
                }
            } else {
                if ($newResources['current_'.strtolower($type)] < $cost) {
                    return $building->kingdom->refresh();
                }

                $newResources['current_'.strtolower($type)] -= $cost;
            }
        }

        $building->kingdom->update(array_map(fn ($value) => max($value, 0), $newResources));

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
            if ($type === KingdomResources::POPULATION->value) {
                $buildingCosts[$type] = intval(
                    $building->required_population - ($building->required_population * $populationCostReduction)
                );

                continue;
            }

            $costReduction = $buildingCostReduction;

            if ($type === KingdomResources::IRON->value) {
                $costReduction += $ironCostReduction;
            }

            $cost = $building->{$type.'_cost'};

            $buildingCosts[$type] = intval($cost - ($cost * $costReduction));
        }

        return $buildingCosts;
    }

    /**
     * Cancel a building upgrade
     */
    public function cancelKingdomBuildingUpgrade(BuildingInQueue $queue): bool
    {
        $building = $queue->building;
        $kingdom = $building->kingdom;

        $this->resourceCalculation($queue);

        if ($this->totalResources <= 0 || $this->totalResources < .10) {
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
     */
    private function resourceCalculation(BuildingInQueue $queue): void
    {
        $start = Carbon::parse($queue->started_at)->timestamp;
        $end = Carbon::parse($queue->completed_at)->timestamp;
        $current = Carbon::parse(now())->timestamp;

        if ($end <= $start) {
            $this->completed = 1;
            $this->totalResources = 0;

            return;
        }

        $this->completed = (($current - $start) / ($end - $start));

        $this->totalResources = max(0, min(1, 1 - $this->completed));
    }

    /**
     * Update the kingdoms resources after we cancel based on the total % of resources to give back.
     */
    private function updateKingdomAfterCancellation(Kingdom $kingdom, KingdomBuilding $building): Kingdom
    {
        $updateData = [];

        foreach (KingdomResources::kingdomResources() as $resource) {
            $cost = $resource === KingdomResources::POPULATION->value ? $building->required_population : $building->{$resource.'_cost'};
            $newAmount = $kingdom->{'current_'.$resource} + ($cost * $this->totalResources);
            $maxValue = 'max_'.$resource;

            $updateData['current_'.$resource] = min($newAmount, $kingdom->{$maxValue});
        }

        $kingdom->update($updateData);

        return $kingdom->refresh();
    }
}
