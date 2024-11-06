<?php

namespace App\Console\Commands;

use App\Flare\ExponentialCurve\Curve\ExponentialAttributeCurve;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Values\LocationType;
use App\Flare\Values\MapNameValue;
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

        $this->rebalanceWeeklyFights($exponentialAttributeCurve);


        // Purgatory Monsters:

        $gameMap = GameMap::where('name', MapNameValue::PURGATORY)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', false)
            ->where('is_celestial_entity', false)
            ->whereNull('only_for_location_type')
            ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 1000000, 4000000000, 100000, 500, MapNameValue::PURGATORY);

        // Ice Plane Monsters:
        $gameMap = GameMap::where('name', MapNameValue::ICE_PLANE)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', false)
            ->where('is_celestial_entity', false)
            ->whereNull('only_for_location_type')
            ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 5000000, 8000000000, 1000000, 5000, MapNameValue::ICE_PLANE);

        // Twisted Memories Monsters:
        $gameMap = GameMap::where('name', MapNameValue::TWISTED_MEMORIES)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', false)
            ->where('is_celestial_entity', false)
            ->whereNull('only_for_location_type')
            ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 10000000, 16000000000, 1000000, 5000, MapNameValue::TWISTED_MEMORIES);

        // Twisted Memories Weekly Fights:
        $gameMap = GameMap::where('name', MapNameValue::TWISTED_MEMORIES)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', false)
            ->where('is_celestial_entity', false)
            ->whereNotNull('only_for_location_type')
            ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 500000000000, 2000000000000, 150000000000, 1000000000, MapNameValue::TWISTED_MEMORIES);

        // Delusional Memories Monsters:
        $gameMap = GameMap::where('name', MapNameValue::DELUSIONAL_MEMORIES)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', false)
            ->where('is_celestial_entity', false)
            ->whereNull('only_for_location_type')
            ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 50000000, 32000000000, 1000000, 5000, MapNameValue::DELUSIONAL_MEMORIES);

        // Delusional Memories Celestials:
        $gameMap = GameMap::where('name', MapNameValue::DELUSIONAL_MEMORIES)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', false)
            ->where('is_celestial_entity', true)
            ->whereNull('only_for_location_type')
            ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 100000000000, 350000000000, 50000000000, 1000000000, MapNameValue::DELUSIONAL_MEMORIES);

        // Delusional Memories Weekly Fights:
        $gameMap = GameMap::where('name', MapNameValue::DELUSIONAL_MEMORIES)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', false)
            ->where('is_celestial_entity', false)
            ->whereNotNull('only_for_location_type')
            ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 500000000000, 4000000000000, 150000000000, 1000000000, MapNameValue::DELUSIONAL_MEMORIES);

        // Shadow Planes Weekly Fights:
        $gameMap = GameMap::where('name', MapNameValue::SHADOW_PLANE)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', false)
            ->where('is_celestial_entity', false)
            ->whereNotNull('only_for_location_type')
            ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 10000000, 30000000, 10000000, 1000000, MapNameValue::SHADOW_PLANE);

        // Hells Weekly Fights:
        $gameMap = GameMap::where('name', MapNameValue::HELL)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', false)
            ->where('is_celestial_entity', false)
            ->whereNotNull('only_for_location_type')
            ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 20000000, 60000000, 10000000, 1000000, MapNameValue::HELL);

        // Surface Raid Monsters:
        $gameMap = GameMap::where('name', MapNameValue::SURFACE)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
            ->where('is_celestial_entity', false)
            ->where('is_raid_monster', true)
            ->where('is_raid_boss', false)
            ->whereNull('only_for_location_type')
            ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 4000000000000, 8000000000000, 1000000000000, 500000000000, $gameMap->name, true);

        // Surface Raid Boss:
        $gameMap = GameMap::where('name', MapNameValue::SURFACE)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
            ->where('is_celestial_entity', false)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', true)
            ->whereNull('only_for_location_type')
            ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 5000000000000000, 10000000000000000, 10000000000000, 50000000000000, $gameMap->name, true);

        // Ice Plane Raid Monsters
        $gameMap = GameMap::where('name', MapNameValue::ICE_PLANE)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
            ->where('is_celestial_entity', false)
            ->where('is_raid_monster', true)
            ->where('is_raid_boss', false)
            ->whereNull('only_for_location_type')
            ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 8000000000000, 14000000000000, 1000000000000, 500000000000, $gameMap->name, true);

        // Ice Plane Raid Boss
        $gameMap = GameMap::where('name', MapNameValue::ICE_PLANE)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
            ->where('is_celestial_entity', false)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', true)
            ->whereNull('only_for_location_type')
            ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 15000000000000000, 30000000000000000, 10000000000000, 50000000000000, $gameMap->name, true);

        // Delusional Memories Raid Monsters:
        $gameMap = GameMap::where('name', MapNameValue::DELUSIONAL_MEMORIES)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
            ->where('is_celestial_entity', false)
            ->where('is_raid_monster', true)
            ->where('is_raid_boss', false)
            ->whereNull('only_for_location_type')
            ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 16000000000000, 32000000000000, 1000000000000, 500000000000, $gameMap->name, true);

        // Delusional Memories Raid Bosses:
        $gameMap = GameMap::where('name', MapNameValue::DELUSIONAL_MEMORIES)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
            ->where('is_celestial_entity', false)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', true)
            ->whereNull('only_for_location_type')
            ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 30000000000000000, 60000000000000000, 10000000000000, 50000000000000, $gameMap->name, true);
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

            $this->manageMonsters($celestials, $exponentialAttributeCurve, 50_000_000, 300_000_000, 1_000_000, 5000, $mapName);
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

            $statRangeData = $this->fetchMonsterStatRange($locationType);
            $statRangeData = (object) $statRangeData->toArray();

            $monsters = Monster::where('game_map_id', $gameMap->id)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->where('is_celestial_entity', false)
                ->where('only_for_location_type', $location->type)
                ->get();

            $this->manageMonsters($monsters, $exponentialAttributeCurve, $statRangeData->min, $statRangeData->max, $statRangeData->increase, $statRangeData->max, $gameMap->name);
        }
    }

    private function fetchMonsterStatRange(LocationType $locationType): SupportCollection
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

        if ($mapName === MapNameValue::ICE_PLANE) {
            return $this->generateIntegers($exponentialAttributeCurve, $size, 30, 3000, 20, 25);
        }

        if ($mapName === MapNameValue::TWISTED_MEMORIES) {
            return $this->generateIntegers($exponentialAttributeCurve, $size, 60, 4000, 40, 50);
        }

        if ($mapName === MapNameValue::DELUSIONAL_MEMORIES) {
            return $this->generateIntegers($exponentialAttributeCurve, $size, 120, 8000, 40, 50);
        }

        // Raid Monsters
        return $this->generateIntegers($exponentialAttributeCurve, $size, 100, 4500, 100, 50);
    }

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

    private function generateFloats(ExponentialAttributeCurve $exponentialAttributeCurve, int $size, float $min = 0.001, float $max = 1.0, float $increase = 0.08, float $range = 0.01): array
    {
        $curve = $exponentialAttributeCurve->setMin($min)
            ->setMax($max)
            ->setIncrease($increase)
            ->setRange($range);

        return $curve->generateValues($size);
    }

    private function generateIntegers(ExponentialAttributeCurve $exponentialAttributeCurve, int $size, int $min, int $max, int $increase, int $range): array
    {
        $curve = $exponentialAttributeCurve->setMin($min)
            ->setMax($max)
            ->setIncrease($increase)
            ->setRange($range);

        return $curve->generateValues($size, true);
    }

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
        ], $xpDetails);
    }
}
