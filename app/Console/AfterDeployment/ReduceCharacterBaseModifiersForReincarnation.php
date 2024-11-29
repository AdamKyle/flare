<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use Illuminate\Console\Command;

class ReduceCharacterBaseModifiersForReincarnation extends Command
{

    const BASE_DAMAGE_MOD_MAX = 0.50;

    const BASE_STAT_MOD_MAX = 0.60;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reduce:character-base-modifiers-for-reincarnation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reduce the character base modifiers for thos who are reincarnated and over the new max';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Character::chunkById(100, function ($characters) {
            foreach ($characters as $character) {
                if ($character->base_damage_stat_mod > self::BASE_DAMAGE_MOD_MAX) {
                    $character->base_damage_stat_mod = self::BASE_DAMAGE_MOD_MAX;
                }

                if ($character->base_stat_mod > self::BASE_STAT_MOD_MAX) {
                    $character->base_stat_mod = self::BASE_STAT_MOD_MAX;
                }

                $character->save();
            }
        });
    }
}
