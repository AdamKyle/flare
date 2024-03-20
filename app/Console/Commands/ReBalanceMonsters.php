<?php

namespace App\Console\Commands;

use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use Illuminate\Console\Command;
use App\Flare\Values\MapNameValue;
use App\Flare\ExponentialCurve\Curve\ExponentialAttributeCurve;
use Illuminate\Database\Eloquent\Collection;

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

    /**
     * Execute the console command.
     */
    public function handle(ExponentialAttributeCurve $exponentialAttributeCurve) {

        foreach ($this->regularMaps as $map) {
            $gameMap  = GameMap::where('name', $map)->first();
            $monsters = Monster::where('game_map_id', $gameMap->id)
                               ->where('is_raid_monster', false)
                               ->where('is_raid_boss', false)
                               ->where('is_celestial_entity', false)
                               ->get();

            $this->manageMonsters($monsters, $exponentialAttributeCurve, 8, 2000000000, 100000, 500, $map);
        }

        // Purgatory Monsters:

        $gameMap  = GameMap::where('name', MapNameValue::PURGATORY)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
                           ->where('is_raid_monster', false)
                           ->where('is_raid_boss', false)
                           ->where('is_celestial_entity', false)
                           ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 1000000, 4000000000, 100000, 500, MapNameValue::PURGATORY);

        // Ice Plane Monsters:
        $gameMap  = GameMap::where('name', MapNameValue::ICE_PLANE)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
                           ->where('is_raid_monster', false)
                           ->where('is_raid_boss', false)
                           ->where('is_celestial_entity', false)
                           ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 5000000, 8000000000, 1000000, 5000, MapNameValue::ICE_PLANE);

        // Twisted Memories Monsters:
        $gameMap  = GameMap::where('name', MapNameValue::TWISTED_MEMORIES)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', false)
            ->where('is_celestial_entity', false)
            ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 10000000, 16000000000, 1000000, 5000, MapNameValue::TWISTED_MEMORIES);

        // Raid Monsters:
        $monsters = Monster::orderBy('game_map_id')
                            ->where('is_celestial_entity', false)
                            ->where('is_raid_monster', true)
                            ->where('is_raid_boss', false)
                            ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 4000000000, 25000000000, 100000, 500);
    }

    protected function manageMonsters(Collection $monsters, ExponentialAttributeCurve $exponentialAttributeCurve, int $min, int $max, int $increase, int $range, ?string $mapName = null): void {
        $floats   = $this->generateFloats($exponentialAttributeCurve, $monsters->count());
        $integers = $this->generateIntegers($exponentialAttributeCurve, $monsters->count(), $min, $max, $increase, $range);
        $xpIntegers = $this->getXPIntegers($exponentialAttributeCurve, $monsters->count(), $mapName);

        foreach ($monsters as $index => $monster) {
            $monsterStats = $this->setMonsterStats($floats, $integers, $xpIntegers, $index);

            $monster->update($monsterStats);
        }
    }

    protected function getXPIntegers(ExponentialAttributeCurve $exponentialAttributeCurve, int $size, ?string $mapName = null): array {
        if (in_array($mapName, $this->regularMaps)) {
            return $this->generateIntegers($exponentialAttributeCurve, $size, 2, 1000, 2, 10);
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

        // Raid Monsters
        return $this->generateIntegers($exponentialAttributeCurve, $size, 100, 4500, 100, 50);
    }

    protected function generateFloats(ExponentialAttributeCurve $exponentialAttributeCurve, int $size): array {
        $curve = $exponentialAttributeCurve->setMin(0.001)
                                           ->setMax(1.0)
                                           ->setIncrease(0.08)
                                           ->setRange(0.01);

        return $curve->generateValues($size);
    }

    protected function generateIntegers(ExponentialAttributeCurve $exponentialAttributeCurve, int $size, int $min, int $max, int $increase, int $range): array {
        $curve = $exponentialAttributeCurve->setMin($min)
                                           ->setMax($max)
                                           ->setIncrease($increase)
                                           ->setRange($range);

        return $curve->generateValues($size, true);
    }

    private function setMonsterStats(array $floats, array $integers, array $xpIntegers, int $index): array {

        $floatValue = min($floats[$index], 1.05);

        return [
            'str'                    => $integers[$index],
            'dur'                    => $integers[$index],
            'dex'                    => $integers[$index],
            'chr'                    => $integers[$index],
            'agi'                    => $integers[$index],
            'int'                    => $integers[$index],
            'focus'                  => $integers[$index],
            'ac'                     => $integers[$index],
            'accuracy'               => $floats[$index],
            'casting_accuracy'       => $floats[$index],
            'dodge'                  => $floats[$index],
            'criticality'            => $floats[$index],
            'drop_check'             => $floats[$index],
            'gold'                   => ceil($integers[$index] / 2),
            'health_range'           => ceil($integers[$index] / 2) . '-' . $integers[$index],
            'attack_range'           => ceil($integers[$index] / 2) . '-' . $integers[$index],
            'xp'                     => $xpIntegers[$index],
            'max_spell_damage'       => $integers[$index],
            'max_affix_damage'       => $integers[$index],
            'healing_percentage'     => $floatValue,
            'spell_evasion'          => $floatValue,
            'affix_resistance'       => $floatValue,
            'entrancing_chance'      => $floatValue,
            'devouring_light_chance' => $floatValue,
        ];
    }
}
