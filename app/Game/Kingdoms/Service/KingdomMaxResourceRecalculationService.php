<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Kingdom;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;

class KingdomMaxResourceRecalculationService
{
    private const BASE_MAX_RESOURCES = 2000;

    private const BASE_MAX_POPULATION = 100;

    private const RESOURCE_BUILDING_CAPACITY_PER_LEVEL = 1000;

    private array $resourceTypes = [
        'stone',
        'wood',
        'clay',
        'iron',
    ];

    public function recalculate(Kingdom $kingdom, bool $preserveCurrentCapacity = false): Kingdom
    {
        $kingdom = $kingdom->refresh();
        $passiveIncrease = $this->bountifulResourcesIncrease($kingdom);
        $maxResources = [
            'stone' => self::BASE_MAX_RESOURCES,
            'wood' => self::BASE_MAX_RESOURCES,
            'clay' => self::BASE_MAX_RESOURCES,
            'iron' => self::BASE_MAX_RESOURCES,
        ];
        $maxPopulation = self::BASE_MAX_POPULATION;

        foreach ($kingdom->buildings as $building) {
            if ($building->gives_resources && $building->level > 1) {
                foreach ($this->resourceTypes as $resourceType) {
                    if ($building->gameBuilding->{'increase_'.$resourceType.'_amount'} > 0) {
                        $maxResources[$resourceType] += ($building->level - 1) * self::RESOURCE_BUILDING_CAPACITY_PER_LEVEL;
                    }
                }
            }

            if ($building->is_farm && $building->level > 1) {
                for ($level = 2; $level <= $building->level; $level++) {
                    $maxPopulation += ($level * 100) + 100;
                }
            }
        }

        $calculatedMaxPopulation = $maxPopulation + $passiveIncrease;

        if ($preserveCurrentCapacity) {
            $calculatedMaxPopulation = max($kingdom->current_population, $calculatedMaxPopulation);
        }

        $calculatedMaxResources = [
            'stone' => $maxResources['stone'] + $passiveIncrease,
            'wood' => $maxResources['wood'] + $passiveIncrease,
            'clay' => $maxResources['clay'] + $passiveIncrease,
            'iron' => $maxResources['iron'] + $passiveIncrease,
        ];

        if ($preserveCurrentCapacity) {
            foreach ($this->resourceTypes as $resourceType) {
                $calculatedMaxResources[$resourceType] = max(
                    $kingdom->{'current_'.$resourceType},
                    $calculatedMaxResources[$resourceType]
                );
            }
        }

        $kingdom->update([
            'max_stone' => $calculatedMaxResources['stone'],
            'max_wood' => $calculatedMaxResources['wood'],
            'max_clay' => $calculatedMaxResources['clay'],
            'max_iron' => $calculatedMaxResources['iron'],
            'max_population' => $calculatedMaxPopulation,
        ]);

        return $kingdom->refresh();
    }

    private function bountifulResourcesIncrease(Kingdom $kingdom): int
    {
        if (is_null($kingdom->character)) {
            return 0;
        }

        $passive = $kingdom->character->passiveSkills
            ->where('passiveSkill.effect_type', PassiveSkillTypeValue::RESOURCE_INCREASE)
            ->first();

        if (is_null($passive)) {
            return 0;
        }

        return $passive->resource_increase_amount;
    }
}
