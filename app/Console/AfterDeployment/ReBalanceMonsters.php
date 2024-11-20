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

    const MAX_STAT_AMOUNT = 100_000_000_000;

    const MAX_AC_AMOUNT = 4_000_000_000;

    const MAX_DAMAGE_AMOUNT = 100_000_000_000;

    const MAX_SPELL_DAMAGE = 50_000_000_000;

    const MAX_AFFIX_DAMAGE = 25_000_000_000;

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

        $this->line('Rebalancing regular monsters ...');

        $this->rebalanceRegularmaps($exponentialAttributeCurve);

        $this->line('Rebalancing end game monsters ...');

        $this->rebalanceEndGameMonsters($exponentialAttributeCurve);

        $this->line('Rebalancing weekly fight monsters ...');

        $this->rebalanceWeeklyFights($exponentialAttributeCurve);

        $this->line('Rebalancing special event map monsters ...');

        $this->rebalanceSpecialEventMaps($exponentialAttributeCurve);

        $this->line('Rebalancing raid monsters ...');

        $this->rebalanceRaidMonsters($exponentialAttributeCurve);

        $this->line('Rebalancing raid bosses ...');

        $this->rebalanceRaidBosses($exponentialAttributeCurve);

        $this->line('All done :)');
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

            $this->manageMonsters($celestials, $exponentialAttributeCurve, 50_000, 500_000, 10_000, 5_000, $mapName);
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

            $this->manageMonsters($celestials, $exponentialAttributeCurve, 1_000_000, 5_000_000, 100_000, 50_000, $mapName);
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

            $this->manageMonsters($celestials, $exponentialAttributeCurve, 8_000_000, 20_000_000, 1_000_000, 500_000, $mapName);
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
    private function rebalanceRaidMonsters(ExponentialAttributeCurve $exponentialAttributeCurve): void
    {
        $raids = Raid::all();

        foreach ($raids as $raid) {
            $monsterIds = $raid->raid_monster_ids;

            $mapName = Monster::whereIn('id', $monsterIds)->first()->gameMap->name;

            $raidType = new RaidType($raid->raid_type);

            $statRangeData = $this->fetchRaidMonsterStats($raidType);
            $statRangeData = (object) $statRangeData->toArray();

            $monsters = Monster::where('is_raid_monster', true)
                ->whereIn('id', $monsterIds)
                ->where('is_raid_boss', false)
                ->where('is_celestial_entity', false)
                ->whereNull('only_for_location_type')
                ->get();

            $this->manageMonsters($monsters, $exponentialAttributeCurve, $statRangeData->min, $statRangeData->max, $statRangeData->increase, $statRangeData->range, $mapName, true);
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
            $raidBossId = $raid->raid_boss_id;

            $mapName = Monster::find($raidBossId)->gameMap->name;

            $raidType = new RaidType($raid->raid_type);

            $statRangeData = $this->fetchRaidBossStats($raidType);
            $statRangeData = (object) $statRangeData->toArray();

            $monsters = Monster::where('is_raid_monster', false)
                ->where('id', $raidBossId)
                ->where('is_raid_boss', true)
                ->where('is_celestial_entity', false)
                ->whereNull('only_for_location_type')
                ->get();

            $this->manageMonsters($monsters, $exponentialAttributeCurve, $statRangeData->min, $statRangeData->max, $statRangeData->increase, $statRangeData->range, $mapName, false, true);
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
        return match (true) {
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
        return match (true) {
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
        return match (true) {
            $raidType->isPirateLordRaid() => collect([
                'min' => 10_000,
                'max' => 50_000,
                'increase' => 5_000,
                'range' => 2_500,
            ]),
            $raidType->isIceQueenRaid(), => collect([
                'min' => 150_000,
                'max' => 300_000,
                'increase' => 8_000,
                'range' => 5_000,
            ]),
            $raidType->isJesterOfTime() => collect([
                'min' => 250_000,
                'max' => 1_750_000,
                'increase' => 80_000,
                'range' => 50_000,
            ]),
            $raidType->isFrozenKing() => collect([
                'min' => 175_000,
                'max' => 500_000,
                'increase' => 10_000,
                'range' => 8_000,
            ]),
            default => collect([
                'min' => 10_000,
                'max' => 50_000,
                'increase' => 5_000,
                'range' => 2_500,
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
        return match (true) {
            $raidType->isPirateLordRaid() => collect([
                'min' => 10_000_000_000_000,
                'max' => 40_000_000_000_000,
                'increase' => 500_000,
                'range' => 5_000,
            ]),
            $raidType->isIceQueenRaid(), => collect([
                'min' => 50_000_000_000_000,
                'max' => 100_000_000_000_000,
                'increase' => 5_000_000,
                'range' => 500_000,
            ]),
            $raidType->isJesterOfTime() => collect([
                'min' => 75_000_000_000_000,
                'max' => 100_000_000_000_000,
                'increase' => 10_000_000,
                'range' => 5_000_000,
            ]),
            $raidType->isFrozenKing() => collect([
                'min' => 75_000_000_000_000,
                'max' => 125_000_000_000_000,
                'increase' => 5_000_000,
                'range' => 500_000,
            ]),
            default => collect([
                'min' => 10_000_000_000_000,
                'max' => 40_000_000_000_000,
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
    private function manageMonsters(Collection $monsters, ExponentialAttributeCurve $exponentialAttributeCurve, int $min, int $max, int $increase, int $range, string $mapName, bool $isRaidMonster = false, bool $isRaidBoss = false): void
    {
        $floats = $this->generateFloats($exponentialAttributeCurve, $monsters->count());
        $integers = $this->generateIntegers($exponentialAttributeCurve, $monsters->count(), $min, $max, $increase, $range);
        $xpIntegers = [];

        if (!$isRaidMonster && !$isRaidBoss) {
            $xpIntegers = $this->getXPIntegers($exponentialAttributeCurve, $monsters->count(), $mapName);
        }

        if ($isRaidMonster) {
            $xpIntegers = $this->getRaidMonsterXpIntegers($exponentialAttributeCurve, $monsters->count(), $mapName);
        }

        if ($isRaidBoss) {
            $xpIntegers = [$this->getRaidBossXpIntegers($mapName)];
        }

        $atonements = $this->fetchElementalAtonements($exponentialAttributeCurve, $mapName, $monsters->count(), $isRaidMonster);

        foreach ($monsters as $index => $monster) {
            $monsterStats = $this->setMonsterStats($floats, $integers, $xpIntegers, $index);

            if (in_array($mapName, $this->regularMaps)) {
                $monsterStats = [
                    ...$monsterStats,
                    'ambush_chance' => 0,
                    'ambush_resistance' => 0,
                    'counter_chance' => 0,
                    'counter_resistance' => 0,
                    'life_stealing_resistance' => 0,
                ];
            }

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

    private function getRaidMonsterXpIntegers(ExponentialAttributeCurve $exponentialAttributeCurve, int $size, string $mapName): array
    {
        return match ($mapName) {
            MapNameValue::SURFACE => $this->generateIntegers($exponentialAttributeCurve, $size, 500, 1_000, 50, 25),
            MapNameValue::ICE_PLANE,
            MapNameValue::DELUSIONAL_MEMORIES => $this->generateIntegers($exponentialAttributeCurve, $size, 1_000, 5_000, 500, 250),
            default => $this->generateIntegers($exponentialAttributeCurve, $size, 500, 1_000, 50, 25)
        };
    }

    private function getRaidBossXpIntegers(string $mapName): int
    {
        return match ($mapName) {
            MapNameValue::SURFACE => 5_000,
            MapNameValue::ICE_PLANE,
            MapNameValue::DELUSIONAL_MEMORIES => 8_000,
            default => 5_000
        };
    }

    /**
     * Get XP Data for the monsters
     *
     * @param ExponentialAttributeCurve $exponentialAttributeCurve
     * @param integer $size
     * @param string|null $mapName
     * @return array
     */
    private function getXPIntegers(ExponentialAttributeCurve $exponentialAttributeCurve, int $size, string $mapName): array
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

        $statAmount = $integers[$index];
        $armourClass = $integers[$index];
        $damage = $integers[$index];
        $spellDamage = $integers[$index];
        $affixDamage = $integers[$index];

        if ($statAmount > self::MAX_STAT_AMOUNT) {
            $statAmount = self::MAX_STAT_AMOUNT;
        }

        if ($armourClass > self::MAX_AC_AMOUNT) {
            $armourClass = self::MAX_AC_AMOUNT;
        }

        if ($damage > self::MAX_DAMAGE_AMOUNT) {
            $damage = self::MAX_DAMAGE_AMOUNT;
        }

        if ($spellDamage > self::MAX_SPELL_DAMAGE) {
            $spellDamage = self::MAX_SPELL_DAMAGE;
        }

        if ($affixDamage > self::MAX_AFFIX_DAMAGE) {
            $affixDamage = self::MAX_AFFIX_DAMAGE;
        }

        return array_merge([
            'str' => $statAmount,
            'dur' => $statAmount,
            'dex' => $statAmount,
            'chr' => $statAmount,
            'agi' => $statAmount,
            'int' => $statAmount,
            'focus' => $statAmount,
            'ac' => $armourClass,
            'accuracy' => $floats[$index],
            'casting_accuracy' => $floats[$index],
            'dodge' => $floats[$index],
            'criticality' => $floats[$index],
            'drop_check' => $floats[$index],
            'gold' => ceil($integers[$index] / 2),
            'health_range' => ceil($integers[$index] / 2) . '-' . $integers[$index],
            'attack_range' => ceil($damage / 2) . '-' . $damage,
            'max_spell_damage' => $spellDamage,
            'max_affix_damage' => $affixDamage,
            'healing_percentage' => $floatValue,
            'spell_evasion' => $floatValue,
            'affix_resistance' => $floatValue,
            'entrancing_chance' => $floatValue,
            'devouring_light_chance' => $floatValue,
            'ambush_chance' => $floatValue >= 1 ? .90 : $floatValue,
            'ambush_resistance' => $floatValue >= 1 ? .80 : $floatValue,
            'counter_chance' => $floatValue >= 1 ? .90 : $floatValue,
            'counter_resistance' => $floatValue >= 1 ? .80 : $floatValue,
            'life_stealing_resistance' => $floatValue >= 1 ? .45 : $floatValue,
        ], $xpDetails);
    }
}
