<?php

namespace App\Console\Commands;

use App\Flare\Models\GameMap;
use Illuminate\Console\Command;

class UpdatePlaneBonuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:planes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all the planes deductions and bonuses';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {

        foreach (GameMap::all() as $gameMap) {
            if ($gameMap->mapType()->isShadowPlane()) {
                $gameMap->update([
                    'xp_bonus'                   => 0.05,
                    'skill_training_bonus'       => 0.05,
                    'drop_chance_bonus'          => 0.05,
                    'enemy_stat_bonus'           => 0.10,
                    'character_attack_reduction' => 0.05
                ]);
            }

            if ($gameMap->mapType()->isHell()) {
                $gameMap->update([
                    'xp_bonus'                   => 0.10,
                    'skill_training_bonus'       => 0.10,
                    'drop_chance_bonus'          => 0.10,
                    'enemy_stat_bonus'           => 0.15,
                    'character_attack_reduction' => 0.10
                ]);
            }

            if ($gameMap->mapType()->isPurgatory()) {
                $gameMap->update([
                    'xp_bonus'                   => 0.15,
                    'skill_training_bonus'       => 0.15,
                    'drop_chance_bonus'          => 0.15,
                    'enemy_stat_bonus'           => 0.20,
                    'character_attack_reduction' => 0.15
                ]);
            }
        }
    }
}
