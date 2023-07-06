<?php

namespace App\Console\Commands;

use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use Illuminate\Console\Command;
use App\Flare\Values\MapNameValue;
use App\Flare\AffixGenerator\Curve\ExponentialAttributeCurve;
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

    /**
     * Execute the console command.
     */
    public function handle(ExponentialAttributeCurve $exponentialAttributeCurve) {
        $regularMaps = [
            MapNameValue::SURFACE,
            MapNameValue::LABYRINTH,
            MapNameValue::DUNGEONS,
            MapNameValue::SHADOW_PLANE,
            MapNameValue::HELL,
        ];

        foreach ($regularMaps as $map) {
            $gameMap  = GameMap::where('name', $map)->first();
            $monsters = Monster::where('game_map_id', $gameMap->id)
                               ->where('is_raid_monster', false)
                               ->where('is_raid_boss', false)
                               ->where('is_celestial_entity', false)
                               ->get();

            $this->manageMonsters($monsters, $exponentialAttributeCurve, 12, 2000000000, 100000, 500);
        }

        // Purgatory Monsters:

        $gameMap  = GameMap::where('name', MapNameValue::PURGATORY)->first();
        $monsters = Monster::where('game_map_id', $gameMap->id)
                           ->where('is_raid_monster', false)
                           ->where('is_raid_boss', false)
                           ->where('is_celestial_entity', false)
                           ->get();

        $this->manageMonsters($monsters, $exponentialAttributeCurve, 1000000, 4000000000, 100000, 500);

        // Raid Monsters:
        $gameMap  = GameMap::where('name', MapNameValue::SURFACE)->first();
        $monsters = Monster::orderBy('game_map_id')
                            ->where('is_celestial_entity', false)
                            ->where('is_raid_monster', true)
                            ->where('is_raid_boss', false)
                            ->get();
        
        $this->manageMonsters($monsters, $exponentialAttributeCurve, 4000000000, 25000000000, 100000, 500);
    }

    protected function manageMonsters(Collection $monsters, ExponentialAttributeCurve $exponentialAttributeCurve, int $min, int $max, int $increase, int $range): void {
        $floats   = $this->generateFloats($exponentialAttributeCurve, $monsters->count());
        $integers = $this->generateIntegers($exponentialAttributeCurve, $monsters->count(), $min, $max, $increase, $range);

        foreach ($monsters as $index => $monster) {
            $monsterStats = $this->setMonsterStats($floats, $integers, $index);

            $monster->update($monsterStats);
        }
    }

    protected function generateFloats(ExponentialAttributeCurve $exponentialAttributeCurve, int $size): array {
        $curve = $exponentialAttributeCurve->setMin(0.01)
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

    private function setMonsterStats(array $floats, array $integers, int $index): array {
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
            'max_spell_damage'       => $integers[$index],
            'max_affix_damage'       => $integers[$index],
            'healing_percentage'     => $floats[$index],
            'spell_evasion'          => $floats[$index],
            'affix_resistance'       => $floats[$index],
            'entrancing_chance'      => $floats[$index],
            'devouring_light_chance' => $floats[$index],
        ];
    }
}
