<?php

namespace App\Console\AfterDeployment;

use App\Flare\ExponentialCurve\Curve\ExponentialAttributeCurve;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Raid;
use App\Flare\Values\LocationType;
use App\Flare\Values\MapNameValue;
use App\Game\Raids\Values\RaidType;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class ReBalanceMonsters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balance:monsters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Balances Monsters';

    private array $regularMaps = [
        MapNameValue::SURFACE,
        MapNameValue::LABYRINTH,
        MapNameValue::DUNGEONS,
        MapNameValue::SHADOW_PLANE,
        MapNameValue::HELL,
    ];

    private array $endGameMaps = [
        MapNameValue::PURGATORY,
        MapNameValue::TWISTED_MEMORIES
    ];

    private array $eventMaps = [
        MapNameValue::ICE_PLANE,
        MapNameValue::DELUSIONAL_MEMORIES,
    ];

    /**
     * Execute the console command.
     */
    public function handle(ExponentialAttributeCurve $exponentialAttributeCurve)
    {

        $this->rebalanceRegularmaps($exponentialAttributeCurve);

        $this->rebalanceEndGameMonsters($exponentialAttributeCurve);

        $this->rebalanceWeeklyFights($exponentialAttributeCurve);

        $this->rebalanceSpecialEventMaps($exponentialAttributeCurve);

        $this->rebalanceRaidMonsters($exponentialAttributeCurve);

        $this->rebalanceRaidBosses($exponentialAttributeCurve);
    }

    /**
     * Rebalance Regular monsters
     *
     * - This exludes event maps
     * - This excludes Purgatory and Twisted Memories
     *
     * @param ExponentialAttributeCurve $exponentialAttributeCurve
     * @return void
     */
    private function rebalanceRegularmaps(ExponentialAttributeCurve $exponentialAttributeCurve): void
    {
        foreach ($this->regularMaps as $mapName) {
            $gameMap = GameMap::where('name', $mapName)->first();
            $monsters = Monster::where('game_map_id', $gameMap->id)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->where('is_celestial_entity', false)
                ->whereNull('only_for_location_type')
                ->get();

            $this->manageMonsters($monsters, $exponentialAttributeCurve, 2, 2_000_000_000, 100_000, 500, $mapName);

            $celestials = Monster::where('game_map_id', $gameMap->id)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->where('is_celestial_entity', true)
                ->whereNull('only_for_location_type')
                ->get();

            $this->manageMonsters($celestials, $exponentialAttributeCurve, 50_000_000, 300_000_000, 1_000_000, 5_000, $mapName);
        }
    }

    /**
     * Rebalance End Game Map Monsters
     *
     * @param ExponentialAttributeCurve $exponentialAttributeCurve
     * @return void
     */
    private function rebalanceEndGameMonsters(ExponentialAttributeCurve $exponentialAttributeCurve): void
    {
        foreach ($this->endGameMaps as $mapName) {
            $gameMap = GameMap::where('name', $mapName)->first();
            $monsters = Monster::where('game_map_id', $gameMap->id)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->where('is_celestial_entity', false)
                ->whereNull('only_for_location_type')
                ->get();

            $mapNameValue = new MapNameValue($mapName);
            $statRangeData = $this->fetchGameMapMonsterStatRange($mapNameValue);
            $statRangeData = (object) $statRangeData->toArray();

            $this->manageMonsters($monsters, $exponentialAttributeCurve, $statRangeData->min, $statRangeData->max, $statRangeData->increase, $statRangeData->range, $mapName);

            $celestials = Monster::where('game_map_id', $gameMap->id)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->where('is_celestial_entity', true)
                ->whereNull('only_for_location_type')
                ->get();

            $this->manageMonsters($celestials, $exponentialAttributeCurve, 750_000_000, 3_500_000_000, 10_000_000, 50_000, $mapName);
        }
    }

    /**
     * Rebalance Special Event Based Maps
     *
     * @param ExponentialAttributeCurve $exponentialAttributeCurve
     * @return void
     */
    private function rebalanceSpecialEventMaps(ExponentialAttributeCurve $exponentialAttributeCurve): void
    {
        foreach ($this->eventMaps as $mapName) {
            $gameMap = GameMap::where('name', $mapName)->first();
            $monsters = Monster::where('game_map_id', $gameMap->id)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->where('is_celestial_entity', false)
                ->whereNotNull('only_for_location_type')
                ->get();

            $mapNameValue = new MapNameValue($mapName);
            $statRangeData = $this->fetchGameMapMonsterStatRange($mapNameValue);
            $statRangeData = (object) $statRangeData->toArray();

            $this->manageMonsters($monsters, $exponentialAttributeCurve, $statRangeData->min, $statRangeData->max, $statRangeData->increase, $statRangeData->range, $mapName);

            $celestials = Monster::where('game_map_id', $gameMap->id)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->where('is_celestial_entity', true)
                ->whereNull('only_for_location_type')
                ->get();

            $this->manageMonsters($celestials, $exponentialAttributeCurve, 5_000_000_000, 15_500_000_000, 100_000_000, 500_000, $mapName);
        }
    }

    /**
     * Rebalance Weekly fights
     *
     * @param ExponentialAttributeCurve $exponentialAttributeCurve
     * @return void
     */
    private function rebalanceWeeklyFights(ExponentialAttributeCurve $exponentialAttributeCurve): void
    {
        $locations = Location::whereNotNull('type')->get();

        foreach ($locations as $location) {
            $gameMap = GameMap::find($location->game_map_id);

            $locationType = new LocationType($location->type);

            $statRangeData = $this->fetchWeeklyMonsterStatRange($locationType);
            $statRangeData = (object) $statRangeData->toArray();

            $monsters = Monster::where('game_map_id', $gameMap->id)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->where('is_celestial_entity', false)
                ->where('only_for_location_type', $location->type)
                ->get();

            $this->manageMonsters($monsters, $exponentialAttributeCurve, $statRangeData->min, $statRangeData->max, $statRangeData->increase, $statRangeData->range, $gameMap->name);
        }
    }

    /**
     * Rebalance the raid monsters based on the raid type.
     *
     * @param ExponentialAttributeCurve $exponentialAttributeCurve
     * @return void
     */
    private function rebalanceRaidBosses(ExponentialAttributeCurve $exponentialAttributeCurve): void
    {
        $raids = Raid::all();

        foreach ($raids as $raid) {
            $monsterIds = $raid->monster_ids;

            $raidType = new RaidType($raid->raid_type);

            $statRangeData = $this->fetchRaidBossStats($raidType);
            $statRangeData = (object) $statRangeData->toArray();

            $monsters = Monster::where('is_raid_monster', false)
                ->whereIn('id', $monsterIds)
                ->where('is_raid_boss', true)
                ->where('is_celestial_entity', false)
                ->whereNotNull('only_for_location_type')
                ->get();

            $this->manageMonsters($monsters, $exponentialAttributeCurve, $statRangeData->min, $statRangeData->max, $statRangeData->increase, $statRangeData->range, $mapName);
        }
    }

    /**
     * Rebalance the raid monsters based on the raid type.
     *
     * @param ExponentialAttributeCurve $exponentialAttributeCurve
     * @return void
     */
    private function rebalanceRaidMonsters(ExponentialAttributeCurve $exponentialAttributeCurve): void
    {
        $raids = Raid::all();

        foreach ($raids as $raid) {
            $monsterIds = $raid->monster_ids;

            $raidType = new RaidType($raid->raid_type);

            $statRangeData = $this->fetchRaidMonsterStats($raidType);
            $statRangeData = (object) $statRangeData->toArray();

            $monsters = Monster::where('is_raid_monster', true)
                ->whereIn('id', $monsterIds)
                ->where('is_raid_boss', false)
                ->where('is_celestial_entity', false)
                ->whereNotNull('only_for_location_type')
                ->get();

            $this->manageMonsters($monsters, $exponentialAttributeCurve, $statRangeData->min, $statRangeData->max, $statRangeData->increase, $statRangeData->range, $mapName);
        }
    }

    /**
     * Fetch the weekly monster stat range data based on location type
     *
     * @param LocationType $locationType
     * @return SupportCollection
     */
    private function fetchWeeklyMonsterStatRange(LocationType $locationType): SupportCollection
    {
        return match ($locationType) {
            $locationType->isLordsStrongHold() => collect([
                'min' => 10_000_000,
                'max' => 30_000_000,
                'increase' => 10_000_000,
                'range' => 1_000_000,
            ]),
            $locationType->isHellsBrokenAnvil() => collect([
                'min' => 50_000_000,
                'max' => 100_000_000,
                'increase' => 12_000_000,
                'range' => 2_000_000,
            ]),
            $locationType->isAlchemyChurch() => collect([
                'min' => 100_000_000_000,
                'max' => 400_000_000_000,
                'increase' => 24_000_000,
                'range' => 4_000_000,
            ]),
            $locationType->isTwistedMaidensDungeons() => collect([
                'min' => 500_000_000_000,
                'max' => 2_000_000_000_000,
                'increase' => 100_000_000_000,
                'range' => 10_000_000,
            ]),
            default => collect([
                'min' => 10_000_000,
                'max' => 30_000_000,
                'increase' => 10_000_000,
                'range' => 1_000_000,
            ])
        };
    }

    /**
     * Fetch game map monster stats based on the map
     *
     * @param MapNameValue $mapNameValue
     * @return SupportCollection
     */
    private function fetchGameMapMonsterStatRange(MapNameValue $mapNameValue): SupportCollection
    {
        return match ($mapNameValue) {
            $mapNameValue->isPurgatory() => collect([
                'min' => 1_000_000_000,
                'max' => 4_000_000_000,
                'increase' => 500_000,
                'range' => 5_000,
            ]),
            $mapNameValue->isTwistedMemories() => collect([
                'min' => 2_000_000_000,
                'max' => 5_500_000_000,
                'increase' => 500_000,
                'range' => 5_000,
            ]),
            $mapNameValue->isTheIcePlane(),
            $mapNameValue->isDelusionalMemories() => collect([
                'min' => 10_000_000_000,
                'max' => 20_000_000_000,
                'increase' => 1_000_000,
                'range' => 500_000,
            ]),
            default => collect([
                'min' => 1_000_000_000,
                'max' => 4_000_000_000,
                'increase' => 500_000,
                'range' => 5_000,
            ])
        };
    }

    /**
     * Fetch raid monster stats based on the raid type
     *
     * @param RaidType $raidType
     * @return SupportCollection
     */
    private function fetchRaidMonsterStats(RaidType $raidType): SupportCollection
    {
        return match ($raidType) {
            $raidType->isPirateLordRaid() => collect([
                'min' => 1_000_000_000,
                'max' => 4_000_000_000,
                'increase' => 500_000,
                'range' => 5_000,
            ]),
            $raidType->isIceQueenRaid(), => collect([
                'min' => 25_000_000_000,
                'max' => 50_000_000_000,
                'increase' => 5_000_000,
                'range' => 500_000,
            ]),
            $raidType->isJesterOfTime() => collect([
                'min' => 75_000_000_000,
                'max' => 100_000_000_000,
                'increase' => 10_000_000,
                'range' => 5_000_000,
            ]),
            default => collect([
                'min' => 1_000_000_000,
                'max' => 4_000_000_000,
                'increase' => 500_000,
                'range' => 5_000,
            ])
        };
    }

    /**
     * Fetch raid boss stats based on raid type
     *
     * @param RaidType $raidType
     * @return SupportCollection
     */
    private function fetchRaidBossStats(RaidType $raidType): SupportCollection
    {
        return match ($raidType) {
            $raidType->isPirateLordRaid() => collect([
                'min' => 100_000_000_000,
                'max' => 400_000_000_000,
                'increase' => 500_000,
                'range' => 5_000,
            ]),
            $raidType->isIceQueenRaid(), => collect([
                'min' => 250_000_000_000,
                'max' => 500_000_000_000,
                'increase' => 5_000_000,
                'range' => 500_000,
            ]),
            $raidType->isJesterOfTime() => collect([
                'min' => 750_000_000_000,
                'max' => 1_000_000_000_000,
                'increase' => 10_000_000,
                'range' => 5_000_000,
            ]),
            default => collect([
                'min' => 100_000_000_000,
                'max' => 400_000_000_000,
                'increase' => 500_000,
                'range' => 5_000,
            ])
        };
    }


    /**
     * Rebalance the actual monsters with the range data.
     *
     * @param Collection $monsters
     * @param ExponentialAttributeCurve $exponentialAttributeCurve
     * @param integer $min
     * @param integer $max
     * @param integer $increase
     * @param integer $range
     * @param string $mapName
     * @param boolean $isSpecialMonster
     * @return void
     */
    private function manageMonsters(Collection $monsters, ExponentialAttributeCurve $exponentialAttributeCurve, int $min, int $max, int $increase, int $range, string $mapName, bool $isSpecialMonster = false): void
    {
        $floats = $this->generateFloats($exponentialAttributeCurve, $monsters->count());
        $integers = $this->generateIntegers($exponentialAttributeCurve, $monsters->count(), $min, $max, $increase, $range);
        $xpIntegers = [];

        if (! $isSpecialMonster) {
            $xpIntegers = $this->getXPIntegers($exponentialAttributeCurve, $monsters->count(), $mapName);
        }

        $atonements = $this->fetchElementalAtonements($exponentialAttributeCurve, $mapName, $monsters->count(), $isSpecialMonster);

        foreach ($monsters as $index => $monster) {
            $monsterStats = $this->setMonsterStats($floats, $integers, $xpIntegers, $index);

            if (isset($atonements[$index])) {
                $monsterStats = array_merge($monsterStats, $atonements[$index]);
            }

            $monster->update($monsterStats);
        }
    }

    /**
     * Fetch monster elemental atonement data
     *
     * @param ExponentialAttributeCurve $exponentialAttributeCurve
     * @param string $mapName
     * @param integer $monsterCount
     * @param boolean $isRaidMonster
     * @return array
     */
    private function fetchElementalAtonements(ExponentialAttributeCurve $exponentialAttributeCurve, string $mapName, int $monsterCount, bool $isRaidMonster): array
    {
        $primaryAtonement = null;
        $startingValue = 0;
        $maxValue = 0;

        if ($mapName === MapNameValue::SURFACE && $isRaidMonster) {
            $primaryAtonement = 'fire';
            $startingValue = .15;
            $maxValue = 0.60;
        }

        if ($mapName === MapNameValue::ICE_PLANE && $isRaidMonster) {
            $primaryAtonement = 'ice';
            $startingValue = .25;
            $maxValue = 0.65;
        } elseif ($mapName === MapNameValue::ICE_PLANE) {
            $primaryAtonement = 'ice';
            $startingValue = .10;
            $maxValue = 0.55;
        }

        if ($mapName === MapNameValue::DELUSIONAL_MEMORIES && $isRaidMonster) {
            $primaryAtonement = 'water';
            $startingValue = .35;
            $maxValue = 0.68;
        } elseif ($mapName === MapNameValue::DELUSIONAL_MEMORIES) {
            $primaryAtonement = 'water';
            $startingValue = .15;
            $maxValue = 0.55;
        }

        if ($mapName === MapNameValue::TWISTED_MEMORIES) {
            $primaryAtonement = 'fire';
            $startingValue = .15;
            $maxValue = 0.55;
        }

        if (is_null($primaryAtonement) && $startingValue <= 0 && $maxValue <= 0) {
            return [];
        }

        return $this->fetchAtonementDataForMonsters($exponentialAttributeCurve, $monsterCount, $primaryAtonement, $startingValue, $maxValue);
    }

    /**
     * Get XP Data for the monsters
     *
     * @param ExponentialAttributeCurve $exponentialAttributeCurve
     * @param integer $size
     * @param string|null $mapName
     * @return array
     */
    private function getXPIntegers(ExponentialAttributeCurve $exponentialAttributeCurve, int $size, ?string $mapName = null): array
    {
        if (in_array($mapName, $this->regularMaps)) {

            if ($mapName === MapNameValue::SURFACE) {
                return $this->generateIntegers($exponentialAttributeCurve, $size, 2, 100, 2, 1);
            }

            if ($mapName === MapNameValue::LABYRINTH) {
                return $this->generateIntegers($exponentialAttributeCurve, $size, 2, 200, 2, 5);
            }

            if ($mapName === MapNameValue::DUNGEONS) {
                return $this->generateIntegers($exponentialAttributeCurve, $size, 2, 250, 2, 5);
            }

            if ($mapName === MapNameValue::SHADOW_PLANE) {
                return $this->generateIntegers($exponentialAttributeCurve, $size, 2, 500, 2, 8);
            }

            if ($mapName === MapNameValue::HELL) {
                return $this->generateIntegers($exponentialAttributeCurve, $size, 2, 1000, 2, 10);
            }
        }

        if ($mapName === MapNameValue::PURGATORY) {
            return $this->generateIntegers($exponentialAttributeCurve, $size, 10, 2000, 10, 20);
        }

        if ($mapName === MapNameValue::TWISTED_MEMORIES) {
            return $this->generateIntegers($exponentialAttributeCurve, $size, 60, 4000, 40, 50);
        }

        if (
            $mapName === MapNameValue::ICE_PLANE ||
            $mapName === MapNameValue::DELUSIONAL_MEMORIES
        ) {
            return $this->generateIntegers($exponentialAttributeCurve, $size, 70, 5000, 20, 25);
        }

        // Raid Monsters
        return $this->generateIntegers($exponentialAttributeCurve, $size, 100, 4500, 100, 50);
    }

    /**
     * Fetch elemental atonrment data for monsters for automation purposes.
     *
     * @param ExponentialAttributeCurve $exponentialAttributeCurve
     * @param integer $monsterCount
     * @param string $primaryAtonement
     * @param float $startingValue
     * @param float $maxValue
     * @return array
     */
    private function fetchAtonementDataForMonsters(ExponentialAttributeCurve $exponentialAttributeCurve, int $monsterCount, string $primaryAtonement, float $startingValue, float $maxValue): array
    {
        $floats = $this->generateFloats($exponentialAttributeCurve, $monsterCount, $startingValue, $maxValue);
        $atonements = [];

        for ($i = 1; $i <= $monsterCount; $i++) {
            $value = $floats[$i - 1];

            $atonements[] = [
                'fire_atonement' => $primaryAtonement !== 'fire' ? $value / 2 : min($value, $maxValue),
                'ice_atonement' => $primaryAtonement !== 'ice' ? $value / 2 : min($value, $maxValue),
                'water_atonement' => $primaryAtonement !== 'water' ? $value / 2 : min($value, $maxValue),
            ];
        }

        return $atonements;
    }

    /**
     * Generate float based stats
     *
     * @param ExponentialAttributeCurve $exponentialAttributeCurve
     * @param integer $size
     * @param float $min
     * @param float $max
     * @param float $increase
     * @param float $range
     * @return array
     */
    private function generateFloats(ExponentialAttributeCurve $exponentialAttributeCurve, int $size, float $min = 0.001, float $max = 1.0, float $increase = 0.08, float $range = 0.01): array
    {
        $curve = $exponentialAttributeCurve->setMin($min)
            ->setMax($max)
            ->setIncrease($increase)
            ->setRange($range);

        return $curve->generateValues($size);
    }

    /**
     * Generate integer based stats
     *
     * @param ExponentialAttributeCurve $exponentialAttributeCurve
     * @param integer $size
     * @param integer $min
     * @param integer $max
     * @param integer $increase
     * @param integer $range
     * @return array
     */
    private function generateIntegers(ExponentialAttributeCurve $exponentialAttributeCurve, int $size, int $min, int $max, int $increase, int $range): array
    {
        $curve = $exponentialAttributeCurve->setMin($min)
            ->setMax($max)
            ->setIncrease($increase)
            ->setRange($range);

        return $curve->generateValues($size, true);
    }

    /**
     * Set basic monster data.
     *
     * @param array $floats
     * @param array $integers
     * @param array $xpIntegers
     * @param integer $index
     * @return array
     */
    private function setMonsterStats(array $floats, array $integers, array $xpIntegers, int $index): array
    {

        $floatValue = min($floats[$index], 1.05);
        $xpDetails = [];

        if (isset($xpIntegers[$index])) {
            $xpDetails['xp'] = $xpIntegers[$index];
        }

        return array_merge([
            'str' => $integers[$index],
            'dur' => $integers[$index],
            'dex' => $integers[$index],
            'chr' => $integers[$index],
            'agi' => $integers[$index],
            'int' => $integers[$index],
            'focus' => $integers[$index],
            'ac' => $integers[$index],
            'accuracy' => $floats[$index],
            'casting_accuracy' => $floats[$index],
            'dodge' => $floats[$index],
            'criticality' => $floats[$index],
            'drop_check' => $floats[$index],
            'gold' => ceil($integers[$index] / 2),
            'health_range' => ceil($integers[$index] / 2) . '-' . $integers[$index],
            'attack_range' => ceil($integers[$index] / 2) . '-' . $integers[$index],
            'max_spell_damage' => $integers[$index],
            'max_affix_damage' => $integers[$index],
            'healing_percentage' => $floatValue,
            'spell_evasion' => $floatValue,
            'affix_resistance' => $floatValue,
            'entrancing_chance' => $floatValue,
            'devouring_light_chance' => $floatValue,
            'ambush_chance' => $floatValue >= 1 ? .95 : $floatValue,
            'ambush_resistance' => $floatValue >= 1 ? .85 : $floatValue,
            'counter_chance' => $floatValue >= 1 ? .95 : $floatValue,
            'counter_resistance' => $floatValue >= 1 ? .85 : $floatValue,
        ], $xpDetails);
    }
}
