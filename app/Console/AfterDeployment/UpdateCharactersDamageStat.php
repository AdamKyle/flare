<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class UpdateCharactersDamageStat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:characters-damage-stat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates all characters to have proper damage stat';

    /**
     * Execute the console command.
     */
    public function handle() {
        Character::chunkById(100, function($characters) {
            foreach ($characters as $character) {
              $character->update([
                  'damage_stat' => $character->class->damage_stat,
              ]);
            }
        });

        Artisan::call('create:character-attack-data');
    }
}
